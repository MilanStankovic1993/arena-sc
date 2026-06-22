<?php

namespace App\Filament\Resources\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Models\Event;
use App\Services\EventStatisticsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Dogadjaji';

    protected static string|UnitEnum|null $navigationGroup = 'Dogadjaji';

    protected static ?string $modelLabel = 'Dogadjaj';

    protected static ?string $pluralModelLabel = 'Dogadjaji';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Osnovni podaci')->schema([
                TextInput::make('title')->label('Naziv')->required(),
                Select::make('type')
                    ->label('Tip')
                    ->options(collect(EventType::cases())->mapWithKeys(fn (EventType $type) => [$type->value => $type->label()])->all())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(collect(EventStatus::cases())->mapWithKeys(fn (EventStatus $status) => [$status->value => $status->label()])->all())
                    ->required(),
                TextInput::make('location')->label('Lokacija'),
                TextInput::make('cta_label')->label('CTA tekst'),
                DatePicker::make('start_date')->label('Pocetak'),
                DatePicker::make('end_date')->label('Kraj'),
                FileUpload::make('cover_image')
                    ->label('Naslovna slika')
                    ->image()
                    ->acceptedFileTypes(['image/avif', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1600')
                    ->imageResizeTargetHeight('1200')
                    ->imageResizeUpscale(false)
                    ->disk('public')
                    ->directory('events'),
                Toggle::make('is_featured')->label('Izdvoji na sajtu'),
                Textarea::make('summary')->label('Kratak opis')->rows(3)->columnSpanFull(),
                Textarea::make('description')->label('Opis')->rows(5)->columnSpanFull(),
                Textarea::make('rules')->label('Pravila')->rows(6)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('title')->label('Dogadjaj')->searchable()->sortable(),
                TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof EventType
                        ? $state->label()
                        : (EventType::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof EventStatus
                        ? $state->label()
                        : (EventStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('start_date')->label('Pocetak')->date(),
                TextColumn::make('entries_count')->label('Timovi')->counts('entries'),
                TextColumn::make('matches_count')->label('Mecevi')->counts('matches'),
                IconColumn::make('is_featured')->label('Izdvojen')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    EventType::Tournament->value => EventType::Tournament->label(),
                    EventType::League->value => EventType::League->label(),
                ]),
                SelectFilter::make('status')->options([
                    EventStatus::Draft->value => EventStatus::Draft->label(),
                    EventStatus::Registration->value => EventStatus::Registration->label(),
                    EventStatus::Ongoing->value => EventStatus::Ongoing->label(),
                    EventStatus::Completed->value => EventStatus::Completed->label(),
                ]),
            ])
            ->recordActions([
                Action::make('generateLeague')
                    ->label('Generisi ligu')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->visible(fn (Event $record): bool => $record->type === EventType::League)
                    ->requiresConfirmation()
                    ->modalHeading('Generisi raspored lige')
                    ->modalDescription('Kreirace se raspored "svako sa svakim" za sve unete ucesnike, samo ako dogadjaj jos nema meceve.')
                    ->action(function (Event $record, EventStatisticsService $statisticsService): void {
                        $created = $statisticsService->generateLeagueSchedule($record);

                        Notification::make()
                            ->title($created > 0 ? "Generisano {$created} meceva." : 'Raspored nije generisan.')
                            ->body($created > 0 ? 'Liga je automatski popunjena rasporedom po kolima.' : 'Proveri da li postoje bar 2 ucesnika i da li dogadjaj vec nema meceve.')
                            ->success()
                            ->send();
                    }),
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
            'index' => ManageEvents::route('/'),
        ];
    }
}
