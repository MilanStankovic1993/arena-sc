<?php

namespace App\Filament\Resources\Reservations;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminReservationService;
use App\Services\ReservationListService;
use App\Services\ReservationParticipantService;
use App\Services\ReservationPricingService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use UnitEnum;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static ?string $navigationLabel = 'Rezervacije';

    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';

    protected static ?string $modelLabel = 'Rezervacija';

    protected static ?string $pluralModelLabel = 'Rezervacije';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function canEdit(Model $record): bool
    {
        return ! $record->trashed() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ! $record->trashed() && parent::canDelete($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Termin')->schema([
                Select::make('user_id')
                    ->label('Korisnik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Za guest rezervaciju ostavi korisnika prazno i popuni podatke gosta.'),
                TextInput::make('guest_name')
                    ->label('Ime gosta')
                    ->maxLength(255)
                    ->required(fn ($get): bool => blank($get('user_id'))),
                TextInput::make('guest_phone')
                    ->label('Telefon gosta (opciono)')
                    ->maxLength(50),
                TextInput::make('guest_email')
                    ->label('Email gosta')
                    ->email()
                    ->maxLength(255),
                Select::make('sport_id')->label('Sport')->relationship('sport', 'name')->required()->searchable()->preload(),
                Select::make('court_id')->label('Teren')->relationship('court', 'name')->required()->searchable()->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(ReservationStatus::cases())->mapWithKeys(fn (ReservationStatus $status) => [$status->value => $status->label()])->all())
                    ->required()
                    ->default(ReservationStatus::Reserved->value),
                DateTimePicker::make('starts_at')->label('Pocetak')->native(false)->displayFormat('d.m.Y H:i')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label('Kraj')->native(false)->displayFormat('d.m.Y H:i')->seconds(false)->required(),
                Select::make('duration_minutes')->label('Trajanje')->options([
                    60 => '60 minuta',
                    90 => '90 minuta',
                    120 => '120 minuta',
                ])->default(60)->required(),
                TextInput::make('court_price')->label('Cena terena')->numeric()->minValue(0)->prefix('RSD')->required()->readOnly(fn ($get): bool => (bool) $get('is_gift')),
                TextInput::make('equipment_price')->label('Cena opreme')->numeric()->minValue(0)->prefix('RSD')->required()->readOnly(fn ($get): bool => (bool) $get('is_gift')),
                TextInput::make('total_price')->label('Ukupno')->numeric()->minValue(0)->prefix('RSD')->required()->readOnly(fn ($get): bool => (bool) $get('is_gift')),
                Toggle::make('is_gift')->label('Poklon — cena 0 RSD')->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        if ($state) {
                            foreach (['court_price', 'equipment_price', 'total_price'] as $field) {
                                $set($field, 0);
                            }
                        }
                    })
                    ->helperText('Poklon ne ulazi u komercijalnu statistiku. Ako uklonite oznaku, ručno unesite cenu pre čuvanja.'),
                Textarea::make('customer_note')->label('Napomena korisnika')->rows(3)->columnSpanFull(),
                Textarea::make('admin_note')->label('Interna napomena')->rows(3)->columnSpanFull(),
                Textarea::make('cancellation_reason')
                    ->label('Razlog otkazivanja')
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('status') === ReservationStatus::Cancelled->value),
            ])->columns(3),
            Section::make('Oprema uz termin')->schema([
                Repeater::make('equipmentItems')
                    ->relationship()
                    ->label('Stavke opreme')
                    ->addActionLabel('Dodaj stavku opreme')
                    ->schema([
                        Select::make('equipment_id')->relationship('equipment', 'name')->required()->searchable()->preload(),
                        TextInput::make('quantity')->label('Kolicina')->integer()->minValue(1)->default(1)->required(),
                        TextInput::make('unit_price')->label('Cena po komadu')->numeric()->minValue(0)->prefix('RSD')->required(),
                        TextInput::make('line_total')->label('Ukupno')->numeric()->minValue(0)->prefix('RSD')->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $list = app(ReservationListService::class);

        return $table
            ->recordTitleAttribute('id')
            ->extraAttributes(['class' => 'reservation-person-groups-table'])
            ->modifyQueryUsing(fn (Builder $query): Builder => $list->query($query))
            ->description('Pretraži ime, telefon ili mejl. Klikni na ime da otvoriš termine. Za sva polja pomeri tabelu levo/desno; za ostale termine skroluj unutar tabele. Zbirovi važe za izabrani pregled. Broj po stranici odnosi se na grupe osoba. Bez kontakta se koristi tačno uneto ime.')
            ->searchPlaceholder('Ime, telefon ili mejl...')
            ->searchDebounce('350ms')
            ->groups([
                Group::make('reservation_customer_group')
                    ->label('Osoba')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (Reservation $record): string => $list->groupTitle($record))
                    ->getDescriptionFromRecordUsing(fn (Reservation $record) => $list->groupDescription($record))
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderByRaw('LOWER('.$list->nameExpression().') '.$direction)->orderByRaw($list->keyExpression().' '.$direction))
                    ->scopeQueryByKeyUsing(fn (Builder $query, string $key): Builder => $query->whereRaw($list->keyExpression().' = ?', [$key]))
                    ->groupQueryUsing(fn ($query) => $query->groupByRaw($list->keyExpression()))
                    ->collapsible(),
            ])
            ->defaultGroup('reservation_customer_group')
            ->collapsedGroupsByDefault()
            ->groupingSettingsHidden()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->pluralModelLabel('grupa osoba')
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('customer_display_name')
                    ->label('Korisnik/gost')
                    ->state(fn (Reservation $record): string => $record->customer_display_name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $list->search($query, $search)),
                TextColumn::make('customer_display_phone')
                    ->label('Telefon')
                    ->state(fn (Reservation $record): ?string => $record->customer_display_phone)
                    ->toggleable(),
                TextColumn::make('sport.name')->label('Sport')->badge(),
                TextColumn::make('court.name')->label('Teren')->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Reservation $record): string => $record->display_status),
                TextColumn::make('starts_at')->label('Pocetak')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('Kraj')->dateTime('d.m.Y H:i'),
                TextColumn::make('participants_count')->counts('participants')->label('Ucesnika'),
                TextColumn::make('total_price')->label('Ukupno')->money('RSD', divideBy: 1)->sortable(),
                TextColumn::make('series_id')->label('Ponavljanje')->formatStateUsing(fn ($state) => filled($state) ? 'Nedeljna serija' : '')->badge()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('record_state')->label('Evidencija')
                    ->options(['active' => 'Postojeće rezervacije', 'deleted' => 'Obrisane rezervacije', 'all' => 'Sve, uključujući obrisane'])
                    ->default('active')
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? 'active') {
                        'deleted' => $query->whereNotNull('reservations.deleted_at'),
                        'all' => $query,
                        default => $query->whereNull('reservations.deleted_at'),
                    }),
                SelectFilter::make('status')->label('Status')->options(collect(ReservationStatus::cases())->mapWithKeys(fn (ReservationStatus $status) => [$status->value => $status->label()])->all()),
                SelectFilter::make('is_gift')->label('Poklon')->options(['yes' => 'Poklonjeni termini', 'no' => 'Ostali termini'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'yes' => $query->where('is_gift', true),
                        'no' => $query->where('is_gift', false),
                        default => $query,
                    }),
                SelectFilter::make('sport')->label('Sport')->relationship('sport', 'name')->searchable()->preload(),
                SelectFilter::make('time_state')->label('Vreme termina')
                    ->options(['future' => 'Budući termini', 'ongoing' => 'U toku', 'completed' => 'Odigrani termini'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'future' => $query->where('status', ReservationStatus::Reserved->value)->where('starts_at', '>=', now()),
                        'ongoing' => $query->where('status', ReservationStatus::Reserved->value)->where('starts_at', '<', now())->where('ends_at', '>', now()),
                        'completed' => $query->where('status', ReservationStatus::Reserved->value)->where('ends_at', '<=', now()),
                        default => $query,
                    }),
                Filter::make('period')
                    ->label('Period')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('until')->label('Do'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($builder, $date) => $builder->whereDate('starts_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($builder, $date) => $builder->whereDate('starts_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('history')->label('Istorija')->icon(Heroicon::OutlinedClock)
                    ->modalHeading(fn (Reservation $record): string => 'Istorija rezervacije #'.$record->id)
                    ->modalWidth(Width::FiveExtraLarge)->modalSubmitAction(false)->modalCancelActionLabel('Zatvori')
                    ->modalContent(fn (Reservation $record) => view('filament.reservations.history', ['audits' => $record->audits()->get()])),
                Action::make('restore')->label('Vrati rezervaciju')->icon(Heroicon::OutlinedArrowUturnLeft)->color('success')
                    ->visible(fn (Reservation $record): bool => $record->trashed() && static::canRestore($record))
                    ->requiresConfirmation()->modalHeading('Vratiti obrisanu rezervaciju?')
                    ->modalDescription('Vraća se sačuvana rezervacija sa istom cenom i opremom. Vraćanje rezervisanog termina nije moguće ako je teren zauzet ili oprema nije dostupna.')
                    ->schema([TextInput::make('reservation')->label('Termin')->disabled()
                        ->default(fn (Reservation $record): string => '#'.$record->id.' · '.$record->customer_display_name.' · '.$record->starts_at->format('d.m.Y H:i'))])
                    ->action(function (Reservation $record, $livewire): void {
                        try {
                            DB::transaction(function () use ($record): void {
                                Reservation::withTrashed()->lockForUpdate()->findOrFail($record->id)->restore();
                            });
                        } catch (ValidationException $exception) {
                            $path = $livewire->{$livewire->getMountedActionSchemaName()}->getStatePath();
                            throw ValidationException::withMessages([$path.'.reservation' => collect($exception->errors())->flatten()->implode(' ')]);
                        }
                        Notification::make()->title('Rezervacija je vraćena.')->success()->send();
                    }),
                Action::make('ucesnici')
                    ->label('Ucesnici')
                    ->icon(Heroicon::OutlinedUsers)
                    ->color('info')
                    ->visible(fn (Reservation $record): bool => static::canEdit($record))
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('participant_ids')
                            ->label('Korisnici u terminu')
                            ->multiple()
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->default(fn (Reservation $record): array => $record->participants()->pluck('users.id')->all())
                            ->helperText('Nosioc rezervacije se automatski zadrzava kao ucesnik termina.'),
                    ])
                    ->action(function (Reservation $record, array $data): void {
                        app(ReservationParticipantService::class)->syncUsers($record, $data['participant_ids'] ?? []);

                        Notification::make()
                            ->title('Ucesnici termina su sacuvani.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->modalWidth(Width::Screen)
                    ->databaseTransaction()
                    ->before(function (Reservation $record, array $data, $livewire): void {
                        if (($data['status'] ?? null) !== ReservationStatus::Reserved->value) {
                            return;
                        }

                        $court = Court::query()->lockForUpdate()->findOrFail($data['court_id']);
                        $schema = $livewire->{$livewire->getMountedActionSchemaName()};
                        $items = array_values($schema->getRawState()['equipmentItems'] ?? []);
                        try {
                            app(ReservationPricingService::class)->hydrateEquipmentPricing(
                                $items,
                                $court->sport_id,
                                Carbon::parse($data['starts_at']),
                                Carbon::parse($data['ends_at']),
                                $record->id,
                            );
                        } catch (RuntimeException $exception) {
                            throw ValidationException::withMessages([
                                $schema->getStatePath().'.equipmentItems' => $exception->getMessage(),
                            ]);
                        }
                    }),
                Action::make('gift')->label('Pokloni termin')->icon(Heroicon::OutlinedGift)->color('info')
                    ->visible(fn (Reservation $record): bool => ! $record->is_gift && $record->status === ReservationStatus::Reserved && static::canEdit($record))
                    ->requiresConfirmation()->modalHeading('Pokloniti ovaj termin?')
                    ->modalDescription('Cena ove rezervacije sa opremom postaje 0 RSD i uklanja se iz komercijalne statistike. Termin ostaje zauzet. Ostali termini iz serije se ne menjaju.')
                    ->databaseTransaction()
                    ->action(function (Reservation $record): void {
                        $record = Reservation::query()->lockForUpdate()->findOrFail($record->id);
                        if ($record->status !== ReservationStatus::Reserved) {
                            throw ValidationException::withMessages(['is_gift' => 'Otkazan termin nije moguće pokloniti. Osvežite listu rezervacija.']);
                        }
                        $record->update(['is_gift' => true]);
                        Notification::make()->title('Termin je označen kao poklon — 0 RSD.')->success()->send();
                    }),
                Action::make('cancel_one')->label('Otkazi termin')->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (Reservation $record) => $record->status === ReservationStatus::Reserved && static::canEdit($record))
                    ->requiresConfirmation()->modalHeading('Otkazati samo ovaj termin?')
                    ->modalDescription('Ostali termini iz serije ostaju rezervisani.')
                    ->action(function (Reservation $record): void {
                        $record->update(['status' => ReservationStatus::Cancelled, 'cancellation_reason' => 'Otkazano od administratora.']);
                        Notification::make()->title('Termin je otkazan.')->success()->send();
                    }),
                Action::make('cancel_series')->label('Otkazi preostalu seriju')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn (Reservation $record) => filled($record->series_id) && static::canEdit($record))
                    ->requiresConfirmation()->modalHeading('Otkazati sve preostale termine ove serije?')
                    ->modalDescription('Otkazuju se svi termini ove serije koji jos nisu poceli. Prosli termini ostaju sacuvani.')
                    ->action(function (Reservation $record): void {
                        $count = app(AdminReservationService::class)->cancelRemaining($record);
                        Notification::make()->title("Otkazano termina: {$count}")->success()->send();
                    }),
                DeleteAction::make()->label('Obriši')->databaseTransaction()
                    ->modalHeading('Obrisati rezervaciju?')
                    ->modalDescription('Termin će biti oslobođen, a rezervacija sačuvana u obrisanim terminima za kasnije vraćanje. Šalje se mejl obaveštenje o brisanju.'),
            ])
            ->headerActions([
                Action::make('calendar_view')
                    ->label('Kalendar')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->url(fn (): string => static::getUrl('calendar')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->databaseTransaction()
                        ->modalDescription('Izabrane rezervacije prelaze u obrisane termine i mogu da se vrate. Za svaku se šalje mejl obaveštenje o brisanju.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReservations::route('/'),
            'calendar' => Pages\CalendarReservations::route('/calendar'),
        ];
    }
}
