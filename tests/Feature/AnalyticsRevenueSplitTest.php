<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\AnalyticsCourtPerformanceTable;
use App\Filament\Widgets\AnalyticsSummaryWidget;
use App\Filament\Widgets\AnalyticsTopCustomersTable;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Filament\Widgets\OperationsOverview;
use App\Filament\Widgets\ReservationTrendsChart;
use App\Filament\Widgets\TopCustomersTable;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsRevenueSplitTest extends TestCase
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
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
    }

    private function booking(string $start, string $end, float $price, string $status = 'reserved', string $slug = 'padel-teren-1', ?User $user = null): void
    {
        $court = Court::where('slug', $slug)->sole();
        $id = DB::table('reservations')->insertGetId([
            'sport_id' => $court->sport_id, 'court_id' => $court->id,
            'guest_name' => 'Analytics test', 'status' => $status,
            'starts_at' => $start, 'ends_at' => $end,
            'court_price' => $price, 'equipment_price' => 0, 'total_price' => $price,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($user) {
            DB::table('reservation_participants')->insert(['reservation_id' => $id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_summary_chart_and_courts_agree_and_exclude_cancelled_and_zero_price_promo_revenue(): void
    {
        $this->booking('2026-10-16 10:00:00', '2026-10-16 11:00:00', 0);
        $this->booking('2026-10-22 13:00:00', '2026-10-22 14:00:00', 2000);
        $this->booking('2026-10-22 14:00:00', '2026-10-22 15:00:00', 3000);
        $this->booking('2026-10-22 13:30:00', '2026-10-22 14:30:00', 1500, slug: 'padel-teren-2');
        $this->booking('2027-01-01 10:00:00', '2027-01-01 11:00:00', 6000);
        $this->booking('2026-10-22 10:00:00', '2026-10-22 11:00:00', 99000, 'cancelled');
        $service = app(AdminAnalyticsService::class);
        $all = ['preset' => 'all'];
        $period = $service->periodSnapshot($all);
        $this->assertSame(2000.0, $period['revenue']);
        $this->assertSame(9000.0, $period['futureValue']);
        $this->assertSame(1500.0, $period['ongoingValue']);
        $this->assertSame(1, $period['completed']);
        $this->assertSame(2000.0, $period['averagePrice']);
        $this->assertSame(3000.0, $service->periodSnapshot()['futureValue']);
        $this->assertSame(3000.0, $service->todaySnapshot()['futureValue']);
        $series = $service->monthlyRevenue($all);
        $courts = $service->courtPerformance($all);
        foreach (['data' => 'revenue', 'futureData' => 'futureValue', 'ongoingData' => 'ongoingValue'] as $seriesKey => $key) {
            $this->assertEquals($period[$key], array_sum($series[$seriesKey]));
            $this->assertEquals($period[$key], $courts->sum($key));
        }
        Livewire::test(AnalyticsSummaryWidget::class, ['pageFilters' => $all])
            ->assertSee('Prihod od odigranih termina')->assertSee('Vrednost budućih rezervacija')
            ->assertSee('9.000 RSD')->assertSeeHtml('wire:poll.5s')
            ->assertDontSee('Ucesnickih termina')
            ->assertSeeHtml('analytics-card-grid--reservations')
            ->assertDontSeeHtml('style="grid-template-columns: repeat(3, minmax(0, 1fr));"');
        $court = Livewire::test(AnalyticsCourtPerformanceTable::class, ['pageFilters' => $all])->instance()->getTableRecords()->firstWhere('slug', 'padel-teren-1');
        $this->assertEquals(2000, $court->filtered_revenue);
        $this->assertEquals(9000, $court->filtered_future_value);
        $this->assertEquals(1, $court->filtered_completed_count);
    }

    public function test_filters_and_customer_table_keep_the_same_split(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->booking('2026-10-22 12:00:00', '2026-10-22 13:00:00', 2000, user: $user);
        $this->booking('2026-10-23 12:00:00', '2026-10-23 13:00:00', 3000, user: $user);
        $this->booking('2026-10-22 13:30:00', '2026-10-22 14:30:00', 1500, user: $user);
        $this->booking('2026-10-22 15:00:00', '2026-10-22 16:00:00', 99000, 'cancelled', user: $user);
        $this->booking('2026-10-22 12:00:00', '2026-10-22 13:00:00', 4000, slug: 'padel-teren-2');
        $court = Court::where('slug', 'padel-teren-1')->sole();
        $filters = ['preset' => 'custom', 'startDate' => '2026-10-22', 'endDate' => '2026-10-23', 'userId' => $user->id, 'courtId' => $court->id, 'sportId' => $court->sport_id];
        $service = app(AdminAnalyticsService::class);
        $period = $service->periodSnapshot($filters);
        $this->assertSame(2000.0, $period['revenue']);
        $this->assertSame(3000.0, $period['futureValue']);
        $this->assertSame(1500.0, $period['ongoingValue']);
        $this->assertEquals(2000, array_sum($service->monthlyRevenue($filters)['data']));
        $record = Livewire::test(AnalyticsTopCustomersTable::class, ['pageFilters' => $filters])->instance()->getTableRecords()->first();
        $this->assertEquals(2000, $record->filtered_revenue);
        $this->assertEquals(3000, $record->filtered_future_value);
        $this->assertEquals(1500, $record->filtered_ongoing_value);
        $this->assertEquals(1, $record->filtered_completed_count);
    }

    public function test_time_passing_reclassifies_values_on_refresh_without_cron(): void
    {
        $this->booking('2026-10-22 14:00:00', '2026-10-22 15:00:00', 5000);
        $service = app(AdminAnalyticsService::class);
        $widget = Livewire::test(AnalyticsSummaryWidget::class);
        $this->assertSame(5000.0, $service->periodSnapshot()['futureValue']);
        $this->travelTo(Carbon::parse('2026-10-22 14:30:00'));
        $widget->call('$refresh')->assertSee('5.000 RSD');
        $this->assertSame(5000.0, $service->periodSnapshot()['ongoingValue']);
        $this->assertSame(0.0, $service->periodSnapshot()['revenue']);
        $this->travelTo(Carbon::parse('2026-10-22 15:00:00'));
        $widget->call('$refresh')->assertSee('5.000 RSD');
        $this->assertSame(5000.0, $service->periodSnapshot()['revenue']);
        $this->assertSame(0.0, $service->periodSnapshot()['ongoingValue']);
    }

    public function test_empty_statistics_show_zero_instead_of_null(): void
    {
        $service = app(AdminAnalyticsService::class);
        foreach (['revenue', 'futureValue', 'ongoingValue'] as $key) {
            $this->assertSame(0.0, $service->periodSnapshot(['preset' => 'all'])[$key]);
        }
        $this->assertSame([0.0], $service->monthlyRevenue()['data']);
    }

    public function test_analytics_tables_explain_horizontal_scrolling_and_use_a_bounded_container(): void
    {
        foreach ([AnalyticsCourtPerformanceTable::class, AnalyticsTopCustomersTable::class] as $widget) {
            Livewire::test($widget, ['pageFilters' => ['preset' => 'all']])
                ->assertSee('Prevucite tabelu levo/desno da vidite sve kolone.')
                ->assertSeeHtml('analytics-responsive-table');
        }
    }

    public function test_cancelling_from_calendar_reduces_future_counts_and_values_after_both_widgets_refresh(): void
    {
        $this->booking('2026-10-22 12:00:00', '2026-10-22 13:00:00', 2000);
        $this->booking('2026-10-23 12:00:00', '2026-10-23 13:00:00', 6000);
        $this->booking('2026-10-24 12:00:00', '2026-10-24 13:00:00', 3000);
        $record = Reservation::where('starts_at', '2026-10-23 12:00:00')->sole();
        $filters = ['preset' => 'all'];
        $service = app(AdminAnalyticsService::class);
        $dashboard = Livewire::test(OperationsOverview::class)->assertSee('9.000 RSD')->assertSeeHtml('wire:poll.5s');
        $statistics = Livewire::test(AnalyticsSummaryWidget::class, ['pageFilters' => $filters])
            ->assertSee('9.000 RSD')->assertSee('2 budućih termina')->assertSeeHtml('wire:poll.5s');
        $this->assertSame(3, $service->periodSnapshot($filters)['total']);
        $start = $record->starts_at->toIso8601String();
        $end = $record->ends_at->toIso8601String();
        Livewire::test(CalendarReservationsWidget::class)->call('onEventClickJs', [
            'tzOffset' => 0,
            'event' => [
                'title' => $record->guest_name, 'start' => $start, 'end' => $end, 'allDay' => false,
                'styles' => [], 'classNames' => [], 'resourceIds' => [], 'display' => 'auto',
                'extendedProps' => ['model' => Reservation::class, 'key' => $record->id],
            ],
            'view' => ['type' => 'listWeek', 'title' => 'Test', 'currentStart' => $start,
                'currentEnd' => $end, 'activeStart' => $start, 'activeEnd' => $end],
        ])->callAction('cancelReservation');
        $this->assertDatabaseHas('reservations', ['id' => $record->id, 'status' => 'cancelled', 'total_price' => 6000]);
        $dashboard->call('$refresh')->assertSee('3.000 RSD')->assertDontSee('9.000 RSD');
        $statistics->call('$refresh')->assertSee('3.000 RSD')->assertSee('1 budućih termina')->assertDontSee('9.000 RSD');
        $stats = (new \ReflectionMethod(OperationsOverview::class, 'getCachedStats'))->invoke($dashboard->instance());
        $values = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);
        $this->assertSame('1', $values['Budući termini']);
        $this->assertSame('3.000 RSD', $values['Vrednost budućih rezervacija']);
        $this->assertSame('2.000 RSD', $values['Prihod od odigranih termina']);
        $period = $service->periodSnapshot($filters);
        $this->assertSame(1, $period['future']);
        $this->assertSame(3000.0, $period['futureValue']);
        $this->assertSame(2000.0, $period['revenue']);
        $this->assertSame(2, $period['reserved']);
        $this->assertSame(1, $period['cancelled']);
        $this->assertSame(2, $period['total']);
        $this->assertSame($period['reserved'], $period['total']);
        $this->assertDatabaseCount('reservations', 3);
        $this->assertEqualsWithDelta(100 / 3, $service->systemHealthSnapshot($filters)['cancellationRate'], 0.001);
        $statistics->assertSee('Bez otkazanih i besplatnih termina')
            ->assertSeeInOrder(['Ukupno rezervacija', '2', 'Otkazanih', '1'])
            ->assertDontSee('Neotkazane rezervacije');
        $this->assertSame(3000.0, array_sum($service->monthlyRevenue($filters)['futureData']));
    }

    public function test_free_bookings_do_not_change_any_commercial_booking_metric_and_remain_in_the_ledger(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $freeCustomer = User::factory()->create(['role' => 'customer']);
        $this->booking('2026-10-22 12:00:00', '2026-10-22 13:00:00', 2000, user: $customer);
        $this->booking('2026-10-23 12:00:00', '2026-10-23 13:00:00', 3000, user: $customer);
        $this->booking('2026-10-22 13:30:00', '2026-10-22 14:30:00', 1500, user: $customer);
        $this->booking('2026-10-22 15:00:00', '2026-10-22 16:00:00', 4000, 'cancelled', user: $customer);
        $filters = ['preset' => 'all'];
        $service = app(AdminAnalyticsService::class);
        $metrics = ['periodSnapshot', 'todaySnapshot', 'systemHealthSnapshot', 'courtPerformance', 'popularityByWeekday', 'popularityByHour', 'durationDistribution', 'monthlyRevenue', 'getFilterSummary'];
        $before = [];
        foreach ($metrics as $method) {
            $before[$method] = $service->$method($filters);
        }
        $this->booking('2026-10-16 10:00:00', '2026-10-16 11:00:00', 0, user: $freeCustomer);
        $this->booking('2026-10-22 13:30:00', '2026-10-22 14:30:00', 0, user: $customer);
        $this->booking('2027-12-31 10:00:00', '2027-12-31 11:00:00', 0, user: $customer);
        $this->booking('2026-10-22 15:00:00', '2026-10-22 16:00:00', 0, 'cancelled', user: $customer);
        foreach ($metrics as $method) {
            $this->assertEquals($before[$method], $service->$method($filters), $method.' must ignore free bookings');
        }
        $this->assertDatabaseCount('reservations', 8);
        $this->assertSame(2000.0, $service->periodSnapshot($filters)['averagePrice']);
        $court = Livewire::test(AnalyticsCourtPerformanceTable::class, ['pageFilters' => $filters])->instance()->getTableRecords()->firstWhere('slug', 'padel-teren-1');
        $this->assertEquals(4, $court->filtered_reservations_count);
        $this->assertEquals(180, $court->filtered_duration_minutes);
        $this->assertEquals(1, $court->filtered_completed_count);
        $customers = Livewire::test(AnalyticsTopCustomersTable::class, ['pageFilters' => $filters])->instance()->getTableRecords();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer->id, $customers->first()->id);
        $this->assertEquals(4, $customers->first()->filtered_reservations_count);
        $this->assertEquals(180, $customers->first()->filtered_duration_minutes);
        $this->assertSame(0, $service->periodSnapshot(['preset' => 'custom', 'startDate' => '2026-10-16', 'endDate' => '2026-10-18'])['total']);
        $dashboardCustomer = Livewire::test(TopCustomersTable::class)->instance()->getTableRecords()->firstWhere('id', $customer->id);
        $this->assertEquals(4, $dashboardCustomer->commercial_reservations_count);
        $chart = Livewire::test(ReservationTrendsChart::class)->instance();
        $chartData = (new \ReflectionMethod(ReservationTrendsChart::class, 'getCachedData'))->invoke($chart);
        $this->assertSame(4, array_sum($chartData['datasets'][0]['data']));
    }
}
