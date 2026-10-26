<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\OperationsOverview;
use App\Models\Court;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OperationsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-22 14:00:00'));
        Mail::fake();
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '']);
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]));
    }

    private function reservation(string $start, string $end, float $price, string $status = 'reserved', string $courtSlug = 'padel-teren-1'): void
    {
        $court = Court::where('slug', $courtSlug)->sole();
        DB::table('reservations')->insert([
            'sport_id' => $court->sport_id, 'court_id' => $court->id,
            'guest_name' => 'Dashboard verification', 'status' => $status,
            'starts_at' => $start, 'ends_at' => $end,
            'court_price' => $price, 'equipment_price' => 0, 'total_price' => $price,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function values(): array
    {
        $widget = new class extends OperationsOverview
        {
            public function values(): array
            {
                return collect($this->getStats())->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()])->all();
            }
        };

        return $widget->values();
    }

    public function test_revenue_is_split_by_end_time_with_exact_boundaries_and_no_cancelled_or_promo_revenue(): void
    {
        $this->reservation('2026-10-22 12:00:00', '2026-10-22 13:00:00', 1000);
        $this->reservation('2026-10-22 13:00:00', '2026-10-22 14:00:00', 2000);
        $this->reservation('2026-10-22 14:00:00', '2026-10-22 15:00:00', 3000);
        $this->reservation('2026-10-23 14:00:00', '2026-10-23 15:00:00', 4000);
        $this->reservation('2026-10-22 13:30:00', '2026-10-22 14:30:00', 1500, courtSlug: 'padel-teren-2');
        foreach (['2026-10-16', '2026-10-17', '2026-10-18'] as $date) {
            $this->reservation($date.' 10:00:00', $date.' 11:00:00', 0);
        }
        $this->reservation('2026-10-22 13:30:00', '2026-10-22 14:30:00', 0);
        $this->reservation('2027-01-01 10:00:00', '2027-01-01 11:00:00', 0);
        foreach ([['12:00', '13:00'], ['13:30', '14:30'], ['15:00', '16:00']] as [$start, $end]) {
            $this->reservation('2026-10-22 '.$start.':00', '2026-10-22 '.$end.':00', 99000, 'cancelled');
        }
        $values = $this->values();
        $this->assertSame('2', $values['Odigrani termini']);
        $this->assertSame('2', $values['Budući termini']);
        $this->assertSame('1', $values['Termini u toku']);
        $this->assertSame('3.000 RSD', $values['Prihod od odigranih termina']);
        $this->assertSame('7.000 RSD', $values['Vrednost budućih rezervacija']);
        $this->assertSame('1.500 RSD', $values['Vrednost termina u toku']);
        Livewire::test(OperationsOverview::class)
            ->assertSee('Odigrani termini')->assertSee('Budući termini')->assertSee('Termini u toku')
            ->assertSeeHtml('wire:poll.5s')
            ->assertSee('Prihod od odigranih termina')->assertSee('3.000 RSD')
            ->assertSee('Vrednost budućih rezervacija')->assertSee('7.000 RSD')
            ->assertSee('Vrednost termina u toku')->assertSee('1.500 RSD')
            ->assertDontSee('Prihod od termina')->assertDontSee('Ukupne rezervacije');
    }

    public function test_value_moves_from_future_to_ongoing_to_completed_as_time_passes(): void
    {
        $this->reservation('2026-10-22 14:00:00', '2026-10-22 15:00:00', 5000);
        $this->assertSame('5.000 RSD', $this->values()['Vrednost budućih rezervacija']);
        $this->assertSame('0 RSD', $this->values()['Prihod od odigranih termina']);
        $this->assertSame('1', $this->values()['Budući termini']);
        $this->assertSame('0', $this->values()['Odigrani termini']);
        $this->travelTo(Carbon::parse('2026-10-22 14:30:00'));
        $this->assertSame('5.000 RSD', $this->values()['Vrednost termina u toku']);
        $this->assertSame('1', $this->values()['Termini u toku']);
        $this->assertSame('0', $this->values()['Budući termini']);
        $this->assertSame('0 RSD', $this->values()['Prihod od odigranih termina']);
        $this->travelTo(Carbon::parse('2026-10-22 15:00:00'));
        $this->assertSame('5.000 RSD', $this->values()['Prihod od odigranih termina']);
        $this->assertSame('1', $this->values()['Odigrani termini']);
        $this->assertSame('0', $this->values()['Termini u toku']);
        $this->assertSame('0 RSD', $this->values()['Vrednost budućih rezervacija']);
        $this->assertSame('0 RSD', $this->values()['Vrednost termina u toku']);
    }

    public function test_empty_dashboard_shows_zero_for_all_revenue_categories(): void
    {
        $values = $this->values();
        $this->assertSame('0', $values['Odigrani termini']);
        $this->assertSame('0', $values['Budući termini']);
        $this->assertSame('0', $values['Termini u toku']);
        $this->assertSame('0 RSD', $values['Prihod od odigranih termina']);
        $this->assertSame('0 RSD', $values['Vrednost budućih rezervacija']);
        $this->assertSame('0 RSD', $values['Vrednost termina u toku']);
    }

    public function test_live_widget_refresh_reclassifies_a_finished_reservation_without_cron(): void
    {
        $this->reservation('2026-10-22 13:30:00', '2026-10-22 14:30:00', 5000);
        $widget = Livewire::test(OperationsOverview::class);
        $this->assertSame('1', $this->values()['Termini u toku']);
        $widget->assertSee('5.000 RSD')->assertSeeHtml('wire:poll.5s');
        $this->travelTo(Carbon::parse('2026-10-22 14:30:00'));
        $widget->call('$refresh');
        $this->assertSame('1', $this->values()['Odigrani termini']);
        $this->assertSame('0', $this->values()['Termini u toku']);
        $stats = (new \ReflectionMethod(OperationsOverview::class, 'getCachedStats'))->invoke($widget->instance());
        $rendered = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()])->all();
        $this->assertSame('5.000 RSD', $rendered['Prihod od odigranih termina']);
        $this->assertSame('0 RSD', $rendered['Vrednost termina u toku']);
        $this->assertSame('1', $rendered['Odigrani termini']);
        $this->assertSame('0', $rendered['Termini u toku']);
    }
}
