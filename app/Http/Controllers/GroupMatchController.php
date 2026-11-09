<?php

namespace App\Http\Controllers;

use App\Models\GroupMatch;
use App\Models\GroupMatchPlayer;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Services\GroupMatchQuestionnaire;
use App\Services\GroupMatchService;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GroupMatchController extends Controller
{
    public function index(Request $request): View
    {
        $sports = $this->sports()->get();
        $sport = $sports->firstWhere('slug', $request->query('sport'));
        $matches = GroupMatch::query()
            ->whereIn('status', [GroupMatch::Open, GroupMatch::Ready, GroupMatch::Review])
            ->whereIn('sport_id', $sports->modelKeys())
            ->where('closes_at', '>', now())
            ->when($sport, fn ($query) => $query->whereBelongsTo($sport))
            ->with('sport')->withCount('confirmedPlayers')->withAvg('confirmedPlayers as average_rating', 'self_rating')
            ->orderBy('starts_at')->orderBy('id')->paginate(12)->withQueryString();

        return view('group-matches.index', compact('matches', 'sports', 'sport'));
    }

    public function create(Request $request, GroupMatchService $service): View|RedirectResponse
    {
        $sports = $this->sports()->get();
        $slot = $sport = $price = $account = null;
        if ($request->filled('starts_at')) {
            try {
                [$slot, $sport, $start] = $this->slot($request->query());
                $price = $service->quote($sport, $start, (int) $slot['duration_minutes']);
                if ($price === null) {
                    throw ValidationException::withMessages(['starts_at' => 'Za izabrano vreme nema slobodnog terena sa definisanom cenom.']);
                }
                $account = $this->playerAccount($request, $sport);
            } catch (ValidationException $exception) {
                return redirect()->route('group-matches.create')->withErrors($exception->errors())->withInput();
            }
        }

        return view('group-matches.create', compact('sports', 'slot', 'sport', 'price', 'account'));
    }

    public function store(Request $request, GroupMatchService $service): RedirectResponse
    {
        [$slot, $sport, $start] = $this->slot($request->all());
        $player = $this->validateApplication($request, $sport, [
            'quoted_price' => ['required', 'numeric', 'min:0'],
            'mixed_level' => ['sometimes', 'boolean'],
            'has_team' => ['sometimes', 'boolean'],
        ]);

        $service->create($sport, $start, (int) $slot['duration_minutes'], $player);

        return redirect()->route('group-matches.index')
            ->with('group_match_status', 'Poslali smo ti mejl. Potvrdi svoju prijavu iz mejla da bi se oglas prikazao na listi.');
    }

    public function show(Request $request, GroupMatch $match): View
    {
        abort_if($match->status === GroupMatch::Draft, 404);
        $match->load(['sport', 'reservation.court', 'confirmedPlayers']);
        $invite = $request->query('poziv');
        $teamAccess = is_string($invite) && $match->team_invite === $invite;
        $joinableSides = $match->joiningSides($teamAccess ? $invite : null);
        $account = $this->playerAccount($request, $match->sport);

        return view('group-matches.show', compact('match', 'teamAccess', 'joinableSides', 'account'));
    }

    public function join(Request $request, GroupMatch $match, GroupMatchService $service): RedirectResponse
    {
        abort_if($match->status === GroupMatch::Draft, 404);
        $collectGuestAnswers = $request->boolean('group_questionnaire');
        $data = $this->validateApplication($request, $match->sport, [
            'side' => ['required', Rule::in(['A', 'B'])],
            'invite' => ['nullable', 'uuid'],
            'guests' => ['sometimes', 'array', 'max:5'],
            'guests.*' => ['array:side,name,email,self_rating,answers'],
            'guests.*.side' => ['required', Rule::in(['A', 'B'])],
            'guests.*.name' => ['required_with:guests.*.email', 'nullable', 'string', 'max:255'],
            'guests.*.email' => ['required_with:guests.*.name', 'nullable', 'email:rfc', 'max:255'],
            'guests.*.self_rating' => [
                $collectGuestAnswers ? 'required_with:guests.*.email' : 'required_with:guests.*.answers',
                'nullable', 'integer', 'between:1,10',
            ],
            'guests.*.answers' => [
                $collectGuestAnswers ? 'required_with:guests.*.email' : 'required_with:guests.*.self_rating',
                'nullable', 'array:0,1,2', 'size:3',
            ],
            'guests.*.answers.*' => ['required', 'integer', 'between:0,3'],
        ]);
        $data['guests'] = array_filter($data['guests'] ?? [], fn (array $guest): bool => filled($guest['email'] ?? null));
        $service->apply($match, $data);

        return redirect()->route('group-matches.show', ['match' => $match, 'poziv' => $data['invite'] ?? null])
            ->with('group_match_status', $data['guests'] === []
                ? 'Potvrdi svoje mesto iz mejla.'
                : 'Tebi i pozvanim igračima poslali smo mejlove za potvrdu mesta.');
    }

    public function player(GroupMatchPlayer $player): View
    {
        $player->load(['groupMatch.sport', 'groupMatch.reservation', 'groupMatch.confirmedPlayers']);

        return view('group-matches.player', compact('player'));
    }

    public function updatePlayer(Request $request, GroupMatchPlayer $player, GroupMatchService $service): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', Rule::in(['confirm', 'leave'])]])['action'];
        if ($action === 'leave') {
            $match = $service->leave($player);

            return redirect()->route('group-matches.index')->with('group_match_status', $player->organizer
                ? 'Oglas je zatvoren.' : 'Tvoja prijava je povučena.');
        }

        $questionnaire = $player->needsQuestionnaire() ? $request->validate($this->questionnaireRules(), $this->messages()) : [];
        $service->confirm($player, $questionnaire);

        return redirect()->to($player->signedUrl());
    }

    private function sports(): \Illuminate\Database\Eloquent\Builder
    {
        return Sport::query()->whereIn('slug', array_keys(GroupMatchQuestionnaire::TeamSizes))
            ->where('is_active', true)->where('supports_online_booking', true)->orderBy('sort_order');
    }

    private function slot(array $input): array
    {
        $slot = Validator::make($input, [
            'sport_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration_minutes' => ['required', 'integer', Rule::in([60, 90, 120])],
        ], $this->messages())->validate();
        $sport = $this->sports()->find($slot['sport_id']);
        if (! $sport || ! config('arena.booking.is_open')) {
            throw ValidationException::withMessages(['sport_id' => 'Izaberi sport za koji su otvorene online rezervacije.']);
        }
        $start = Carbon::createFromFormat('!Y-m-d\TH:i', $slot['starts_at']);
        $end = $start->copy()->addMinutes((int) $slot['duration_minutes']);
        if (! in_array($start->minute, [0, 30], true)
            || $start->lte(now()->addMinutes(GroupMatchService::ClosingMinutes))
            || ! app(ReservationScheduleService::class)->isWithinOperatingHours($start, $end)) {
            throw ValidationException::withMessages(['starts_at' => 'Izaberi vreme na pun sat ili pola sata, u radnom vremenu i više od 30 minuta unapred.']);
        }

        return [$slot, $sport, $start];
    }

    private function playerRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ] + $this->questionnaireRules();
    }

    private function playerAccount(Request $request, Sport $sport): ?array
    {
        $user = $request->user();
        if (! $user?->hasVerifiedEmail()) {
            return null;
        }

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_rating' => PlayerProfile::matchRating($user, $sport),
        ];
    }

    private function validateApplication(Request $request, Sport $sport, array $rules): array
    {
        $account = $this->playerAccount($request, $sport);
        if ($account !== null) {
            return $request->validate($rules, $this->messages()) + $account;
        }

        return $request->validate($rules + $this->playerRules(), $this->messages());
    }

    private function questionnaireRules(): array
    {
        return [
            'self_rating' => ['required', 'integer', 'between:1,10'],
            'answers' => ['required', 'array:0,1,2', 'size:3'],
            'answers.*' => ['required', 'integer', 'between:0,3'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Unesi ime i prezime.',
            'email.required' => 'Unesi mejl za potvrdu mesta.',
            'email.email' => 'Proveri adresu mejla.',
            'self_rating.*' => 'Izaberi ocenu od 1 do 10.',
            'answers.*' => 'Odgovori na sva tri pitanja.',
            'sport_id.required' => 'Izaberi sport.',
            'starts_at.*' => 'Izaberi datum i vreme.',
            'duration_minutes.*' => 'Izaberi trajanje.',
            'side.required' => 'Izaberi stranu na kojoj želiš da igraš.',
            'guests.*.name.required_with' => 'Unesi ime za svakog igrača kog pozivaš.',
            'guests.*.email.required_with' => 'Unesi mejl za svakog igrača kog pozivaš.',
            'guests.*.email.email' => 'Proveri mejl pozvanog igrača.',
            'guests.*.self_rating.*' => 'Izaberi ocenu od 1 do 10 za svakog pozvanog igrača.',
            'guests.*.answers.*' => 'Odgovori na sva pitanja za svakog pozvanog igrača.',
        ];
    }
}
