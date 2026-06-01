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
            'sports' => Sport::query()->where('is_active', true)->withCount(['courts', 'equipment', 'pricingRules'])->orderBy('sort_order')->get(),
            'featuredCourts' => Court::query()->where('is_active', true)->with('sport')->take(3)->get(),
            'featuredEquipment' => Equipment::query()->where('is_active', true)->take(4)->get(),
            'featuredEvents' => Event::query()->where('is_featured', true)->orWhereIn('status', ['registration', 'ongoing'])->orderBy('start_date')->take(3)->get(),
        ]);
    }
}
