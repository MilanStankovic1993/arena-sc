<?php

namespace App\Filament\Resources\EventEntries;

use App\Filament\Resources\EventEntries\Pages\ManageEventEntries;
use App\Models\EventEntry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                TextInput::make('team_name')->label('Naziv tima / para')->required(),
                TextInput::make('contact_name')->label('Kontakt osoba'),
                TextInput::make('contact_phone')->label('Telefon'),
                TextInput::make('played')->label('Odigrano')->numeric()->default(0),
                TextInput::make('wins')->label('Pobede')->numeric()->default(0),
                TextInput::make('losses')->label('Porazi')->numeric()->default(0),
                TextInput::make('points')->label('Poeni')->numeric()->default(0),
                TextInput::make('score_for')->label('Osvojeni gemovi/poeni')->numeric()->default(0),
                TextInput::make('score_against')->label('Primljeni gemovi/poeni')->numeric()->default(0),
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
                TextColumn::make('event.title')->label('Dogadjaj')->badge(),
                TextColumn::make('played')->label('P'),
                TextColumn::make('wins')->label('W'),
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
