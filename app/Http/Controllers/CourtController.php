<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Equipment;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function show(Court $court): View
    {
        $court->load(['sport', 'pricingRules' => fn ($query) => $query->where('is_active', true)->orderBy('start_time')]);

        return view('courts.show', [
            'court' => $court,
            'equipment' => Equipment::query()
                ->where('is_active', true)
                ->where('is_rentable', true)
                ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $court->sport_id))
                ->get(),
        ]);
    }
}
