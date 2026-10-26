<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\PhoneReservationForm;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\CalendarResource;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class CalendarReservationsWidget extends CalendarWidget
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $sort = 4;

    #[Locked]
    public CalendarViewType $calendarView = CalendarViewType::ListWeek;

    #[Locked]
    public bool $selectingReservations = false;

    #[Locked]
    public array $selectedReservationIds = [];

    protected bool $eventClickEnabled = true;

    protected bool $dateClickEnabled = true;

    protected ?string $defaultEventClickAction = 'view';

    public function getHeaderActions(): array
    {
        return [
            PhoneReservationForm::action()->visible(fn () => ReservationResource::canCreate())
                ->fillForm(fn (array $arguments): array => [
                    'court_id' => $arguments['court_id'] ?? null,
                    'booking_date' => $arguments['booking_date'] ?? now()->toDateString(),
                    'booking_time' => $arguments['booking_time'] ?? null,
                    'duration_minutes' => 60,
                    'customer_type' => 'guest',
                ])
                ->after(fn () => $this->refreshRecords()),
            Action::make('select_reservations')
                ->label(fn (): string => $this->selectingReservations ? 'Završi izbor ('.count($this->selectedReservationIds).')' : 'Izaberi termine')
                ->color(fn (): string => $this->selectingReservations ? 'primary' : 'gray')
                ->action(function (): void {
                    $this->selectingReservations = ! $this->selectingReservations;
                    $this->selectedReservationIds = [];
                    $this->refreshRecords();
                }),
            $this->reservationMutationAction('cancel_selected', false, true),
            $this->reservationMutationAction('delete_selected', true, true),
            Action::make('view_day')
                ->label('Dan')
                ->color(fn (): string => $this->calendarView === CalendarViewType::ResourceTimeGridDay ? 'primary' : 'gray')
                ->extraAttributes(fn (): array => ['aria-pressed' => $this->calendarView === CalendarViewType::ResourceTimeGridDay ? 'true' : 'false'])
                ->action(fn () => $this->switchView(CalendarViewType::ResourceTimeGridDay)),
            Action::make('view_week')
                ->label('Nedelja')
                ->color(fn (): string => $this->calendarView === CalendarViewType::ListWeek ? 'primary' : 'gray')
                ->extraAttributes(fn (): array => ['aria-pressed' => $this->calendarView === CalendarViewType::ListWeek ? 'true' : 'false'])
                ->action(fn () => $this->switchView(CalendarViewType::ListWeek)),
            Action::make('view_month')
                ->label('Mesec')
                ->color(fn (): string => $this->calendarView === CalendarViewType::DayGridMonth ? 'primary' : 'gray')
                ->extraAttributes(fn (): array => ['aria-pressed' => $this->calendarView === CalendarViewType::DayGridMonth ? 'true' : 'false'])
                ->action(fn () => $this->switchView(CalendarViewType::DayGridMonth)),
        ];
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        if ($this->selectingReservations || ! ReservationResource::canCreate()) {
            return;
        }

        $resourceId = (string) ($info->resource?->getId() ?? '');
        $courtId = str_starts_with($resourceId, 'court-') ? (int) substr($resourceId, 6) : null;
        $this->mountAction('create', [
            'court_id' => $courtId,
            'booking_date' => $info->date->toDateString(),
            'booking_time' => $info->allDay ? null : $info->date->format('H:i'),
        ]);
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        abort_unless($event instanceof Reservation && ReservationResource::canView($event), 403);

        if ($this->selectingReservations) {
            $id = (int) $event->getKey();
            $this->selectedReservationIds = in_array($id, $this->selectedReservationIds, true)
                ? array_values(array_diff($this->selectedReservationIds, [$id]))
                : [...$this->selectedReservationIds, $id];
            $this->refreshRecords();

            return;
        }

        parent::onEventClick($info, $event, $action);
    }

    protected function reservationMutationAction(string $name, bool $delete, bool $bulk = false): Action
    {
        return Action::make($name)
            ->label(fn (): string => ($delete ? 'Trajno obriši' : 'Otkaži').($bulk ? ' izabrane ('.count($this->selectedReservationIds).')' : ' termin'))
            ->color($delete ? 'danger' : 'warning')
            ->visible(fn (): bool => $bulk ? $this->selectingReservations : ($this->getEventRecord() instanceof Reservation && ($delete
                ? ReservationResource::canDelete($this->getEventRecord())
                : ReservationResource::canEdit($this->getEventRecord()) && $this->getEventRecord()->status === ReservationStatus::Reserved)))
            ->disabled(fn (): bool => $bulk && $this->selectedReservationIds === [])
            ->requiresConfirmation()
            ->modalHeading($delete ? 'Trajno brisanje termina' : 'Otkazivanje termina')
            ->modalDescription(fn (): string => ($bulk ? 'Izabrano termina: '.count($this->selectedReservationIds).'. ' : '')
                .($delete ? 'Rezervacije i povezane stavke biće trajno obrisane. Ova radnja se ne može poništiti.' : 'Menja se status u „Otkazana“. Podaci ostaju sačuvani, a termin se oslobađa.')
                .' Ostali termini ponavljajuće serije neće biti promenjeni.')
            ->modalSubmitActionLabel($delete ? 'Da, trajno obriši' : 'Da, otkaži')
            ->cancelParentActions()
            ->action(function () use ($delete, $bulk): void {
                $ids = $bulk ? $this->selectedReservationIds : [$this->getEventRecord()?->getKey()];
                abort_if(empty($ids) || in_array(null, $ids, true), 422);
                $count = DB::transaction(function () use ($ids, $delete): int {
                    $records = Reservation::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
                    foreach ($records as $record) {
                        abort_unless($delete ? ReservationResource::canDelete($record) : ReservationResource::canEdit($record), 403);
                    }
                    $count = 0;
                    foreach ($records as $record) {
                        if ($delete) {
                            $record->delete();
                            $count++;
                        } elseif ($record->status === ReservationStatus::Reserved) {
                            $record->update(['status' => ReservationStatus::Cancelled]);
                            $count++;
                        }
                    }

                    return $count;
                });
                $this->selectedReservationIds = [];
                $this->refreshRecords();
                Notification::make()->title(($delete ? 'Trajno obrisano termina: ' : 'Otkazano termina: ').$count)->success()->send();
            });
    }

    public function cancelReservationAction(): Action
    {
        return $this->reservationMutationAction('cancelReservation', false);
    }

    public function deleteReservationAction(): Action
    {
        return $this->reservationMutationAction('deleteReservation', true);
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
                    $reservation->is_gift ? 'Poklon' : null,
                    $reservation->customer_display_name,
                    $sportName,
                    $courtName,
                ])->filter()->implode(' | ');
                $color = $this->eventColor($reservation);
                $selected = in_array((int) $reservation->id, $this->selectedReservationIds, true);

                return CalendarEvent::make($reservation)
                    ->title(($this->selectingReservations ? ($selected ? '☑ ' : '☐ ') : '').$title)
                    ->classNames($selected ? ['arena-event-selected'] : [])
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
            'slotMaxTime' => '24:00:00',
            'slotLabelFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
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
                TextEntry::make('status')->label('Status')
                    ->state(fn (Reservation $record): string => $record->display_status),
                TextEntry::make('starts_at')
                    ->label('Termin')
                    ->state(fn (Reservation $record): string => $record->starts_at->format('d.m.Y H:i').' - '.$record->ends_at->format('H:i')),
                TextEntry::make('total_price')
                    ->label('Ukupna cena')
                    ->state(fn (Reservation $record): string => number_format((float) $record->total_price, 0, ',', '.').' RSD'),
            ])
            ->extraModalFooterActions([
                $this->cancelReservationAction(),
                $this->deleteReservationAction(),
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
