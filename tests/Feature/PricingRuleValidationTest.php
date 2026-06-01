<?php

namespace Tests\Feature;

use App\Models\PricingRule;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PricingRuleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_overlapping_pricing_rules_for_the_same_sport_and_day_range(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Test sport',
            'description' => 'Test sport description',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Pravilo 1',
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'price_60' => 2000,
            'price_90' => 3000,
            'price_120' => 4000,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Vec postoji definisano pravilo za ovaj period');

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Pravilo 2',
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '15:00:00',
            'end_time' => '18:00:00',
            'price_60' => 2500,
            'price_90' => 3500,
            'price_120' => 4500,
            'is_active' => true,
        ]);
    }
}
