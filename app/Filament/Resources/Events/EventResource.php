<?php

namespace App\Filament\Resources\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('title')->label('Naziv')->required()->live(onBlur: true),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
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
                FileUpload::make('cover_image')->label('Naslovna slika')->image()->disk('public')->directory('events'),
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
                    ->formatStateUsing(fn (?string $state): string => EventType::tryFrom($state ?? '')?->label() ?? (string) $state),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => EventStatus::tryFrom($state ?? '')?->label() ?? (string) $state),
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
