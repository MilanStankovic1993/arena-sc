<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Filament\Facades\Filament;
use Guava\Calendar\Enums\CalendarViewType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarReservationsWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function reservationForSelection(string $name, int $day): Reservation
    {
        $sport = Sport::firstOrCreate(['slug' => 'selection-test'], ['name' => 'Padel', 'is_active' => true]);
        $court = Court::firstOrCreate(['slug' => 'selection-test'], ['sport_id' => $sport->id, 'name' => 'Teren', 'is_active' => true]);
        $start = now()->addDays($day)->setTime(13, 0);

        return Reservation::withoutEvents(fn () => Reservation::create([
            'sport_id' => $sport->id, 'court_id' => $court->id, 'guest_name' => $name,
            'status' => 'reserved', 'starts_at' => $start, 'ends_at' => $start->copy()->addHour(),
            'duration_minutes' => 60, 'court_price' => 1000, 'equipment_price' => 0, 'total_price' => 1000,
            'series_id' => 'same-series',
        ]));
    }

    private function clickData(Reservation $record): array
    {
        $start = $record->starts_at->toIso8601String();
        $end = $record->ends_at->toIso8601String();

        return ['tzOffset' => 0, 'event' => [
            'title' => $record->guest_name, 'start' => $start, 'end' => $end, 'allDay' => false,
            'styles' => [], 'classNames' => [], 'resourceIds' => [], 'display' => 'auto',
            'extendedProps' => ['model' => Reservation::class, 'key' => $record->id],
        ], 'view' => ['type' => 'listWeek', 'title' => 'Test', 'currentStart' => $start,
            'currentEnd' => $end, 'activeStart' => $start, 'activeEnd' => $end]];
    }

    public function test_selection_toggles_and_bulk_cancel_preserves_records_and_other_series_dates(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $first = $this->reservationForSelection('Prvi', 1);
        $second = $this->reservationForSelection('Drugi', 2);
        $other = $this->reservationForSelection('Ostaje', 3);
        $component = Livewire::test(CalendarReservationsWidget::class)
            ->callAction('select_reservations')
            ->call('onEventClickJs', $this->clickData($first))
            ->assertSet('selectedReservationIds', [$first->id])
            ->call('onEventClickJs', $this->clickData($first))
            ->assertSet('selectedReservationIds', [])
            ->call('onEventClickJs', $this->clickData($first))
            ->call('onEventClickJs', $this->clickData($second))
            ->callAction('cancel_selected')
            ->assertSet('selectedReservationIds', []);
        $this->assertDatabaseHas('reservations', ['id' => $first->id, 'status' => 'cancelled', 'total_price' => 1000]);
        $this->assertDatabaseHas('reservations', ['id' => $second->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('reservations', ['id' => $other->id, 'status' => 'reserved']);
    }

    public function test_bulk_delete_removes_only_selected_reservations(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $first = $this->reservationForSelection('Obrisi', 1);
        $other = $this->reservationForSelection('Ostaje', 2);
        $participant = User::factory()->create();
        $first->participants()->attach($participant);
        Livewire::test(CalendarReservationsWidget::class)->callAction('select_reservations')
            ->call('onEventClickJs', $this->clickData($first))
            ->callAction('delete_selected')->assertSet('selectedReservationIds', []);
        $this->assertSoftDeleted('reservations', ['id' => $first->id]);
        $this->assertDatabaseHas('reservation_participants', ['reservation_id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $participant->id]);
        $this->assertDatabaseHas('reservations', ['id' => $other->id]);
    }

    public function test_leaving_selection_clears_marks_and_keeps_the_chosen_view(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $record = $this->reservationForSelection('Izbor', 1);
        Livewire::test(CalendarReservationsWidget::class)->callAction('view_month')
            ->callAction('select_reservations')->call('onEventClickJs', $this->clickData($record))
            ->callAction('select_reservations')->assertSet('selectingReservations', false)
            ->assertSet('selectedReservationIds', [])->assertSet('calendarView', CalendarViewType::DayGridMonth);
        $this->assertDatabaseHas('reservations', ['id' => $record->id, 'status' => 'reserved']);
    }

    public function test_single_actions_work_from_the_reservation_details(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $record = $this->reservationForSelection('Pojedinacni', 1);
        Livewire::test(CalendarReservationsWidget::class)
            ->call('onEventClickJs', $this->clickData($record))
            ->assertActionMounted('view')
            ->callAction('cancelReservation');
        $this->assertDatabaseHas('reservations', ['id' => $record->id, 'status' => 'cancelled']);
        Livewire::test(CalendarReservationsWidget::class)
            ->call('onEventClickJs', $this->clickData($record->fresh()))
            ->callAction('deleteReservation');
        $this->assertSoftDeleted('reservations', ['id' => $record->id]);
    }

    public function test_selected_view_and_button_state_survive_subsequent_livewire_requests(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $component = Livewire::test(CalendarReservationsWidget::class);

        foreach ([
            ['view_month', CalendarViewType::DayGridMonth],
            ['view_day', CalendarViewType::ResourceTimeGridDay],
            ['view_week', CalendarViewType::ListWeek],
            ['view_month', CalendarViewType::DayGridMonth],
        ] as [$selectedAction, $view]) {
            $component->callAction($selectedAction)
                ->assertSet('calendarView', $view)
                ->assertDispatched('calendar--set', key: 'view', value: $view->value)
                ->call('$refresh')
                ->assertSet('calendarView', $view);

            foreach (['view_day', 'view_week', 'view_month'] as $actionName) {
                $action = $component->instance()->getAction($actionName);
                $this->assertSame($actionName === $selectedAction ? 'primary' : 'gray', $action->getColor());
                $this->assertSame($actionName === $selectedAction ? 'true' : 'false', $action->getExtraAttributes()['aria-pressed']);
            }
        }
    }

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
        $this->assertSame('Milan Stankovic | Padel | Padel teren 1', $events[0]['title']);
    }
}
