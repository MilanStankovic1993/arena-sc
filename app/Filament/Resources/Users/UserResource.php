<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\UserStatisticsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Korisnici';

    protected static string|UnitEnum|null $navigationGroup = 'Korisnici i analitika';

    protected static ?string $modelLabel = 'Korisnik';

    protected static ?string $pluralModelLabel = 'Korisnici';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Korisnicki profil')->schema([
                TextInput::make('name')->label('Ime i prezime')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Telefon'),
                Select::make('role')
                    ->label('Uloga')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])->all())
                    ->required(),
                DatePicker::make('registered_at')->label('Datum registracije'),
                Textarea::make('notes')->label('Napomena')->rows(4)->columnSpanFull(),
            ])->columns(2),
            Section::make('Statistika korisnika')
                ->visible(fn (?User $record): bool => filled($record))
                ->schema([
                    Placeholder::make('stats_total')
                        ->label('Ukupno rezervacija')
                        ->content(fn (?User $record): string => number_format(static::stats($record)['total'] ?? 0, 0, ',', '.')),
                    Placeholder::make('stats_revenue')
                        ->label('Ukupna potrosnja')
                        ->content(fn (?User $record): string => static::money(static::stats($record)['revenue'] ?? 0)),
                    Placeholder::make('stats_average')
                        ->label('Prosecna cena')
                        ->content(fn (?User $record): string => static::money(static::stats($record)['averageSpend'] ?? 0)),
                    Placeholder::make('stats_cancel_rate')
                        ->label('Stopa otkazivanja')
                        ->content(fn (?User $record): string => number_format(static::stats($record)['cancellationRate'] ?? 0, 1, ',', '.') . '%'),
                    Placeholder::make('stats_last')
                        ->label('Poslednji termin')
                        ->content(fn (?User $record): string => static::dateTime(static::stats($record)['lastReservationAt'] ?? null)),
                    Placeholder::make('stats_favorite_sport')
                        ->label('Najcesci sport')
                        ->content(fn (?User $record): string => static::stats($record)['favoriteSport'] ?? 'Nema podataka'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Korisnik')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon'),
                TextColumn::make('role')->label('Uloga')->badge(),
                TextColumn::make('total_reservations')->label('Rezervacije')->sortable(),
                TextColumn::make('cancelled_reservations')->label('Otkazivanja')->sortable(),
                TextColumn::make('last_reservation_at')->label('Poslednji termin')->dateTime()->sortable(),
                TextColumn::make('reservations_sum_total_price')->label('Ukupna potrosnja')->sum('reservations', 'total_price')->money('RSD', divideBy: 1),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
                    UserRole::Customer->value => UserRole::Customer->label(),
                ]),
                Filter::make('active_period')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('until')->label('Do'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($builder, $date) => $builder->whereHas('reservations', fn ($reservations) => $reservations->whereDate('starts_at', '>=', $date)))
                            ->when($data['until'] ?? null, fn ($builder, $date) => $builder->whereHas('reservations', fn ($reservations) => $reservations->whereDate('starts_at', '<=', $date)));
                    }),
            ])
            ->recordActions([
                Action::make('statistika')
                    ->label('Statistika')
                    ->icon(Heroicon::OutlinedChartBarSquare)
                    ->color('info')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn (User $record): string => 'Statistika korisnika - ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zatvori')
                    ->modalContent(fn (User $record): View => view('filament.resources.users.statistics', [
                        'record' => $record,
                        'stats' => static::stats($record),
                    ])),
                EditAction::make()->modalWidth(Width::Screen),
                DeleteAction::make()->visible(fn (User $record): bool => $record->role !== UserRole::SuperAdmin),
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
            'index' => ManageUsers::route('/'),
        ];
    }

    protected static function stats(?User $record): array
    {
        if (! $record) {
            return [];
        }

        return app(UserStatisticsService::class)->summary($record);
    }

    protected static function money(float|int $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . ' RSD';
    }

    protected static function dateTime($value): string
    {
        return $value?->format('d.m.Y H:i') ?? 'Nema podataka';
    }
}
