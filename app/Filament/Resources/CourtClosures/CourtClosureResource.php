<?php

namespace App\Filament\Resources\CourtClosures;

use App\Filament\Resources\CourtClosures\Pages\ManageCourtClosures;
use App\Models\CourtClosure;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class CourtClosureResource extends Resource
{
    protected static ?string $model = CourtClosure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Blokade terena';

    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';

    protected static ?string $modelLabel = 'Blokada terena';

    protected static ?string $pluralModelLabel = 'Blokade terena';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Blokada termina')->schema([
                Select::make('court_id')->label('Teren')->relationship('court', 'name')->required()->searchable()->preload(),
                TextInput::make('title')->label('Naziv blokade')->required(),
                DateTimePicker::make('starts_at')->label('Pocetak')->required(),
                DateTimePicker::make('ends_at')->label('Kraj')->required(),
                Textarea::make('reason')->label('Razlog')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktivna blokada')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Naziv')->searchable(),
                TextColumn::make('court.name')->label('Teren')->badge(),
                TextColumn::make('starts_at')->label('Od')->dateTime(),
                TextColumn::make('ends_at')->label('Do')->dateTime(),
                IconColumn::make('is_active')->label('Aktivna')->boolean(),
            ])
            ->filters([
                SelectFilter::make('court')->relationship('court', 'name'),
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
            'index' => ManageCourtClosures::route('/'),
        ];
    }
}
