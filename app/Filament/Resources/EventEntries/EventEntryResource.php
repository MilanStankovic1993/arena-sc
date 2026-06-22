<?php

namespace App\Filament\Resources\EventEntries;

use App\Filament\Resources\EventEntries\Pages\ManageEventEntries;
use App\Models\EventEntry;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class EventEntryResource extends Resource
{
    protected static ?string $model = EventEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Ucesnici / timovi';

    protected static string|UnitEnum|null $navigationGroup = 'Dogadjaji';

    protected static ?string $modelLabel = 'Ucesnik / tim';

    protected static ?string $pluralModelLabel = 'Ucesnici / timovi';

    protected static ?string $recordTitleAttribute = 'team_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tim / par')->schema([
                Select::make('event_id')->label('Dogadjaj')->relationship('event', 'title')->required()->searchable()->preload(),
                Select::make('user_id')
                    ->label('Nosilac / korisnik')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('event_id', $get('event_id')),
                    )
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        $user = $state ? User::query()->find($state) : null;

                        if (! $user) {
                            return;
                        }

                        if (! $get('team_name')) {
                            $set('team_name', $user->name);
                        }

                        $set('contact_name', $user->name);
                        $set('contact_phone', $user->phone);
                    }),
                TextInput::make('team_name')
                    ->label('Naziv tima / para')
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('event_id', $get('event_id')),
                    )
                    ->helperText('Moze biti ime igraca ili naziv para/tima, ali mora biti vezan za registrovanog korisnika.'),
                TextInput::make('contact_name')->label('Kontakt osoba'),
                TextInput::make('contact_phone')->label('Telefon'),
                Hidden::make('played')->default(0),
                Hidden::make('wins')->default(0),
                Hidden::make('draws')->default(0),
                Hidden::make('losses')->default(0),
                Hidden::make('points')->default(0),
                Hidden::make('score_for')->default(0),
                Hidden::make('score_against')->default(0),
                Textarea::make('notes')->label('Napomene')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('team_name')
            ->columns([
                TextColumn::make('team_name')->label('Tim / par')->searchable(),
                TextColumn::make('user.name')->label('Korisnik')->searchable(),
                TextColumn::make('event.title')->label('Dogadjaj')->badge(),
                TextColumn::make('played')->label('P'),
                TextColumn::make('wins')->label('W'),
                TextColumn::make('draws')->label('D'),
                TextColumn::make('losses')->label('L'),
                TextColumn::make('points')->label('Pts')->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')->relationship('event', 'title'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth(Width::Screen),
                DeleteAction::make(),
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
            'index' => ManageEventEntries::route('/'),
        ];
    }
}
