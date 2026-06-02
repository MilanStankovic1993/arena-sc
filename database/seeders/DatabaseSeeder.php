<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\UserRole;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\EventEntry;
use App\Models\EventMatch;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@arena-sc.test'],
            [
                'name' => 'Arena Super Admin',
                'phone' => '+38160111222',
                'role' => UserRole::SuperAdmin,
                'registered_at' => now()->toDateString(),
                'password' => 'password',
            ],
        );

        $customers = collect([
            [
                'name' => 'Marko Petrovic',
                'email' => 'marko@arena-sc.test',
                'phone' => '+38160111001',
            ],
            [
                'name' => 'Nikola Jovanovic',
                'email' => 'nikola@arena-sc.test',
                'phone' => '+38160111002',
            ],
            [
                'name' => 'Jelena Ilic',
                'email' => 'jelena@arena-sc.test',
                'phone' => '+38160111003',
            ],
        ])->map(function (array $customer): User {
            return User::query()->updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'role' => UserRole::Customer,
                    'registered_at' => now()->subDays(30)->toDateString(),
                    'password' => 'password',
                ],
            );
        });

        $padel = Sport::query()->updateOrCreate(
            ['slug' => 'padel'],
            [
                'name' => 'Padel',
                'short_description' => 'Rezervacija padel terena i opreme.',
                'description' => 'Moderni padel tereni sa reflektorima i dodatnom opremom za rekreativce i timove.',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $basket = Sport::query()->updateOrCreate(
            ['slug' => 'kosarka-3x3'],
            [
                'name' => 'Kosarka 3x3',
                'short_description' => 'Brzi termini za basket 3x3.',
                'description' => 'Poluotvoreni teren za basket 3x3 sa LED rasvetom i sistemom online prijave.',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $padelOne = Court::query()->updateOrCreate(
            ['slug' => 'padel-teren-1'],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel teren 1',
                'location' => 'Hala A',
                'surface' => 'Sinteticka trava',
                'capacity' => 4,
                'description' => 'Profesionalni padel teren sa rasvetom.',
                'base_price' => 2800,
                'requires_approval' => true,
                'is_active' => true,
            ],
        );

        $padelTwo = Court::query()->updateOrCreate(
            ['slug' => 'padel-teren-2'],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel teren 2',
                'location' => 'Hala A',
                'surface' => 'Sinteticka trava',
                'capacity' => 4,
                'description' => 'Drugi teren za vecernje i vikend termine.',
                'base_price' => 3000,
                'requires_approval' => true,
                'is_active' => true,
            ],
        );

        $basketCourt = Court::query()->updateOrCreate(
            ['slug' => '3x3-arena-court'],
            [
                'sport_id' => $basket->id,
                'name' => '3x3 Arena Court',
                'location' => 'Open zone',
                'surface' => 'Akrilna podloga',
                'capacity' => 6,
                'description' => 'Teren prilagodjen turnirima i trening terminima.',
                'base_price' => 2200,
                'requires_approval' => true,
                'is_active' => true,
            ],
        );

        PricingRule::query()->updateOrCreate(
            [
                'sport_id' => $padel->id,
                'name' => 'Padel dnevni blok',
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
            ],
            [
                'days_of_week' => [1, 2, 3, 4, 5],
                'price_60' => 2800,
                'price_90' => 4000,
                'price_120' => 5200,
                'is_active' => true,
            ],
        );

        PricingRule::query()->updateOrCreate(
            [
                'sport_id' => $padel->id,
                'name' => 'Padel vecernji blok',
                'start_time' => '18:00:00',
                'end_time' => '23:00:00',
            ],
            [
                'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
                'price_60' => 3300,
                'price_90' => 4700,
                'price_120' => 6100,
                'is_active' => true,
            ],
        );

        PricingRule::query()->updateOrCreate(
            [
                'sport_id' => $basket->id,
                'name' => 'Basket dnevni blok',
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
            ],
            [
                'days_of_week' => [1, 2, 3, 4, 5],
                'price_60' => 2200,
                'price_90' => 3200,
                'price_120' => 4200,
                'is_active' => true,
            ],
        );

        PricingRule::query()->updateOrCreate(
            [
                'sport_id' => $basket->id,
                'name' => 'Basket vecernji blok',
                'start_time' => '18:00:00',
                'end_time' => '23:00:00',
            ],
            [
                'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
                'price_60' => 2500,
                'price_90' => 3600,
                'price_120' => 4700,
                'is_active' => true,
            ],
        );

        $racket = Equipment::query()->updateOrCreate(
            ['slug' => 'padel-reket'],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel reket',
                'sku' => 'PAD-001',
                'short_description' => 'Iznajmljivanje reketa po terminu.',
                'rental_price' => 400,
                'sale_price' => 6500,
                'stock_quantity' => 12,
                'is_rentable' => true,
                'is_sellable' => true,
                'is_active' => true,
            ],
        );

        Equipment::query()->updateOrCreate(
            ['slug' => 'padel-loptice'],
            [
                'sport_id' => $padel->id,
                'name' => 'Padel loptice',
                'sku' => 'PAD-002',
                'short_description' => 'Set od tri loptice.',
                'rental_price' => 0,
                'sale_price' => 900,
                'stock_quantity' => 30,
                'is_rentable' => false,
                'is_sellable' => true,
                'is_active' => true,
            ],
        );

        CourtClosure::query()->updateOrCreate(
            [
                'court_id' => $padelTwo->id,
                'title' => 'Servis terena',
            ],
            [
                'reason' => 'Redovno odrzavanje podloge i mreze.',
                'starts_at' => now()->addDays(2)->setTime(14, 0),
                'ends_at' => now()->addDays(2)->setTime(18, 0),
                'is_active' => true,
            ],
        );

        $startsAt = Carbon::now()->addDay()->setTime(19, 0);

        $reservation = Reservation::query()->updateOrCreate(
            [
                'user_id' => $customers->first()->id,
                'court_id' => $padelOne->id,
                'starts_at' => $startsAt,
            ],
            [
                'sport_id' => $padel->id,
                'status' => 'reserved',
                'ends_at' => (clone $startsAt)->addHour(),
                'duration_minutes' => 60,
                'players_count' => 4,
                'court_price' => 3300,
                'equipment_price' => 800,
                'total_price' => 4100,
                'customer_note' => 'Treba nam i dva reketa.',
            ],
        );

        $reservation->equipmentItems()->updateOrCreate(
            ['equipment_id' => $racket->id],
            [
                'quantity' => 2,
                'unit_price' => 400,
                'line_total' => 800,
            ],
        );

        $league = Event::query()->updateOrCreate(
            ['slug' => 'padel-prolecna-liga'],
            [
                'title' => 'Padel prolecna liga',
                'type' => EventType::League,
                'status' => EventStatus::Ongoing,
                'location' => 'Arena SC / Padel zona',
                'cta_label' => 'Prijavi tim',
                'summary' => 'Ligaški format sa tabelom, rezultatima i punim pregledom učesnika.',
                'description' => 'Početna demo verzija događaja za prikaz lige i buduće statistike.',
                'rules' => 'Timovi igraju svako sa svakim. Pobeda donosi 3 poena.',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
                'is_featured' => true,
            ],
        );

        $pairOne = EventEntry::query()->updateOrCreate(
            ['event_id' => $league->id, 'team_name' => 'Arena Smash'],
            [
                'contact_name' => 'Marko Petrovic',
                'contact_phone' => '+38160111001',
                'played' => 3,
                'wins' => 3,
                'losses' => 0,
                'points' => 9,
                'score_for' => 18,
                'score_against' => 8,
            ],
        );

        $pairTwo = EventEntry::query()->updateOrCreate(
            ['event_id' => $league->id, 'team_name' => 'Blue Court Duo'],
            [
                'contact_name' => 'Nikola Jovanovic',
                'contact_phone' => '+38160111002',
                'played' => 3,
                'wins' => 2,
                'losses' => 1,
                'points' => 6,
                'score_for' => 15,
                'score_against' => 10,
            ],
        );

        EventMatch::query()->updateOrCreate(
            [
                'event_id' => $league->id,
                'round_label' => '1. kolo',
                'home_entry_id' => $pairOne->id,
                'away_entry_id' => $pairTwo->id,
            ],
            [
                'scheduled_at' => now()->addDays(5)->setTime(19, 0),
                'status' => 'finished',
                'home_score' => 2,
                'away_score' => 1,
                'notes' => 'Demo rezultat za prikaz na sajtu i u adminu.',
            ],
        );
    }
}
