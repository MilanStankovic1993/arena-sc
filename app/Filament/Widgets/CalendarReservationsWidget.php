<?php

namespace App\Filament\Widgets;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\CalendarResource;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;

class CalendarReservationsWidget extends CalendarWidget
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $sort = 4;

    protected CalendarViewType $calendarView = CalendarViewType::ListWeek;

    protected bool $eventClickEnabled = true;

    protected ?string $defaultEventClickAction = 'view';

    public function getHeaderActions(): array
    {
        return [
            Action::make('view_day')
                ->label('Dan')
                ->color($this->calendarView === CalendarViewType::ResourceTimeGridDay ? 'primary' : 'gray')
                ->action(fn () => $this->switchView(CalendarViewType::ResourceTimeGridDay)),
            Action::make('view_week')
                ->label('Nedelja')
                ->color($this->calendarView === CalendarViewType::ListWeek ? 'primary' : 'gray')
                ->action(fn () => $this->switchView(CalendarViewType::ListWeek)),
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

    protected function getEvents(FetchInfo $info): Collection|array
    {
        return Reservation::query()
            ->where('starts_at', '<', $info->end)
            ->where('ends_at', '>', $info->start)
            ->with(['court', 'sport', 'user'])
            ->get()
            ->map(function (Reservation $reservation): CalendarEvent {
                $courtName = $reservation->court?->name;
                $sportName = $reservation->sport?->name;
                $title = collect([
                    $reservation->customer_display_name,
                    $courtName,
                ])->filter()->implode(' | ');
                $color = $this->eventColor($reservation);

                return CalendarEvent::make($reservation)
                    ->title($title)
                    ->start($reservation->starts_at)
                    ->end($reservation->ends_at)
                    ->resourceId($this->courtResourceId($reservation->court_id))
                    ->backgroundColor($color)
                    ->textColor('#ffffff')
                    ->styles([
                        'border-color' => $color,
                        'box-shadow' => '0 12px 24px rgba(15, 42, 31, 0.18)',
                    ])
                    ->extendedProps([
                        'reservation_id' => $reservation->id,
                        'sport' => $sportName,
                        'court' => $courtName,
                    ]);
            })
            ->values();
    }

    protected function getResources(): Collection|array
    {
        return Sport::query()
            ->with(['courts' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (Sport $sport): Collection => $sport->courts
                ->map(fn (Court $court): CalendarResource => CalendarResource::make($this->courtResourceId($court->id))
                    ->title($sport->name.' / '.$court->name)
                    ->eventBackgroundColor($this->sportColor($sport))
                    ->eventTextColor('#ffffff')
                    ->extendedProps([
                        'sport' => $sport->name,
                        'court' => $court->name,
                        'court_id' => $court->id,
                    ])))
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
            'eventMaxStack' => 4,
            'eventMinHeight' => 42,
            'eventShortHeight' => 36,
            'resourceAreaHeaderContent' => 'Sport / teren',
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
        ];
    }

    public function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading('Pregled rezervacije')
            ->modalWidth(Width::Large)
            ->schema([
                TextEntry::make('customer_display_name')
                    ->label('Korisnik / gost')
                    ->weight(FontWeight::Bold),
                TextEntry::make('customer_display_phone')
                    ->label('Telefon')
                    ->placeholder('Nije unet'),
                TextEntry::make('sport.name')
                    ->label('Sport')
                    ->badge(),
                TextEntry::make('court.name')
                    ->label('Teren')
                    ->badge(),
                TextEntry::make('starts_at')
                    ->label('Termin')
                    ->state(fn (Reservation $record): string => $record->starts_at->format('d.m.Y H:i').' - '.$record->ends_at->format('H:i')),
                TextEntry::make('total_price')
                    ->label('Ukupna cena')
                    ->state(fn (Reservation $record): string => number_format((float) $record->total_price, 0, ',', '.').' RSD'),
            ]);
    }

    protected function eventColor(Reservation $reservation): string
    {
        if (($reservation->status->value ?? $reservation->status) === 'cancelled') {
            return '#dc2626';
        }

        return $this->sportColor($reservation->sport);
    }

    protected function sportColor(?Sport $sport): string
    {
        $palette = [
            '#16a34a',
            '#2563eb',
            '#f59e0b',
            '#0f766e',
            '#be123c',
            '#7c3aed',
        ];

        return $palette[((int) ($sport?->id ?? 0)) % count($palette)];
    }

    protected function courtResourceId(int|string|null $courtId): string
    {
        return 'court-'.($courtId ?: 'unknown');
    }
}
