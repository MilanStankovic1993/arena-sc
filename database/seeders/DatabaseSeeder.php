<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\PricingRule;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'milan.stankovic@radijator.rs'],
            [
                'name' => 'Milan Stankovic',
                'phone' => null,
                'role' => UserRole::SuperAdmin,
                'registered_at' => now()->toDateString(),
                'email_verified_at' => now(),
                'password' => '28Januar',
            ],
        );

        $padel = Sport::query()->updateOrCreate(
            ['name' => 'Padel'],
            [
                'short_description' => 'Premium padel tereni za rekreativce, timove i turnire.',
                'description' => 'Sportski centar Arena raspolaze sa tri padel terena dostupna za online rezervacije.',
                'supports_online_booking' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $basket = Sport::query()->updateOrCreate(
            ['name' => 'Basket 3x3'],
            [
                'short_description' => 'Basket 3x3 teren za brze termine, treninge i sportske dogadjaje.',
                'description' => 'Sportski centar Arena raspolaze basket 3x3 terenom dostupnim za online rezervacije.',
                'supports_online_booking' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        foreach ([
            ['sport_id' => $padel->id, 'name' => 'Padel teren 1', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'description' => 'Padel teren za dnevne i vecernje termine.'],
            ['sport_id' => $padel->id, 'name' => 'Padel teren 2', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'description' => 'Padel teren za rekreativne i timske termine.'],
            ['sport_id' => $padel->id, 'name' => 'Padel teren 3', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'description' => 'Padel teren za treninge, meceve i turnire.'],
            ['sport_id' => $basket->id, 'name' => 'Basket 3x3 teren', 'location' => 'Sportski centar Arena', 'surface' => 'Sportska podloga', 'description' => 'Basket 3x3 teren za termine, treninge i dogadjaje.'],
        ] as $court) {
            Court::query()->updateOrCreate(
                ['name' => $court['name']],
                [
                    'sport_id' => $court['sport_id'],
                    'location' => $court['location'],
                    'surface' => $court['surface'],
                    'capacity' => null,
                    'description' => $court['description'],
                    'requires_approval' => false,
                    'is_active' => true,
                ],
            );
        }

        $this->seedPricingRules($padel, [
            ['Padel radni dan pre podne', [1, 2, 3, 4, 5], '08:00:00', '18:00:00', 3000],
            ['Padel radni dan popodne', [1, 2, 3, 4, 5], '18:00:00', '23:30:00', 3500],
            ['Padel vikend pre podne', [6, 0], '08:00:00', '18:00:00', 4000],
            ['Padel vikend popodne', [6, 0], '18:00:00', '23:30:00', 4500],
        ]);

        $this->seedPricingRules($basket, [
            ['Basket radni dan pre podne', [1, 2, 3, 4, 5], '08:00:00', '18:00:00', 2000],
            ['Basket radni dan popodne', [1, 2, 3, 4, 5], '18:00:00', '23:30:00', 2500],
            ['Basket vikend pre podne', [6, 0], '08:00:00', '18:00:00', 3000],
            ['Basket vikend popodne', [6, 0], '18:00:00', '23:30:00', 3500],
        ]);
    }

    private function seedPricingRules(Sport $sport, array $rules): void
    {
        foreach ($rules as [$name, $days, $startTime, $endTime, $price]) {
            PricingRule::query()->updateOrCreate(
                [
                    'sport_id' => $sport->id,
                    'name' => $name,
                ],
                [
                    'days_of_week' => $days,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price_60' => $price,
                    'price_90' => $price * 1.5,
                    'price_120' => $price * 2,
                    'valid_from' => null,
                    'valid_to' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
