<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\UserRole;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\MembershipPlan;
use App\Models\PricingRule;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        $padel = Sport::query()->updateOrCreate(
            ['name' => 'Padel'],
            [
                'slug' => 'padel',
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
                'slug' => 'basket-3x3',
                'short_description' => 'Basket 3x3 teren za brze termine, treninge i sportske dogadjaje.',
                'description' => 'Sportski centar Arena raspolaze basket 3x3 terenom dostupnim za online rezervacije.',
                'supports_online_booking' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        foreach ([
            ['sport_id' => $padel->id, 'name' => 'Padel teren 1', 'slug' => 'padel-teren-1', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'capacity' => 4, 'description' => 'Padel teren za dnevne i vecernje termine.'],
            ['sport_id' => $padel->id, 'name' => 'Padel teren 2', 'slug' => 'padel-teren-2', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'capacity' => 4, 'description' => 'Padel teren za rekreativne i timske termine.'],
            ['sport_id' => $padel->id, 'name' => 'Padel teren 3', 'slug' => 'padel-teren-3', 'location' => 'Sportski centar Arena', 'surface' => 'Sinteticka trava', 'capacity' => 4, 'description' => 'Padel teren za treninge, meceve i turnire.'],
            ['sport_id' => $basket->id, 'name' => 'Basket 3x3 teren', 'slug' => 'basket-3x3-teren', 'location' => 'Sportski centar Arena', 'surface' => 'Sportska podloga', 'capacity' => 6, 'description' => 'Basket 3x3 teren za termine, treninge i dogadjaje.'],
        ] as $court) {
            Court::query()->updateOrCreate(
                ['slug' => $court['slug']],
                [
                    'sport_id' => $court['sport_id'],
                    'name' => $court['name'],
                    'location' => $court['location'],
                    'surface' => $court['surface'],
                    'capacity' => $court['capacity'],
                    'description' => $court['description'],
                    'requires_approval' => false,
                    'is_active' => true,
                ],
            );
        }

        $this->seedPricingRules($padel, [
            ['Padel radni dan pre podne', [1, 2, 3, 4, 5], '08:00:00', '18:00:00', 3000],
            ['Padel radni dan popodne', [1, 2, 3, 4, 5], '18:00:00', '23:00:00', 3500],
            ['Padel vikend pre podne', [6, 0], '08:00:00', '18:00:00', 4000],
            ['Padel vikend popodne', [6, 0], '18:00:00', '23:00:00', 4500],
        ]);

        $this->seedPricingRules($basket, [
            ['Basket radni dan pre podne', [1, 2, 3, 4, 5], '08:00:00', '18:00:00', 2000],
            ['Basket radni dan popodne', [1, 2, 3, 4, 5], '18:00:00', '23:00:00', 2500],
            ['Basket vikend pre podne', [6, 0], '08:00:00', '18:00:00', 3000],
            ['Basket vikend popodne', [6, 0], '18:00:00', '23:00:00', 3500],
        ]);

        $this->seedEquipment($padel, $basket);
        $this->seedMembershipPlans($padel, $basket);
        $this->seedEvents();
    }

    private function seedAdmin(): void
    {
        $email = trim((string) config('arena.seed_admin.email'));
        $password = (string) config('arena.seed_admin.password');

        if ($email === '' && $password === '') {
            $this->command?->warn('SEED_ADMIN_EMAIL i SEED_ADMIN_PASSWORD nisu postavljeni; admin nalog nije kreiran.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 12) {
            throw new \RuntimeException(
                'SEED_ADMIN_EMAIL mora biti validan, a SEED_ADMIN_PASSWORD mora imati najmanje 12 karaktera.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('arena.seed_admin.name'),
                'phone' => null,
                'role' => UserRole::SuperAdmin,
                'registered_at' => now()->toDateString(),
                'email_verified_at' => now(),
                'password' => $password,
            ],
        );
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

    private function seedEquipment(Sport $padel, Sport $basket): void
    {
        foreach ([
            [
                'sport_id' => $padel->id,
                'name' => 'Padel reket',
                'sku' => 'RENT-PADEL-REKET',
                'short_description' => 'Reket za termin.',
                'description' => 'Iznajmljivanje padel reketa za jedan termin.',
                'rental_price' => 400,
                'sale_price' => 6500,
                'stock_quantity' => 12,
                'is_rentable' => true,
                'is_sellable' => true,
            ],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel loptice',
                'sku' => 'SALE-PADEL-LOPTICE',
                'short_description' => 'Pakovanje padel loptica.',
                'description' => 'Novo pakovanje loptica za padel mec.',
                'rental_price' => 0,
                'sale_price' => 900,
                'stock_quantity' => 20,
                'is_rentable' => false,
                'is_sellable' => true,
            ],
            [
                'sport_id' => $basket->id,
                'name' => 'Basket lopta',
                'sku' => 'RENT-BASKET-LOPTA',
                'short_description' => 'Lopta za basket termin.',
                'description' => 'Iznajmljivanje basket lopte za jedan termin.',
                'rental_price' => 300,
                'sale_price' => 0,
                'stock_quantity' => 6,
                'is_rentable' => true,
                'is_sellable' => false,
            ],
        ] as $item) {
            Equipment::query()->updateOrCreate(
                ['sku' => $item['sku']],
                [
                    ...$item,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedMembershipPlans(Sport $padel, Sport $basket): void
    {
        foreach ([
            [
                'sport_id' => $padel->id,
                'name' => 'Padel mesec',
                'period_label' => '30 dana',
                'duration_days' => 30,
                'reservation_limit' => 8,
                'price' => 24000,
                'short_description' => 'Mesecni paket za redovne padel termine.',
                'description' => 'Ukljucuje 8 rezervacija padel termina tokom 30 dana.',
                'sort_order' => 1,
            ],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel intenziv',
                'period_label' => '30 dana',
                'duration_days' => 30,
                'reservation_limit' => 12,
                'price' => 33000,
                'short_description' => 'Paket za igrace koji cesce rezervisu termine.',
                'description' => 'Ukljucuje 12 rezervacija padel termina tokom 30 dana.',
                'sort_order' => 2,
            ],
            [
                'sport_id' => $basket->id,
                'name' => 'Basket mesec',
                'period_label' => '30 dana',
                'duration_days' => 30,
                'reservation_limit' => 8,
                'price' => 16000,
                'short_description' => 'Mesecni paket za basket 3x3 termine.',
                'description' => 'Ukljucuje 8 rezervacija basket termina tokom 30 dana.',
                'sort_order' => 3,
            ],
        ] as $plan) {
            MembershipPlan::query()->updateOrCreate(
                ['name' => $plan['name']],
                [
                    ...$plan,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedEvents(): void
    {
        Event::query()->updateOrCreate(
            ['slug' => 'arena-padel-open'],
            [
                'title' => 'Arena Padel Open',
                'type' => EventType::Tournament,
                'status' => EventStatus::Registration,
                'location' => 'Sportski centar Arena',
                'cta_label' => 'Prijavi ekipu',
                'summary' => 'Otvoreni padel turnir za rekreativce i napredne igrace.',
                'description' => 'Arena Padel Open okuplja igrace kroz grupnu fazu i eliminacije. Broj mesta je ogranicen.',
                'rules' => 'Mecevi se igraju po pravilima organizatora. Detaljan raspored se objavljuje nakon zatvaranja prijava.',
                'start_date' => now()->addWeeks(3)->toDateString(),
                'end_date' => now()->addWeeks(3)->addDay()->toDateString(),
                'is_featured' => true,
            ],
        );
    }
}
