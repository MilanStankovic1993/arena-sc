<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\PhoneReservationForm;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use App\Services\GroupMatchService;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationEditPricingService;
use App\Services\ReservationPricingService;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\CalendarResource;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use RuntimeException;

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
            ->label(fn (): string => ($delete ? 'Obriši' : 'Otkaži').($bulk ? ' izabrane ('.count($this->selectedReservationIds).')' : ' termin'))
            ->color($delete ? 'danger' : 'warning')
            ->visible(fn (): bool => $bulk ? $this->selectingReservations : ($this->getEventRecord() instanceof Reservation && ($delete
                ? ReservationResource::canDelete($this->getEventRecord())
                : ReservationResource::canEdit($this->getEventRecord()) && $this->getEventRecord()->status === ReservationStatus::Reserved)))
            ->disabled(fn (): bool => $bulk && $this->selectedReservationIds === [])
            ->requiresConfirmation()
            ->modalHeading($delete ? 'Brisanje termina uz mogućnost vraćanja' : 'Otkazivanje termina')
            ->modalDescription(fn (): string => ($bulk ? 'Izabrano termina: '.count($this->selectedReservationIds).'. ' : '')
                .($delete ? 'Termin se oslobađa. Rezervacija i oprema ostaju u evidenciji obrisanih termina i mogu da se vrate. Šalje se mejl obaveštenje o brisanju.' : 'Menja se status u „Otkazana“. Podaci ostaju sačuvani, a termin se oslobađa.')
                .' Ostali termini ponavljajuće serije neće biti promenjeni.')
            ->modalSubmitActionLabel($delete ? 'Da, obriši' : 'Da, otkaži')
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
                Notification::make()->title(($delete ? 'Premešteno u obrisane termine: ' : 'Otkazano termina: ').$count)->success()->send();
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

    public function rescheduleReservationAction(): Action
    {
        return Action::make('rescheduleReservation')
            ->label('Izmeni vreme')
            ->icon(Heroicon::OutlinedClock)
            ->record(fn () => $this->getEventRecord())
            ->visible(fn (?Model $record): bool => $record instanceof Reservation
                && $record->status === ReservationStatus::Reserved
                && ReservationResource::canEdit($record))
            ->modalHeading('Izmeni vreme termina')
            ->modalWidth(Width::TwoExtraLarge)
            ->stickyModalFooter()
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('Sačuvaj vreme')
            ->fillForm(fn (Reservation $record): array => [
                'booking_date' => $record->starts_at->toDateString(),
                'booking_time' => $record->starts_at->format('H:i'),
                'duration_minutes' => $record->duration_minutes,
            ])
            ->schema([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    DatePicker::make('booking_date')->label('Datum')->native()->required()->live(),
                    TextInput::make('booking_time')->label('Početak')->placeholder('HH:MM')->mask('99:99')
                        ->rule('date_format:H:i')->required()->live(onBlur: true),
                ]),
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    ToggleButtons::make('duration_minutes')->label('Trajanje')
                        ->options([60 => '60 min', 90 => '90 min', 120 => '120 min'])->inline()->required()->live(),
                    TextEntry::make('schedule_preview')->label('Završetak i ukupna cena')
                        ->state(fn (Get $get, Reservation $record): ?string => $this->reservationTimePreview($record, $get)),
                ]),
                View::make('filament.reservation-time-choices')
                    ->key('reservation_time_choices')
                    ->viewData(fn (Get $get, Reservation $record, View $component): array => [
                        ...$this->reservationTimeChoices($record, $get),
                        'timePath' => $component->getContainer()->getStatePath().'.booking_time',
                    ]),
            ])
            ->cancelParentActions()
            ->action(function (Reservation $record, array $data): void {
                $this->saveReservationTime($record, $data);
                $this->refreshRecords();
                Notification::make()->title('Vreme termina je sačuvano.')->success()->send();
            });
    }

    private function reservationTimePreview(Reservation $record, Get $get): ?string
    {
        $date = $get('booking_date');
        $time = $get('booking_time');
        $duration = (int) $get('duration_minutes');
        if (blank($date) || ! Carbon::hasFormat($time ?? '', 'H:i') || ! in_array($duration, [60, 90, 120], true)) {
            return null;
        }

        try {
            $schedule = $this->rescheduleData($record, $date.' '.$time, $duration);
            $data = app(ReservationEditPricingService::class)->prepareForSave($schedule, $record);
        } catch (QueryException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }

        $end = Carbon::parse($data['ends_at']);
        $available = app(ReservationAvailabilityService::class)->isAvailable(
            $record->court, Carbon::parse($data['starts_at']), $end, $record->id,
        );

        return $end->format('d.m.Y. H:i')
            .' · '.number_format((float) $data['total_price'], 2, ',', '.').' RSD'
            .($available ? '' : ' · Teren je zauzet');
    }

    private function reservationTimeChoices(Reservation $record, Get $get): array
    {
        $duration = (int) $get('duration_minutes');
        $date = $get('booking_date');
        $time = $get('booking_time');
        $result = ['slots' => collect(), 'shifts' => [], 'time' => $time];
        if (blank($date) || ! in_array($duration, [60, 90, 120], true)) {
            return $result;
        }

        $schedule = app(ReservationScheduleService::class);
        $day = Carbon::parse($date);
        $slots = $schedule->buildDailySchedule($record->court, $day, $duration);
        $result['slots'] = app(ReservationAvailabilityService::class)->markAvailableSlots($record->court, $slots, $record->id);

        if (Carbon::hasFormat($time ?? '', 'H:i')) {
            $start = Carbon::parse($date.' '.$time);
            foreach ([-2, -1, 1, 2] as $hours) {
                $target = $start->copy()->addHours($hours);
                $result['shifts'][$hours] = $target->isSameDay($day)
                    && $schedule->isWithinOperatingHours($target, $target->copy()->addMinutes($duration))
                    ? $target->format('H:i') : null;
            }
        }

        return $result;
    }

    private function rescheduleData(Reservation $record, mixed $start, int $duration): array
    {
        if ($start === $record->starts_at->format('Y-m-d H:i')) {
            $start = $record->starts_at;
        }

        return app(ReservationEditPricingService::class)->synchronizeTimes([
            'sport_id' => $record->sport_id,
            'court_id' => $record->court_id,
            'starts_at' => $start,
            'ends_at' => $record->ends_at,
            'duration_minutes' => $duration,
            'is_gift' => $record->is_gift,
            'court_price' => $record->court_price,
            'equipment_price' => $record->equipment_price,
            'total_price' => $record->total_price,
        ], 'duration_minutes');
    }

    private function saveReservationTime(Reservation $record, array $data): void
    {
        $path = $this->getMountedActionSchema()->getStatePath();

        DB::transaction(function () use ($record, $data, $path): void {
            Court::query()->lockForUpdate()->findOrFail($record->court_id);
            $current = Reservation::withTrashed()->lockForUpdate()->findOrFail($record->getKey());
            abort_unless(ReservationResource::canEdit($current), 403);

            if ($current->court_id !== $record->court_id || $current->status !== ReservationStatus::Reserved) {
                throw ValidationException::withMessages([
                    $path.'.booking_time' => 'Rezervacija je u međuvremenu promenjena. Ponovo otvorite detalje termina.',
                ]);
            }

            try {
                $schedule = $this->rescheduleData($current, $data['booking_date'].' '.$data['booking_time'], (int) $data['duration_minutes']);
                $start = Carbon::parse($schedule['starts_at']);
                $end = Carbon::parse($schedule['ends_at']);
                if ($start->equalTo($current->starts_at) && $end->equalTo($current->ends_at)
                    && $schedule['duration_minutes'] === (int) $current->duration_minutes) {
                    return;
                }

                app(ReservationPricingService::class)->hydrateEquipmentPricing(
                    $current->equipmentItems()->lockForUpdate()->get(['equipment_id', 'quantity'])->toArray(),
                    $current->sport_id, $start, $end, $current->id,
                );
                $current->update(app(ReservationEditPricingService::class)->prepareForSave($schedule, $current));
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    $path.'.booking_time' => collect($exception->errors())->flatten()->all(),
                ]);
            } catch (QueryException $exception) {
                throw $exception;
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([$path.'.booking_time' => $exception->getMessage()]);
            }

            $current->groupMatch?->update([
                'starts_at' => $current->starts_at,
                'ends_at' => $current->ends_at,
                'closes_at' => $current->starts_at->copy()->subMinutes(GroupMatchService::ClosingMinutes),
                'duration_minutes' => $current->duration_minutes,
            ]);
        }, attempts: 3);
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
                $this->rescheduleReservationAction(),
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
