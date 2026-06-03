<?php

namespace App\Filament\Resources\Courts;

use App\Filament\Resources\Courts\Pages\ManageCourts;
use App\Models\Court;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class CourtResource extends Resource
{
    protected static ?string $model = Court::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Tereni';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'Teren';

    protected static ?string $pluralModelLabel = 'Tereni';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Teren')->schema([
                Select::make('sport_id')->label('Sport')->relationship('sport', 'name')->required()->searchable()->preload(),
                TextInput::make('name')->label('Naziv')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('location')->label('Lokacija'),
                TextInput::make('surface')->label('Podloga'),
                TextInput::make('capacity')->label('Kapacitet')->numeric(),
                TextInput::make('base_price')->label('Osnovna cena (fallback)')->numeric()->prefix('RSD')->required(),
                FileUpload::make('image')->label('Slika')->image()->disk('public')->directory('courts'),
                Textarea::make('description')->label('Opis')->rows(4)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktivan teren')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Teren')->searchable()->sortable(),
                TextColumn::make('sport.name')->label('Sport')->badge(),
                TextColumn::make('location')->label('Lokacija'),
                TextColumn::make('base_price')->label('Fallback cena')->money('RSD', divideBy: 1),
                TextColumn::make('reservations_count')->label('Rezervacija')->counts('reservations'),
                IconColumn::make('is_active')->label('Aktivan')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sport')->relationship('sport', 'name'),
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
            'index' => ManageCourts::route('/'),
        ];
    }
}
