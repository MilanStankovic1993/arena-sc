<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Sport;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'reservations' => Auth::user()->reservations()->with(['sport', 'court', 'equipmentItems.equipment'])->latest('starts_at')->get(),
        ]);
    }

    public function store(
        StoreReservationRequest $request,
        ReservationAvailabilityService $availabilityService,
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

        $equipmentItems = $pricingService->hydrateEquipmentPricing($request->input('equipment', []));
        $courtPrice = $pricingService->calculateCourtPrice($court, $startsAt, $endsAt);
        $equipmentPrice = $pricingService->calculateEquipmentPrice($equipmentItems);
        $status = $court->requires_approval ? ReservationStatus::Pending : ReservationStatus::Approved;

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
                'players_count' => $request->integer('players_count'),
                'court_price' => $courtPrice,
                'equipment_price' => $equipmentPrice,
                'total_price' => $courtPrice + $equipmentPrice,
                'customer_note' => $request->string('customer_note')->toString(),
                'approved_at' => $status === ReservationStatus::Approved ? now() : null,
            ]);

            foreach ($equipmentItems as $item) {
                $reservation->equipmentItems()->create($item);
            }
        });

        return redirect()->route('dashboard')->with('status', 'Zahtev za rezervaciju je uspesno poslat.');
    }
}
