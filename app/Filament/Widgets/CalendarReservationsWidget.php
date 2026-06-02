<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Filament\Actions\Action;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;

class CalendarReservationsWidget extends CalendarWidget
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $sort = 4;

    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    public function getHeaderActions(): array
    {
        return [
            Action::make('view_day')
                ->label('Dan')
                ->color($this->calendarView === CalendarViewType::TimeGridDay ? 'primary' : 'gray')
                ->action(fn () => $this->switchView(CalendarViewType::TimeGridDay)),
            Action::make('view_week')
                ->label('Nedelja')
                ->color($this->calendarView === CalendarViewType::TimeGridWeek ? 'primary' : 'gray')
                ->action(fn () => $this->switchView(CalendarViewType::TimeGridWeek)),
            Action::make('view_month')
                ->label('Mesec')
                ->color($this->calendarView === CalendarViewType::DayGridMonth ? 'primary' : 'gray')
                ->action(fn () => $this->switchView(CalendarViewType::DayGridMonth)),
        ];
    }

    public function switchView(CalendarViewType $view): void
    {
        $this->calendarView = $view;
        $this->dispatch('calendar--set', key: 'view', value: $view->value);
    }

    public function getHeading(): string
    {
        return 'Kalendar rezervacija';
    }

    protected function getEvents(FetchInfo $info): Collection | array
    {
        return Reservation::query()
            ->where('starts_at', '<', $info->end)
            ->where('ends_at', '>', $info->start)
            ->with('user')
            ->get()
            ->map(function (Reservation $reservation): CalendarEvent {
                $title = $reservation->user?->name ?: 'Gost';

                $color = match ($reservation->status->value ?? $reservation->status) {
                    'reserved' => '#16a34a',
                    'cancelled' => '#dc2626',
                    default => '#2563eb',
                };

                return CalendarEvent::make($reservation)
                    ->title($title)
                    ->start($reservation->starts_at)
                    ->end($reservation->ends_at)
                    ->backgroundColor($color)
                    ->textColor('#ffffff')
                    ->extendedProps([
                        'reservation_id' => $reservation->id,
                    ]);
            })
            ->values();
    }

    public function getOptions(): array
    {
        return [
            'firstDay' => 1,
            'allDaySlot' => false,
            'nowIndicator' => true,
            'height' => 'auto',
            'slotMinTime' => '07:00:00',
            'slotMaxTime' => '23:00:00',
            'slotDuration' => '00:30:00',
            'datesAboveResources' => true,
        ];
    }
}
