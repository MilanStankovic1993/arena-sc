<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(): View
    {
        return view('equipment.index', [
            'equipment' => Equipment::query()
                ->where('is_active', true)
                ->with('sport')
                ->orderByDesc('is_sellable')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
