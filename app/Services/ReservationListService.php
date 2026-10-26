<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ReservationListService
{
    private function concat(array $parts): string
    {
        return DB::getDriverName() === 'sqlite'
            ? '('.implode(' || ', $parts).')'
            : 'CONCAT('.implode(', ', $parts).')';
    }

    public function nameExpression(): string
    {
        return "COALESCE(NULLIF(TRIM((SELECT name FROM users WHERE users.id = reservations.user_id)), ''), NULLIF(TRIM(reservations.guest_name), ''), 'Gost')";
    }

    public function phoneExpression(string $column = 'reservations.guest_phone'): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')";
    }

    public function keyExpression(): string
    {
        $name = "LOWER(TRIM(COALESCE(reservations.guest_name, '')))";
        $phone = $this->phoneExpression();
        $user = $this->concat(["'user:'", 'reservations.user_id']);
        $guestPhone = $this->concat(["'guest:'", $name, "'|phone:'", $phone]);
        $guestEmail = $this->concat(["'guest:'", $name, "'|email:'", 'LOWER(TRIM(reservations.guest_email))']);
        $guestName = $this->concat(["'guest:'", $name, "'|no-contact'"]);
        $anonymous = $this->concat(["'anonymous:'", 'reservations.id']);

        return "CASE WHEN reservations.user_id IS NOT NULL THEN {$user} WHEN {$phone} <> '' THEN {$guestPhone} WHEN TRIM(COALESCE(reservations.guest_email, '')) <> '' THEN {$guestEmail} WHEN {$name} <> '' THEN {$guestName} ELSE {$anonymous} END";
    }

    public function query(Builder $query): Builder
    {
        return $query->select('reservations.*')
            ->selectRaw($this->keyExpression().' as reservation_customer_group')
            ->with(['user', 'sport', 'court']);
    }

    public function search(Builder $query, string $search): Builder
    {
        $pattern = '%'.$search.'%';
        $phone = preg_replace('/[^0-9]/', '', $search);

        return $query->where(function (Builder $query) use ($pattern, $phone): void {
            $query->where('guest_name', 'like', $pattern)
                ->orWhere('guest_phone', 'like', $pattern)
                ->orWhere('guest_email', 'like', $pattern)
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $pattern)->orWhere('phone', 'like', $pattern)->orWhere('email', 'like', $pattern));

            if (strlen($phone) >= 2) {
                $query->orWhereRaw($this->phoneExpression().' LIKE ?', ['%'.$phone.'%']);
            }
        });
    }

    public function paginateGroups(Builder $query, int $perPage, int $page, string $pageName): LengthAwarePaginator
    {
        $key = $this->keyExpression();
        $asOf = now();
        $groups = (clone $query)->reorder()->setEagerLoads([])->select([])
            ->selectRaw("{$key} as customer_key, MIN(".$this->nameExpression().') as customer_name, COUNT(*) as reservation_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN reservations.is_gift = 0 AND reservations.status = 'reserved' AND reservations.total_price > 0 AND reservations.ends_at <= ? THEN reservations.total_price ELSE 0 END), 0) as completed_revenue", [$asOf])
            ->selectRaw("COALESCE(SUM(CASE WHEN reservations.is_gift = 0 AND reservations.status = 'reserved' AND reservations.total_price > 0 AND reservations.starts_at >= ? THEN reservations.total_price ELSE 0 END), 0) as future_value", [$asOf])
            ->groupByRaw($key)->toBase();
        $total = DB::query()->fromSub(clone $groups, 'customer_groups')->count();
        $selectedGroups = DB::query()->fromSub(clone $groups, 'customer_groups')->orderByDesc('reservation_count')->orderByRaw('LOWER(customer_name)')->orderBy('customer_key')
            ->forPage($page, $perPage)->get()->keyBy('customer_key');
        $groupRanks = $selectedGroups->keys()->flip();
        $records = (clone $query)->whereIn(DB::raw($key), $selectedGroups->keys())->get()
            ->sortBy(fn (Reservation $record): int => $groupRanks[$record->reservation_customer_group])->values();
        foreach ($records as $record) {
            $record->setAttribute('customer_group_count', (int) $selectedGroups[$record->reservation_customer_group]->reservation_count);
            $record->setAttribute('customer_group_name', $selectedGroups[$record->reservation_customer_group]->customer_name);
            $record->setAttribute('customer_group_completed_revenue', (float) $selectedGroups[$record->reservation_customer_group]->completed_revenue);
            $record->setAttribute('customer_group_future_value', (float) $selectedGroups[$record->reservation_customer_group]->future_value);
        }

        return new class($records, $total, $perPage, $page, ['pageName' => $pageName]) extends LengthAwarePaginator
        {
            public function lastItem(): ?int
            {
                return $this->total() ? min($this->currentPage() * $this->perPage(), $this->total()) : null;
            }
        };
    }

    public function groupDescription(Reservation $record): HtmlString
    {
        $contact = $record->customer_display_phone ?: $record->customer_display_email;

        $details = ($record->customer_group_count ?? 0).' rezervacija u izabranom pregledu'
            .($contact ? ' · '.$contact : ' · Bez kontakta: grupisano po tačno unetom imenu');

        return new HtmlString('<span class="reservation-group-summary">'
            .'<span class="reservation-group-amount">Prihod od odigranih: <strong>'.number_format((float) $record->customer_group_completed_revenue, 2, ',', '.').' RSD</strong></span>'
            .'<span class="reservation-group-amount">Vrednost budućih: <strong>'.number_format((float) $record->customer_group_future_value, 2, ',', '.').' RSD</strong></span>'
            .'<span class="reservation-group-details">'.e($details).'</span></span>');
    }

    public function groupTitle(Reservation $record): string
    {
        $name = $record->customer_group_name ?? $record->customer_display_name;
        $phone = preg_replace('/[\s+()\-]/', '', $record->guest_phone ?? '');

        return $name.' · '.match (true) {
            filled($record->user_id) => 'Nalog #'.$record->user_id,
            filled($phone) => $phone,
            filled($record->guest_email) => mb_strtolower(trim($record->guest_email)),
            filled($record->guest_name) => 'Bez kontakta',
            default => 'Rezervacija #'.$record->id,
        };
    }
}
