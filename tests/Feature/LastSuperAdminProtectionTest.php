<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LastSuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_superadmin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        try {
            $admin->update(['role' => UserRole::Customer]);
            $this->fail('Poslednji superadmin je demotovan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }

        $this->assertSame(UserRole::SuperAdmin, $admin->fresh()->role);
    }

    public function test_last_superadmin_cannot_be_deleted_but_one_of_two_can(): void
    {
        $firstAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        try {
            $firstAdmin->delete();
            $this->fail('Poslednji superadmin je obrisan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user', $exception->errors());
        }

        $secondAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->assertTrue($firstAdmin->delete());
        $this->assertDatabaseHas('users', ['id' => $secondAdmin->id]);
    }
}
