<?php

namespace App\Http\Controllers;

use App\Models\GroupMatch;
use App\Models\PlayerInvitation;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Services\GroupMatchQuestionnaire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlayerProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $sports = Sport::query()->whereIn('slug', array_keys(GroupMatchQuestionnaire::TeamSizes))
            ->where('is_active', true)->orderBy('sort_order')->get();
        $sport = $sports->firstWhere('slug', $request->query('sport')) ?? $sports->first();
        $profiles = $profile = $played = $upcoming = $incoming = $outgoing = null;
        $myRatings = $myResults = collect();

        if ($sport) {
            $base = PlayerProfile::query()->whereBelongsTo($sport);
            $profile = (clone $base)->whereBelongsTo($user)->withAvg('ratings', 'score')->first();
            $countPlayedResults = fn (Builder $query) => $query->whereHas('groupMatch', fn (Builder $match) => $match->played());
            $profiles = $base->whereHas('user', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
                ->with('user:id,name')->withAvg('ratings', 'score')->withCount([
                    'ratings',
                    'results' => $countPlayedResults,
                    'results as wins' => fn (Builder $query) => $countPlayedResults($query)->whereColumn('own_score', '>', 'opponent_score'),
                    'results as draws' => fn (Builder $query) => $countPlayedResults($query)->whereColumn('own_score', '=', 'opponent_score'),
                ])->orderByDesc('wins')->orderByDesc('ratings_avg_score')->orderBy('id')
                ->paginate(15)->withQueryString();

            if ($profile) {
                $myRatings = DB::table('player_ratings')->where('rater_id', $user->id)
                    ->whereIn('player_profile_id', $profiles->getCollection()->modelKeys())->pluck('score', 'player_profile_id');
                $played = $this->myMatches($sport, $user)->played()->with('reservation')
                    ->latest('ends_at')->paginate(10, ['*'], 'rezultati')->withQueryString();
                $myResults = $profile->results()->whereIn('group_match_id', $played->getCollection()->modelKeys())->get()->keyBy('group_match_id');
            }

            $upcoming = $this->myMatches($sport, $user)->where('status', GroupMatch::Open)
                ->where('closes_at', '>', now())->orderBy('starts_at')->get();
            $invitations = PlayerInvitation::query()->whereHas('groupMatch', fn (Builder $query) => $query
                ->whereBelongsTo($sport)->where('status', GroupMatch::Open)->where('closes_at', '>', now()))
                ->with('groupMatch')->latest('id');
            $incoming = (clone $invitations)->where('recipient_id', $user->id)
                ->whereDoesntHave('groupMatch.confirmedPlayers', fn (Builder $query) => $query->where('user_id', $user->id))
                ->where(fn (Builder $query) => $query->whereNull('accepted')->orWhere('accepted', true))->with('sender:id,name')->get();
            $outgoing = $invitations->where('sender_id', $user->id)->with('recipient:id,name')->get();
        }

        return view('player-profiles.index', compact('sports', 'sport', 'profiles', 'profile', 'played', 'upcoming', 'incoming', 'outgoing', 'myRatings', 'myResults'));
    }

    public function save(Request $request, Sport $sport): RedirectResponse
    {
        abort_unless($sport->is_active && isset(GroupMatchQuestionnaire::TeamSizes[$sport->slug]), 404);
        $data = $request->validate(['starting_rating' => ['required', 'integer', 'between:1,10']]);
        PlayerProfile::query()->updateOrCreate(['user_id' => $request->user()->id, 'sport_id' => $sport->id], $data);

        return $this->backToSport($sport, 'Profil igrača je sačuvan.');
    }

    public function rate(Request $request, PlayerProfile $profile): RedirectResponse
    {
        $user = $request->user();
        abort_unless($profile->user_id !== $user->id && $profile->user->hasVerifiedEmail()
            && PlayerProfile::query()->whereBelongsTo($user)->where('sport_id', $profile->sport_id)->exists(), 403);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,10']]);
        $profile->ratings()->updateOrCreate(['rater_id' => $user->id], $data);

        return $this->backToSport(Sport::findOrFail($profile->sport_id), 'Ocena je sačuvana.');
    }

    public function result(Request $request, GroupMatch $match): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->myMatches($match->sport, $user)->played()->whereKey($match->id)->exists(), 403);
        $profile = PlayerProfile::query()->whereBelongsTo($user)->whereBelongsTo($match->sport)->firstOrFail();
        $data = $request->validate([
            'own_score' => ['required', 'integer', 'between:0,999'],
            'opponent_score' => ['required', 'integer', 'between:0,999'],
        ]);
        $profile->results()->updateOrCreate(['group_match_id' => $match->id], $data);

        return $this->backToSport($match->sport, 'Tvoj rezultat je sačuvan.');
    }

    public function invite(Request $request, PlayerProfile $profile): RedirectResponse
    {
        $user = $request->user();
        abort_unless($profile->user_id !== $user->id && $profile->user->hasVerifiedEmail(), 403);
        $data = $request->validate(['match' => ['required', 'uuid'], 'challenge' => ['required', 'boolean']]);
        $match = GroupMatch::query()->where('sport_id', $profile->sport_id)->findOrFail($data['match']);
        $invitation = new PlayerInvitation([
            'sender_id' => $user->id, 'recipient_id' => $profile->user_id, 'group_match_id' => $match->id,
            'challenge' => $data['challenge'],
        ]);
        $invitation->setRelation('groupMatch', $match);
        $this->invitationSeat($invitation);
        $saved = PlayerInvitation::query()->firstOrCreate(
            $invitation->only(['sender_id', 'recipient_id', 'group_match_id']),
            $invitation->only(['challenge']),
        );

        return $this->backToSport($match->sport, $saved->wasRecentlyCreated
            ? 'Poziv je dostupan igraču na njegovom nalogu.' : 'Ovom igraču već si poslao poziv za taj termin.');
    }

    public function respond(Request $request, PlayerInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->recipient_id === $request->user()->id, 403);
        $request->validate(['accept' => ['required', 'boolean']]);
        if ($request->boolean('accept')) {
            abort_if($invitation->accepted === false, 403);
            $parameters = $this->invitationSeat($invitation);
            PlayerInvitation::query()->whereKey($invitation->id)->whereNull('accepted')->update(['accepted' => true]);
            abort_unless($invitation->refresh()->accepted, 403);

            return redirect()->route('group-matches.show', $parameters);
        }

        PlayerInvitation::query()->whereKey($invitation->id)->whereNull('accepted')->update(['accepted' => false]);

        return $this->backToSport($invitation->groupMatch->sport, 'Odgovor na poziv je sačuvan.');
    }

    private function myMatches(Sport $sport, User $user): Builder
    {
        return GroupMatch::query()->whereBelongsTo($sport)
            ->whereHas('confirmedPlayers', fn (Builder $query) => $query->where('user_id', $user->id));
    }

    private function invitationSeat(PlayerInvitation $invitation): array
    {
        $match = $invitation->groupMatch;
        $players = $match->confirmedPlayers;
        $sender = $players->firstWhere('user_id', $invitation->sender_id);
        if (! config('arena.booking.is_open') || ! $match->sport->is_active || ! $match->sport->supports_online_booking
            || $match->status !== GroupMatch::Open || ! $match->closes_at->isFuture()
            || ! $sender || $players->contains('user_id', $invitation->recipient_id)) {
            throw ValidationException::withMessages(['match' => 'Ovaj termin više nije otvoren za taj poziv.']);
        }

        $teamInvite = ! $invitation->challenge && $sender->side === 'A' ? $match->team_invite : null;
        $opponentSide = $sender->side === 'A' ? 'B' : 'A';
        $preferredSides = $invitation->challenge ? [$opponentSide] : [$sender->side, $opponentSide];
        foreach ($preferredSides as $side) {
            $token = $side === 'A' ? $teamInvite : null;
            $availableSides = $match->joiningSides($token);
            if (in_array($side, $availableSides, true) && $players->where('side', $side)->count() < $match->team_size) {
                return ['match' => $match, 'strana' => $side, 'poziv' => $token];
            }
        }

        throw ValidationException::withMessages(['match' => 'Za ovu vrstu poziva trenutno nema slobodnog mesta.']);
    }

    private function backToSport(Sport $sport, string $status): RedirectResponse
    {
        return redirect()->route('player-profiles.index', ['sport' => $sport->slug])->with('group_match_status', $status);
    }
}
