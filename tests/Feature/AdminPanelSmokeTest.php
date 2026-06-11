<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_admin_pages_render_for_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $sport = Sport::query()->create([
            'name' => 'Smoke Padel',
            'slug' => 'smoke-padel',
            'is_active' => true,
        ]);
        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Smoke teren',
            'slug' => 'smoke-teren',
            'is_active' => true,
        ]);
        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'duration_minutes' => 60,
            'court_price' => 3000,
            'equipment_price' => 0,
            'total_price' => 3000,
        ]);

        $reservation->participants()->sync([$customer->id]);

        foreach ([
            '/admin',
            '/admin/reservations',
            '/admin/reservations/calendar',
            '/admin/users',
            '/admin/sports',
            '/admin/courts',
            '/admin/equipment',
            '/admin/pricing-rules',
            '/admin/membership-plans',
            '/admin/user-memberships',
            '/admin/events',
            '/admin/event-entries',
            '/admin/event-matches',
            '/admin/email-campaigns',
            '/admin/statistika',
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }
}
