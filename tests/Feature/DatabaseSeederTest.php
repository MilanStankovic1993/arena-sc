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
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Sport::query()->count());
        $this->assertSame(4, Court::query()->count());
        $this->assertSame(8, PricingRule::query()->count());
        $this->assertSame(30, Equipment::query()->count());
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
        $this->assertDatabaseHas('equipment', [
            'sku' => 'PAT10PCH26',
            'rental_price' => 400,
            'sale_price' => 0,
            'stock_quantity' => 10,
            'is_rentable' => true,
            'is_sellable' => false,
        ]);
        $this->assertDatabaseHas('equipment', [
            'sku' => 'CAL26LUXWHGR',
            'sale_price' => 17400,
            'stock_quantity' => 10,
            'is_rentable' => false,
            'is_sellable' => true,
        ]);
        Storage::disk('public')->assertExists('equipment/nox/pat10pch26.png');
        Storage::disk('public')->assertExists('equipment/nox/cal26luxwhgr.jpg');
        $this->assertDatabaseHas('equipment', ['sku' => 'CAHMCNLVBLBAG', 'image' => 'equipment/nox/CAHMCNLVBLBAG.webp']);
        Storage::disk('public')->assertExists('equipment/nox/PRTNXNEBLBAG.webp');
        $this->assertDatabaseHas('membership_plans', ['slug' => 'padel-mesec']);
        $this->assertDatabaseHas('events', ['slug' => 'arena-padel-open']);
    }
}
