<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\MembershipPlan;
use App\Models\PricingRule;
use App\Models\Sport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_public_catalog_data_idempotently(): void
    {
        config([
            'arena.seed_admin.email' => '',
            'arena.seed_admin.password' => '',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Sport::query()->count());
        $this->assertSame(4, Court::query()->count());
        $this->assertSame(8, PricingRule::query()->count());
        $this->assertSame(3, Equipment::query()->count());
        $this->assertSame(3, MembershipPlan::query()->count());
        $this->assertSame(1, Event::query()->count());

        $this->assertDatabaseHas('sports', [
            'slug' => 'padel',
            'supports_online_booking' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('courts', [
            'slug' => 'padel-teren-1',
            'capacity' => 4,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('equipment', ['sku' => 'RENT-PADEL-REKET']);
        $this->assertDatabaseHas('membership_plans', ['slug' => 'padel-mesec']);
        $this->assertDatabaseHas('events', ['slug' => 'arena-padel-open']);
    }
}
