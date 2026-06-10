<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\ReservationAvailabilityService;
use App\Services\MembershipReservationLimitService;
use App\Services\ReservationPricingService;
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
    ): RedirectResponse {
        $court = Court::query()->with('sport')->findOrFail($request->integer('court_id'));
        $startsAt = Carbon::parse($request->string('starts_at'));
        $endsAt = $startsAt->copy()->addMinutes($request->integer('duration_minutes'));

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

        if (! $membershipLimitService->canReserve($user, $court->sport_id, $startsAt)) {
            return back()->withErrors([
                'starts_at' => $membershipLimitService->message($user, $court->sport_id, $startsAt),
            ])->withInput();
        }

        $equipmentItems = $pricingService->hydrateEquipmentPricing($request->input('equipment', []));
        try {
            $courtPrice = $pricingService->calculateCourtPrice($court, $startsAt, $endsAt);
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'starts_at' => $exception->getMessage(),
            ])->withInput();
        }

        $equipmentPrice = $pricingService->calculateEquipmentPrice($equipmentItems);
        $status = ReservationStatus::Reserved;

        DB::transaction(function () use (
            $request,
            $court,
            $startsAt,
            $endsAt,
            $equipmentItems,
            $courtPrice,
            $equipmentPrice,
            $status
        ): void {
            $reservation = Reservation::create([
                'user_id' => Auth::id(),
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
        });

        return redirect()->route('dashboard')->with('status', 'Uspesno ste rezervisali termin.');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === Auth::id(), 403);

        if ($reservation->status === ReservationStatus::Cancelled) {
            return redirect()->route('dashboard')->with('status', 'Termin je vec otkazan.');
        }

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancellation_reason' => 'Otkazano od korisnika.',
        ]);

        return redirect()->route('dashboard')->with('status', 'Uspesno ste otkazali termin.');
    }
}
