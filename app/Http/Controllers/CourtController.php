<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function show(Court $court): View
    {
        $court->load('sport');
        abort_unless($court->is_active && $court->sport?->is_active, 404);

        $pricingRules = $court->sport->pricingRules()
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        return view('courts.show', [
            'court' => $court,
            'pricingRules' => $pricingRules,
        ]);
    }
}
