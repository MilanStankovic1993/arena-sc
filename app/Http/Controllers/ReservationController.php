<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\MembershipReservationLimitService;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationPricingService;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ReservationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('dashboard', [
            'reservations' => $user->participatedReservations()->with(['user', 'sport', 'court', 'equipmentItems.equipment'])->latest('starts_at')->get(),
            'memberships' => $user->memberships()
                ->with('membershipPlan.sport')
                ->where('is_active', true)
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->orderByDesc('ends_at')
                ->get(),
        ]);
    }

    public function store(
        StoreReservationRequest $request,
        ReservationAvailabilityService $availabilityService,
        MembershipReservationLimitService $membershipLimitService,
        ReservationPricingService $pricingService,
        ReservationScheduleService $scheduleService,
    ): RedirectResponse {
        $court = Court::query()->with('sport')->findOrFail($request->integer('court_id'));
        $startsAt = Carbon::parse($request->string('starts_at'));
        $endsAt = $startsAt->copy()->addMinutes($request->integer('duration_minutes'));

        if (! $court->is_active || ! $court->sport?->is_active) {
            return back()->withErrors([
                'court_id' => 'Izabrani teren vise nije dostupan.',
            ])->withInput();
        }

        if (! $scheduleService->isWithinOperatingHours($startsAt, $endsAt)) {
            return back()->withErrors([
                'starts_at' => 'Termin mora biti u okviru radnog vremena od 08:00 do 23:00.',
            ])->withInput();
        }

        if (! $availabilityService->isAvailable($court, $startsAt, $endsAt)) {
            return back()->withErrors([
                'starts_at' => 'Izabrani termin nije dostupan za ovaj teren.',
            ])->withInput();
        }

        if (! $court->sport?->supports_online_booking) {
            return redirect()
                ->route('booking.index', ['sport' => $court->sport->slug])
                ->with('status', 'Za izabrani sport online rezervacija nije dostupna. Posaljite upit i kontaktiracemo vas.');
        }

        $user = Auth::user();

        if ($user && ! $membershipLimitService->canReserve($user, $court->sport_id, $startsAt)) {
            return back()->withErrors([
                'starts_at' => $membershipLimitService->message($user, $court->sport_id, $startsAt),
            ])->withInput();
        }

        $status = ReservationStatus::Reserved;

        $result = DB::transaction(function () use (
            $request,
            $court,
            $startsAt,
            $endsAt,
            $user,
            $status,
            $availabilityService,
            $membershipLimitService,
            $pricingService,
        ): Reservation|array {
            if ($user) {
                $user = $user->newQuery()->lockForUpdate()->findOrFail($user->getKey());
            }

            $court = Court::query()
                ->with('sport')
                ->lockForUpdate()
                ->findOrFail($court->getKey());

            if (! $court->is_active || ! $court->sport?->is_active) {
                return ['error' => 'Izabrani teren vise nije dostupan.'];
            }

            if (! $court->sport?->supports_online_booking) {
                return ['contact_only' => true];
            }

            if (! $availabilityService->isAvailable($court, $startsAt, $endsAt)) {
                return ['error' => 'Izabrani termin nije dostupan za ovaj teren.'];
            }

            if ($user && ! $membershipLimitService->canReserve($user, $court->sport_id, $startsAt)) {
                return ['error' => $membershipLimitService->message($user, $court->sport_id, $startsAt)];
            }

            try {
                $equipmentItems = $pricingService->hydrateEquipmentPricing(
                    $request->input('equipment', []),
                    $court->sport_id,
                    $startsAt,
                    $endsAt,
                );
                $courtPrice = $pricingService->calculateCourtPrice($court, $startsAt, $endsAt);
            } catch (RuntimeException $exception) {
                return ['error' => $exception->getMessage()];
            }

            $equipmentPrice = $pricingService->calculateEquipmentPrice($equipmentItems);

            $reservation = Reservation::create([
                'user_id' => $user?->getKey(),
                'guest_name' => $user ? null : $request->string('guest_name')->toString(),
                'guest_phone' => $user ? null : $request->string('guest_phone')->toString(),
                'guest_email' => $user ? null : $request->string('guest_email')->toString(),
                'sport_id' => $court->sport_id,
                'court_id' => $court->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $request->integer('duration_minutes'),
                'court_price' => $courtPrice,
                'equipment_price' => $equipmentPrice,
                'total_price' => $courtPrice + $equipmentPrice,
                'customer_note' => $request->string('customer_note')->toString(),
            ]);

            foreach ($equipmentItems as $item) {
                $reservation->equipmentItems()->create($item);
            }

            return $reservation;
        }, attempts: 3);

        if (is_array($result)) {
            if ($result['contact_only'] ?? false) {
                return redirect()
                    ->route('booking.index', ['sport' => $court->sport->slug])
                    ->with('status', 'Za izabrani sport online rezervacija nije dostupna. Posaljite upit i kontaktiracemo vas.');
            }

            return back()->withErrors([
                'starts_at' => $result['error'],
            ])->withInput();
        }

        if (Auth::check()) {
            return redirect()->route('dashboard')->with('status', 'Uspesno ste rezervisali termin.');
        }

        return redirect()
            ->route('booking.index', ['sport' => $court->sport->slug])
            ->with('status', 'Uspesno ste rezervisali termin. Kontaktiracemo vas ukoliko bude potrebna potvrda.');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === Auth::id(), 403);

        if ($reservation->status === ReservationStatus::Cancelled) {
            return redirect()->route('dashboard')->with('status', 'Termin je vec otkazan.');
        }

        if (! $reservation->starts_at->isFuture()) {
            return redirect()->route('dashboard')->withErrors([
                'reservation' => 'Prosli termin nije moguce otkazati.',
            ]);
        }

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancellation_reason' => 'Otkazano od korisnika.',
        ]);

        return redirect()->route('dashboard')->with('status', 'Uspesno ste otkazali termin.');
    }
}
