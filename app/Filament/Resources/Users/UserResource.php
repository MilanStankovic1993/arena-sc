<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Korisnici';

    protected static string|UnitEnum|null $navigationGroup = 'Korisnici i analitika';

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
                EditAction::make(),
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
}
