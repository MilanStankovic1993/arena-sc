<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\View\View;

class SportController extends Controller
{
    public function index(): View
    {
        return view('sports.index', [
            'sports' => Sport::query()
                ->where('is_active', true)
                ->with(['courts', 'equipment'])
                ->withCount('pricingRules')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
