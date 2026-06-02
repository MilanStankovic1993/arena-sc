<?php

namespace App\Filament\Resources\Reservations;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Models\Reservation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Termin')->schema([
                Select::make('user_id')->label('Korisnik')->relationship('user', 'name')->required()->searchable()->preload(),
                Select::make('sport_id')->label('Sport')->relationship('sport', 'name')->required()->searchable()->preload(),
                Select::make('court_id')->label('Teren')->relationship('court', 'name')->required()->searchable()->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(ReservationStatus::cases())->mapWithKeys(fn (ReservationStatus $status) => [$status->value => $status->label()])->all())
                    ->required()
                    ->default(ReservationStatus::Reserved->value),
                DateTimePicker::make('starts_at')->label('Pocetak')->required(),
                DateTimePicker::make('ends_at')->label('Kraj')->required(),
                TextInput::make('duration_minutes')->label('Trajanje (min)')->numeric()->default(60)->required(),
                TextInput::make('players_count')->label('Broj igraca')->numeric(),
                TextInput::make('court_price')->label('Cena terena')->numeric()->prefix('RSD')->required(),
                TextInput::make('equipment_price')->label('Cena opreme')->numeric()->prefix('RSD')->required(),
                TextInput::make('total_price')->label('Ukupno')->numeric()->prefix('RSD')->required(),
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
                        TextInput::make('quantity')->label('Kolicina')->numeric()->default(1)->required(),
                        TextInput::make('unit_price')->label('Cena po komadu')->numeric()->prefix('RSD')->required(),
                        TextInput::make('line_total')->label('Ukupno')->numeric()->prefix('RSD')->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')->label('Korisnik')->searchable(),
                TextColumn::make('sport.name')->label('Sport')->badge(),
                TextColumn::make('court.name')->label('Teren')->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus|string|null $state): string => $state instanceof ReservationStatus ? $state->label() : (ReservationStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('starts_at')->label('Pocetak')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Kraj')->dateTime(),
                TextColumn::make('total_price')->label('Ukupno')->money('RSD', divideBy: 1)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(ReservationStatus::cases())->mapWithKeys(fn (ReservationStatus $status) => [$status->value => $status->label()])->all()),
                SelectFilter::make('court')->relationship('court', 'name'),
                Filter::make('period')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Od'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Do'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($builder, $date) => $builder->whereDate('starts_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($builder, $date) => $builder->whereDate('starts_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                EditAction::make()->modalWidth(Width::Screen),
                DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('calendar_view')
                    ->label('Kalendar')
                    ->icon('heroicon-o-calendar')
                    ->color('warning') 
                    ->url(fn (): string => static::getUrl('calendar')), 
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
