<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\GroupMatch;
use App\Models\PricingRule;
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
        $sport = Sport::firstOrCreate(['name' => 'Padel'], ['is_active' => true]);
        $court = $sport->courts()->firstOrCreate(['name' => 'Teren'], ['is_active' => true]);
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

    public function test_admin_can_reschedule_from_details_and_calendar_refreshes(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 12));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($admin);
        $record = $this->reservationForSelection('Promena vremena', 1);
        $other = $this->reservationForSelection('Drugi termin iz serije', 2);
        $match = GroupMatch::create([
            'sport_id' => $record->sport_id, 'reservation_id' => $record->id, 'status' => GroupMatch::Booked,
            'starts_at' => $record->starts_at, 'ends_at' => $record->ends_at,
            'closes_at' => $record->starts_at->copy()->subMinutes(30), 'duration_minutes' => 60,
            'team_size' => 2, 'quoted_price' => 1000,
        ]);
        $this->addRescheduleTariff($record);
        $start = $record->starts_at->copy()->setTime(17, 30);
        $end = $start->copy()->addMinutes(90);

        $component = Livewire::test(CalendarReservationsWidget::class)
            ->call('onEventClickJs', $this->clickData($record))
            ->assertActionMounted('view');
        $action = $component->instance()->getMountedAction()->getModalAction('rescheduleReservation');
        $this->assertTrue($action->isVisible());
        $this->assertSame('Izmeni vreme', $action->getLabel());

        $component->mountAction('rescheduleReservation')
            ->assertActionMounted('rescheduleReservation')
            ->assertSet('mountedActions.1.data.booking_date', $record->starts_at->toDateString())
            ->assertSet('mountedActions.1.data.booking_time', $record->starts_at->format('H:i'));

        $choiceView = $component->instance()->getSchema('mountedActionSchema1')->getComponent('reservation_time_choices');
        $choices = $choiceView->getViewData();
        $this->assertSame([-2 => '11:00', -1 => '12:00', 1 => '14:00', 2 => '15:00'], $choices['shifts']);
        $this->assertSame('mountedActions.1.data.booking_time', $choices['timePath']);
        $this->assertTrue($choices['slots']->first(fn ($slot) => $slot['starts_at']->format('H:i') === '13:00')['available']);
        $this->assertStringContainsString(
            '$set(\'mountedActions.1.data.booking_time\', \'14:00\')',
            html_entity_decode($choiceView->toHtml()),
        );

        $component->set('mountedActions.1.data.booking_time', $start->format('H:i'))
            ->set('mountedActions.1.data.duration_minutes', 90);
        $preview = $component->instance()->getSchema('mountedActionSchema1')->getComponent('schedule_preview');
        $this->assertSame($end->format('d.m.Y. H:i').' · 4.500,00 RSD', $preview->getState());

        $component->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertDispatched('calendar--refresh');

        $this->assertDatabaseHas('reservations', [
            'id' => $record->id, 'starts_at' => $start, 'ends_at' => $end,
            'duration_minutes' => 90, 'court_price' => 4500, 'total_price' => 4500,
        ]);
        $this->assertDatabaseHas('reservations', ['id' => $other->id, 'starts_at' => $other->starts_at]);
        $this->assertDatabaseHas('reservation_audits', ['reservation_id' => $record->id, 'actor_id' => $admin->id, 'event' => 'updated']);
        $this->assertDatabaseHas('group_matches', ['id' => $match->id, 'starts_at' => $start, 'ends_at' => $end, 'duration_minutes' => 90]);
    }

    public function test_calendar_rescheduling_rejects_a_busy_court_or_unavailable_equipment(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 12));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $record = $this->reservationForSelection('Promena vremena', 1);
        $busy = $this->reservationForSelection('Zauzet termin', 2);
        $this->addRescheduleTariff($record);

        $component = Livewire::test(CalendarReservationsWidget::class)
            ->call('onEventClickJs', $this->clickData($record))
            ->mountAction('rescheduleReservation')
            ->assertActionMounted('rescheduleReservation')
            ->set('mountedActions.1.data.booking_date', $busy->starts_at->toDateString())
            ->set('mountedActions.1.data.booking_time', $busy->starts_at->format('H:i'));

        $slots = $component->instance()->getSchema('mountedActionSchema1')->getComponent('reservation_time_choices')->getViewData()['slots'];
        $this->assertFalse($slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '13:00')['available']);
        $this->assertTrue($slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '14:00')['available']);

        $component->callMountedAction()
            ->assertHasErrors(['mountedActions.1.data.booking_time' => 'Izabrani termin nije dostupan za ovaj teren.']);

        $equipment = Equipment::create([
            'name' => 'Reket', 'is_active' => true, 'is_rentable' => true, 'rental_price' => 100, 'stock_quantity' => 1,
        ]);
        foreach ([$record, $busy] as $reservation) {
            $reservation->equipmentItems()->create([
                'equipment_id' => $equipment->id, 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100,
            ]);
        }
        $otherCourt = Court::create(['sport_id' => $busy->sport_id, 'name' => 'Drugi teren', 'is_active' => true]);
        $busy->update(['court_id' => $otherCourt->id]);

        Livewire::test(CalendarReservationsWidget::class)
            ->call('onEventClickJs', $this->clickData($record))
            ->mountAction('rescheduleReservation')
            ->set('mountedActions.1.data.booking_date', $busy->starts_at->toDateString())
            ->set('mountedActions.1.data.booking_time', $busy->starts_at->format('H:i'))
            ->callMountedAction()
            ->assertHasErrors(['mountedActions.1.data.booking_time' => 'Oprema Reket nije dostupna u trazenoj kolicini.']);
        $this->assertDatabaseHas('reservations', [
            'id' => $record->id, 'starts_at' => $record->starts_at, 'total_price' => 1000,
        ]);
    }

    private function addRescheduleTariff(Reservation $record): void
    {
        PricingRule::create([
            'sport_id' => $record->sport_id, 'name' => 'Test cenovnik', 'start_time' => '08:00', 'end_time' => '00:00',
            'price_60' => 3000, 'price_90' => 4500, 'price_120' => 6000, 'is_active' => true,
        ]);
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
