<?php

namespace App\Filament\Resources\EventMatches;

use App\Filament\Resources\EventMatches\Pages\ManageEventMatches;
use App\Models\EventEntry;
use App\Models\EventMatch;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EventMatchResource extends Resource
{
    protected static ?string $model = EventMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Mecevi i rezultati';

    protected static string|UnitEnum|null $navigationGroup = 'Dogadjaji';

    protected static ?string $modelLabel = 'Mec / rezultat';

    protected static ?string $pluralModelLabel = 'Mecevi / rezultati';

    protected static ?string $recordTitleAttribute = 'round_label';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mec')->schema([
                Select::make('event_id')->label('Dogadjaj')->relationship('event', 'title')->required()->searchable()->preload()->live(),
                TextInput::make('round_label')->label('Kolo / faza'),
                DateTimePicker::make('scheduled_at')->label('Termin'),
                Select::make('status')->label('Status')->options([
                    'scheduled' => 'Zakazan',
                    'finished' => 'Zavrsen',
                    'cancelled' => 'Otkazan',
                ])->required(),
                Select::make('home_entry_id')
                    ->label('Domacin')
                    ->options(fn (Get $get): array => EventEntry::query()
                        ->where('event_id', $get('event_id'))
                        ->orderBy('team_name')
                        ->pluck('team_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Select::make('away_entry_id')
                    ->label('Gost')
                    ->options(fn (Get $get): array => EventEntry::query()
                        ->where('event_id', $get('event_id'))
                        ->orderBy('team_name')
                        ->pluck('team_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('home_score')->label('Rezultat domacin')->numeric()->minValue(0),
                TextInput::make('away_score')->label('Rezultat gost')->numeric()->minValue(0),
                Textarea::make('notes')->label('Napomena')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('round_label')
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('event.title')->label('Dogadjaj')->badge(),
                TextColumn::make('round_label')->label('Faza'),
                TextColumn::make('homeEntry.team_name')->label('Domacin'),
                TextColumn::make('awayEntry.team_name')->label('Gost'),
                TextColumn::make('scheduled_at')->label('Termin')->dateTime(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('score_line')
                    ->label('Rezultat')
                    ->state(fn (EventMatch $record): string => is_null($record->home_score) || is_null($record->away_score) ? '-' : "{$record->home_score} : {$record->away_score}"),
            ])
            ->filters([
                SelectFilter::make('event')->relationship('event', 'title'),
                SelectFilter::make('status')->options([
                    'scheduled' => 'Zakazan',
                    'finished' => 'Zavrsen',
                    'cancelled' => 'Otkazan',
                ]),
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
            'index' => ManageEventMatches::route('/'),
        ];
    }
}
