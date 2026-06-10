<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\PricingRule;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function __invoke(): View
    {
        return view('price-list.index', [
            'pricingRules' => PricingRule::query()
                ->where('is_active', true)
                ->with('sport')
                ->orderBy('sport_id')
                ->orderBy('start_time')
                ->get(),
            'membershipPlans' => MembershipPlan::query()
                ->where('is_active', true)
                ->with('sport')
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ]);
    }
}
