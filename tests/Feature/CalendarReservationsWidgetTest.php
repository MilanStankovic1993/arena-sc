<?php

namespace Tests\Feature;

use App\Filament\Widgets\CalendarReservationsWidget;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Guava\Calendar\Enums\CalendarViewType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarReservationsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_view_uses_readable_list_layout(): void
    {
        $property = new \ReflectionProperty(CalendarReservationsWidget::class, 'calendarView');

        $this->assertSame(CalendarViewType::ListWeek, $property->getValue(app(CalendarReservationsWidget::class)));
    }

    public function test_calendar_resources_are_court_columns_with_sport_context(): void
    {
        $padel = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $basket = Sport::query()->create([
            'name' => 'Basket 3x3',
            'slug' => 'basket-3x3',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $padelCourt = Court::query()->create([
            'sport_id' => $padel->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'is_active' => true,
        ]);
        $basketCourt = Court::query()->create([
            'sport_id' => $basket->id,
            'name' => 'Basket teren',
            'slug' => 'basket-teren',
            'is_active' => true,
        ]);

        $resources = app(CalendarReservationsWidget::class)->getResourcesJs();

        $this->assertSame('court-'.$padelCourt->id, $resources[0]['id']);
        $this->assertSame('Padel / Padel teren 1', $resources[0]['title']);
        $this->assertSame('Padel', $resources[0]['extendedProps']['sport']);
        $this->assertSame('Padel teren 1', $resources[0]['extendedProps']['court']);
        $this->assertSame('court-'.$basketCourt->id, $resources[1]['id']);
        $this->assertSame('Basket 3x3 / Basket teren', $resources[1]['title']);
        $this->assertSame('Basket 3x3', $resources[1]['extendedProps']['sport']);
        $this->assertArrayNotHasKey('children', array_filter($resources[0]));
    }

    public function test_calendar_events_are_linked_to_their_court_resource(): void
    {
        $user = User::factory()->create(['name' => 'Milan Stankovic']);
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'is_active' => true,
        ]);
        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'is_active' => true,
        ]);
        $startsAt = now()->addDay()->setTime(13, 0);

        Reservation::query()->create([
            'user_id' => $user->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'duration_minutes' => 90,
            'court_price' => 4500,
            'equipment_price' => 0,
            'total_price' => 4500,
        ]);

        $events = app(CalendarReservationsWidget::class)->getEventsJs([
            'startStr' => $startsAt->copy()->startOfDay()->toIso8601String(),
            'endStr' => $startsAt->copy()->endOfDay()->toIso8601String(),
            'tzOffset' => 0,
        ]);

        $this->assertSame(['court-'.$court->id], $events[0]['resourceIds']);
        $this->assertStringContainsString('Milan Stankovic | Padel teren 1', $events[0]['title']);
    }
}
