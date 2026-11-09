<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\GroupMatchMail;
use App\Models\Court;
use App\Models\GroupMatch;
use App\Models\GroupMatchPlayer;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GroupMatchService
{
    public const ClosingMinutes = 30;

    public function __construct(
        private ReservationAvailabilityService $availability,
        private ReservationPricingService $pricing,
    ) {}

    public function quote(Sport $sport, CarbonInterface $start, int $duration): ?float
    {
        $end = $start->copy()->addMinutes($duration);
        $courts = $this->courts($sport)->get();

        return $this->availableCourts($courts, $start, $end)->min('price');
    }

    public function availableCourtNames(GroupMatch $match): Collection
    {
        if (! isset(GroupMatchQuestionnaire::TeamSizes[$match->sport->slug])) {
            return collect();
        }

        return $this->options($match, $this->courts($match->sport)->get())->pluck('court.name');
    }

    public function create(Sport $sport, CarbonInterface $start, int $duration, array $data): GroupMatch
    {
        return DB::transaction(function () use ($sport, $start, $duration, $data): GroupMatch {
            $sport = Sport::query()->lockForUpdate()->findOrFail($sport->id);
            $this->requireOpenBooking($sport);
            $closes = $start->copy()->subMinutes(self::ClosingMinutes);

            if ($closes->lte(now())) {
                $this->invalid('starts_at', 'Izaberi termin koji počinje za više od 30 minuta.');
            }

            $price = $this->quote($sport, $start, $duration);
            if ($price === null || $price !== (float) $data['quoted_price']) {
                $this->invalid('starts_at', 'Dostupnost ili cena se promenila. Ponovo proveri termin.');
            }

            $match = GroupMatch::create([
                'sport_id' => $sport->id,
                'status' => GroupMatch::Draft,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($duration),
                'closes_at' => $closes,
                'duration_minutes' => $duration,
                'quoted_price' => $price,
                'team_size' => GroupMatchQuestionnaire::TeamSizes[$sport->slug],
                'mixed_level' => $data['mixed_level'] ?? false,
                'team_invite' => ($data['has_team'] ?? false) ? (string) Str::uuid() : null,
            ]);
            $this->sendVerification($match->players()->create($this->playerData($data, 'A') + ['organizer' => true]));

            return $match;
        }, attempts: 3);
    }

    public function apply(GroupMatch $match, array $data): void
    {
        DB::transaction(function () use ($match, $data): void {
            $match = $this->lockMatch($match);
            $this->requireOpenBooking($match->sport);
            $this->requireStatus($match, [GroupMatch::Open]);

            $applicants = collect([$this->playerData($data, $data['side'])]);
            foreach ($data['guests'] ?? [] as $guest) {
                $applicants->push([
                    'name' => $guest['name'],
                    'email' => Str::lower($guest['email']),
                    'side' => $guest['side'],
                ] + (isset($guest['answers'], $guest['self_rating'])
                    ? $this->score($guest)
                    : ['answers' => [], 'self_rating' => 0, 'level' => 0]));
            }
            $emails = $applicants->pluck('email');
            if ($emails->unique()->count() !== $applicants->count()) {
                $this->invalid('guests', 'Za svakog igrača unesi drugu mejl adresu.');
            }

            $confirmed = $match->confirmedPlayers()->get()->countBy('side');
            $joinable = $match->joiningSides($data['invite'] ?? null);
            foreach ($applicants->countBy('side') as $side => $count) {
                if (! in_array($side, $joinable, true)) {
                    $this->invalid('side', 'Za izabranu stranu potreban je odgovarajući poziv.');
                }
                if ($confirmed->get($side, 0) + $count > $match->team_size) {
                    $this->invalid('side', 'U izabranoj ekipi nema dovoljno slobodnih mesta.');
                }
            }

            $userId = $data['user_id'] ?? null;
            $existing = $match->players()->where(fn (Builder $query) => $query->whereIn('email', $emails)
                ->when($userId, fn (Builder $query) => $query->orWhere('user_id', $userId)))->get();
            foreach ($applicants as $attributes) {
                $accountId = $attributes['user_id'] ?? null;
                $byEmail = $existing->firstWhere('email', $attributes['email']);
                $byAccount = $accountId ? $existing->firstWhere('user_id', $accountId) : null;
                $player = $byAccount ?? $byEmail;
                if (($byAccount && $byEmail && ! $byAccount->is($byEmail))
                    || ($player && ($player->confirmed_at || $player->level_review_status || $player->side !== $attributes['side']
                        || ($player->user_id && $player->user_id !== $accountId)))) {
                    $this->invalid('guests', 'Neka od unetih adresa već ima prijavu za ovaj termin.');
                }
                $this->checkOverlappingConfirmation($match, $attributes['email'], $accountId);
                if ($player && $accountId) {
                    $player->update($attributes);
                } elseif ($player?->needsQuestionnaire() && $attributes['answers'] !== []) {
                    $player->update(Arr::only($attributes, ['answers', 'self_rating', 'level']));
                }
                $this->sendVerification($player ?? $match->players()->create($attributes));
            }
        }, attempts: 3);
    }

    public function confirm(GroupMatchPlayer $player, array $questionnaire = []): GroupMatch
    {
        return DB::transaction(function () use ($player, $questionnaire): GroupMatch {
            $match = $this->lockMatch($player->groupMatch);
            $player = $match->players()->findOrFail($player->id);
            if ($player->confirmed_at || $player->level_review_status) {
                return $match;
            }

            $this->requireOpenBooking($match->sport);
            $this->requireStatus($match, [$player->organizer ? GroupMatch::Draft : GroupMatch::Open]);
            $this->checkPlayerCanTakeSeat($match, $player);
            $details = [];
            if ($player->needsQuestionnaire()) {
                if ($questionnaire === []) {
                    $this->invalid('answers', 'Popuni upitnik da potvrdiš svoje mesto.');
                }
                $details = $this->score($questionnaire);
            }
            if (! $player->organizer && $match->needsLevelReview((int) ($details['self_rating'] ?? $player->self_rating))) {
                $player->update($details + ['level_review_status' => GroupMatchPlayer::LevelReviewPending]);
            } else {
                $this->confirmSeat($match, $player, $details);
            }

            return $match;
        }, attempts: 3);
    }

    public function reviewPlayer(GroupMatch $match, string $playerId, bool $approve, User $admin): void
    {
        abort_unless($admin->role === UserRole::SuperAdmin, 403);

        DB::transaction(function () use ($match, $playerId, $approve): void {
            $match = $this->lockMatch($match);
            $this->requireStatus($match, [GroupMatch::Open]);
            $player = $match->playersAwaitingLevelReview()->find($playerId);
            if (! $player) {
                $this->invalid('player', 'Ova prijava više ne čeka pregled.');
            }

            if ($approve) {
                $this->requireOpenBooking($match->sport);
                $this->checkPlayerCanTakeSeat($match, $player);
                $this->confirmSeat($match, $player, ['level_review_status' => GroupMatchPlayer::LevelReviewApproved]);
            } else {
                $player->update(['level_review_status' => GroupMatchPlayer::LevelReviewDeclined]);
            }
        }, attempts: 3);
    }

    private function checkPlayerCanTakeSeat(GroupMatch $match, GroupMatchPlayer $player): void
    {
        if ($match->confirmedPlayers()->where('side', $player->side)->count() >= $match->team_size) {
            $this->invalid('side', 'Ta strana je popunjena. Pogledaj slobodna mesta na oglasu.');
        }
        $this->checkOverlappingConfirmation($match, $player->email, $player->user_id);
    }

    private function confirmSeat(GroupMatch $match, GroupMatchPlayer $player, array $details): void
    {
        $player->update($details + ['confirmed_at' => now()]);
        $match->load('confirmedPlayers');
        $full = $match->confirmedPlayers->count() === $match->capacity();
        $match->update([
            'status' => match (true) {
                ! $full => GroupMatch::Open,
                $match->needsLevelReview() => GroupMatch::Review,
                default => GroupMatch::Ready,
            },
        ]);
    }

    public function leave(GroupMatchPlayer $player): GroupMatch
    {
        return DB::transaction(function () use ($player): GroupMatch {
            $match = $this->lockMatch($player->groupMatch);
            $player = $match->players()->findOrFail($player->id);
            $this->requireStatus($match, GroupMatch::Pending);

            if ($player->organizer) {
                $this->close($match, 'Organizator je povukao oglas.');
            } else {
                $player->delete();
                if ($player->confirmed_at && in_array($match->status, [GroupMatch::Ready, GroupMatch::Review], true)) {
                    $match->update(['status' => GroupMatch::Open]);
                }
            }

            return $match;
        }, attempts: 3);
    }

    public function reconcile(): void
    {
        $sportIds = GroupMatch::query()
            ->where(fn ($query) => $query->where('status', GroupMatch::Ready)
                ->orWhere(fn ($expired) => $expired->whereIn('status', GroupMatch::Pending)->where('closes_at', '<=', now())))
            ->distinct()->pluck('sport_id');

        foreach ($sportIds as $sportId) {
            DB::transaction(function () use ($sportId): void {
                $sport = Sport::query()->lockForUpdate()->find($sportId);
                if (! $sport) {
                    return;
                }

                // Normal online and phone bookings take the same court locks.
                $supported = isset(GroupMatchQuestionnaire::TeamSizes[$sport->slug]);
                $courts = $supported ? $this->courts($sport)->lockForUpdate()->get() : collect();
                $matches = GroupMatch::query()->whereBelongsTo($sport)->whereIn('status', GroupMatch::Pending)
                    ->orderBy('starts_at')->orderBy('id')->get();
                $cutoff = now();
                foreach ($matches->where('closes_at', '<=', $cutoff) as $expired) {
                    $this->close($expired, 'Do 30 minuta pre početka termin nije rezervisan.');
                }

                if (! $supported || ! config('arena.booking.is_open') || ! $sport->is_active || ! $sport->supports_online_booking) {
                    return;
                }

                $waiting = $matches->where('closes_at', '>', $cutoff)
                    ->whereIn('status', [GroupMatch::Ready, GroupMatch::Review]);
                $waiting->load('confirmedPlayers');
                $waiting = $waiting->reject(function (GroupMatch $match): bool {
                    if (! $match->needsLevelReview()) {
                        return false;
                    }
                    if ($match->status === GroupMatch::Ready) {
                        $match->update(['status' => GroupMatch::Review]);
                    }

                    return true;
                });
                foreach ($this->overlappingGroups($waiting) as $group) {
                    $assignments = $this->assignCourts($group, $courts);
                    if ($assignments === null) {
                        foreach ($group->where('status', GroupMatch::Ready) as $match) {
                            $match->update(['status' => GroupMatch::Review]);
                        }
                        continue;
                    }

                    foreach ($group as $match) {
                        if ($match->closes_at->gt(now())) {
                            $this->reserve($match, $assignments[$match->id]);
                        } else {
                            $this->close($match, 'Do 30 minuta pre početka termin nije rezervisan.');
                        }
                    }
                }
            }, attempts: 3);
        }
    }

    public function approve(GroupMatch $match, User $admin): void
    {
        abort_unless($admin->role === UserRole::SuperAdmin, 403);

        DB::transaction(function () use ($match): void {
            $sport = Sport::query()->lockForUpdate()->findOrFail($match->sport_id);
            $this->requireOpenBooking($sport);
            $courts = $this->courts($sport)->lockForUpdate()->get();
            $match = GroupMatch::query()->findOrFail($match->id);
            $this->requireStatus($match, [GroupMatch::Review]);

            $choice = $this->options($match, $courts)->first();
            if (! $choice) {
                $this->invalid('match', 'Nema slobodnog terena po prikazanoj ceni za ovaj termin.');
            }

            $this->reserve($match, $choice);
        }, attempts: 3);
    }

    public function decline(GroupMatch $match, User $admin, string $note): void
    {
        abort_unless($admin->role === UserRole::SuperAdmin, 403);

        DB::transaction(function () use ($match, $note): void {
            $match = $this->lockMatch($match);
            $this->requireStatus($match, [GroupMatch::Review]);
            $this->close($match, $note);
        }, attempts: 3);
    }

    private function lockMatch(GroupMatch $match): GroupMatch
    {
        Sport::query()->lockForUpdate()->findOrFail($match->sport_id);

        return GroupMatch::query()->findOrFail($match->id);
    }

    private function requireOpenBooking(Sport $sport): void
    {
        if (! config('arena.booking.is_open') || ! $sport->is_active || ! $sport->supports_online_booking
            || ! isset(GroupMatchQuestionnaire::TeamSizes[$sport->slug])) {
            $this->invalid('sport_id', 'Grupne rezervacije za ovaj sport trenutno nisu dostupne.');
        }
    }

    private function requireStatus(GroupMatch $match, array $statuses): void
    {
        if (! in_array($match->status, $statuses, true) || $match->closes_at->lte(now())) {
            $this->invalid('match', 'Ovaj grupni termin trenutno ne prima tu promenu.');
        }
    }

    private function checkOverlappingConfirmation(GroupMatch $match, string $email, ?int $userId = null): void
    {
        $alreadyConfirmed = GroupMatchPlayer::query()->where(fn (Builder $query) => $query->where('email', $email)
            ->when($userId, fn (Builder $query) => $query->orWhere('user_id', $userId)))->whereNotNull('confirmed_at')
            ->where('group_match_id', '!=', $match->id)
            ->whereHas('groupMatch', fn ($query) => $query->where('sport_id', $match->sport_id)
                ->where('starts_at', '<', $match->ends_at)->where('ends_at', '>', $match->starts_at)
                ->where(fn ($group) => $group->where(fn ($open) => $open->whereIn('status', GroupMatch::Pending)->where('closes_at', '>', now()))
                    ->orWhere(fn ($booked) => $booked->where('status', GroupMatch::Booked)
                        ->whereHas('reservation', fn ($reservation) => $reservation
                            ->whereNull('deleted_at')->where('status', ReservationStatus::Reserved->value)))))
            ->exists();
        if ($alreadyConfirmed) {
            $this->invalid('email', 'Za ovo vreme već postoji potvrđena prijava za isti sport.');
        }
    }

    private function playerData(array $data, string $side): array
    {
        return [
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'side' => $side,
        ] + (isset($data['user_id']) ? [
            'user_id' => $data['user_id'],
            'self_rating' => $data['profile_rating'],
            'level' => $data['profile_rating'],
            'answers' => [],
        ] : $this->score($data));
    }

    private function score(array $data): array
    {
        $rating = (int) $data['self_rating'];

        return [
            'self_rating' => $rating,
            'answers' => $data['answers'],
            'level' => GroupMatchQuestionnaire::level($data['answers'], $rating),
        ];
    }

    private function sendVerification(GroupMatchPlayer $player): void
    {
        Mail::to($player->email)->queue((new GroupMatchMail($player, 'verify', $player->signedUrl()))->afterCommit());
    }

    private function close(GroupMatch $match, string $note): void
    {
        $match->update(['status' => GroupMatch::Closed, 'closing_note' => $note]);
        foreach ($match->confirmedPlayers()->get() as $player) {
            Mail::to($player->email)->queue((new GroupMatchMail($player, 'closed'))->afterCommit());
        }
    }

    private function courts(Sport $sport): Builder
    {
        return Court::query()->whereBelongsTo($sport)->where('is_active', true)
            ->where('capacity', '>=', 2 * GroupMatchQuestionnaire::TeamSizes[$sport->slug])->orderBy('id');
    }

    private function availableCourts(Collection $courts, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $courts->map(function (Court $court) use ($start, $end): ?array {
            if (! $this->availability->isAvailable($court, $start, $end)) {
                return null;
            }

            try {
                return ['court' => $court, 'price' => $this->pricing->calculateCourtPrice($court, $start, $end)];
            } catch (RuntimeException) {
                return null;
            }
        })->filter()->sortBy('price')->values();
    }

    private function options(GroupMatch $match, Collection $courts): Collection
    {
        return $this->availableCourts($courts, $match->starts_at, $match->ends_at)
            ->where('price', '<=', (float) $match->quoted_price);
    }

    private function overlappingGroups(Collection $matches): array
    {
        $groups = [];
        $end = null;
        foreach ($matches as $match) {
            if ($end === null || $match->starts_at->gte($end)) {
                $groups[] = collect();
                $end = $match->ends_at;
            }

            $groups[array_key_last($groups)]->push($match);
            if ($match->ends_at->gt($end)) {
                $end = $match->ends_at;
            }
        }

        return $groups;
    }

    private function assignCourts(Collection $matches, Collection $courts): ?array
    {
        if ($matches->contains('status', GroupMatch::Review)) {
            return null;
        }

        $options = $matches->mapWithKeys(fn (GroupMatch $match) => [$match->id => $this->options($match, $courts)]);
        $assignments = [];
        $windows = [];
        foreach ($matches->sortBy(fn (GroupMatch $match) => $options[$match->id]->count()) as $match) {
            $choice = $options[$match->id]->first(fn (array $candidate): bool => ! collect($windows[$candidate['court']->id] ?? [])
                ->contains(fn (GroupMatch $other): bool => $other->starts_at->lt($match->ends_at) && $other->ends_at->gt($match->starts_at)));
            if (! $choice) {
                return null;
            }

            $assignments[$match->id] = $choice;
            $windows[$choice['court']->id][] = $match;
        }

        return $assignments;
    }

    private function reserve(GroupMatch $match, array $choice): void
    {
        $players = $match->confirmedPlayers()->get();
        $organizer = $players->firstWhere('organizer', true);
        if (! $organizer || $players->count() !== $match->capacity()) {
            $this->invalid('match', 'Potrebna je cela ekipa sa potvrđenim prijavama.');
        }

        $reservation = new Reservation([
            'guest_name' => $organizer->name,
            'guest_email' => $organizer->email,
            'sport_id' => $match->sport_id,
            'court_id' => $choice['court']->id,
            'status' => ReservationStatus::Reserved,
            'starts_at' => $match->starts_at,
            'ends_at' => $match->ends_at,
            'duration_minutes' => $match->duration_minutes,
            'players_count' => $players->count(),
            'court_price' => $choice['price'],
            'total_price' => $choice['price'],
            'customer_note' => 'Grupni termin · Pronađi ekipu',
        ]);
        $reservation->suppressNotification = true;
        $reservation->save();
        $match->update(['reservation_id' => $reservation->id, 'status' => GroupMatch::Booked]);

        foreach ($players as $player) {
            Mail::to($player->email)->queue((new GroupMatchMail($player, 'booked'))->afterCommit());
        }
        if ($email = config('arena.contact.email')) {
            Mail::to($email)->queue((new AdminReservationNotificationMail($reservation, 'created'))->afterCommit());
        }
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
