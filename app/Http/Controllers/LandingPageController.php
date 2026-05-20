<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Sport;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'sports' => Sport::query()->where('is_active', true)->withCount(['courts', 'equipment'])->orderBy('sort_order')->get(),
            'featuredCourts' => Court::query()->where('is_active', true)->with('sport')->take(3)->get(),
            'featuredEquipment' => Equipment::query()->where('is_active', true)->take(4)->get(),
        ]);
    }
}
