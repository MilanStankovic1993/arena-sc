<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'sports' => Sport::query()
                ->where('is_active', true)
                ->withCount([
                    'courts' => fn ($query) => $query->where('is_active', true),
                    'equipment' => fn ($query) => $query->where('is_active', true),
                    'pricingRules' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderBy('sort_order')
                ->get(),
            'featuredCourts' => Court::query()
                ->where('is_active', true)
                ->whereHas('sport', fn ($query) => $query->where('is_active', true))
                ->with('sport')
                ->take(3)
                ->get(),
            'featuredEquipment' => Equipment::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->whereNull('sport_id')
                    ->orWhereHas('sport', fn ($sportQuery) => $sportQuery->where('is_active', true)))
                ->take(4)
                ->get(),
            'featuredEvents' => Event::query()
                ->whereIn('status', ['registration', 'ongoing', 'completed'])
                ->where(fn ($query) => $query
                    ->where('is_featured', true)
                    ->orWhereIn('status', ['registration', 'ongoing']))
                ->orderBy('start_date')
                ->take(3)
                ->get(),
        ]);
    }
}
