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
                ->with([
                    'courts' => fn ($query) => $query->where('is_active', true),
                    'equipment' => fn ($query) => $query->where('is_active', true),
                ])
                ->withCount([
                    'pricingRules' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
