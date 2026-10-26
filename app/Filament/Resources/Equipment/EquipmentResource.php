<?php

namespace App\Filament\Resources\Equipment;

use App\Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Equipment\Pages\ManageEquipment;
use App\Models\Equipment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Oprema';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'Artikal';

    protected static ?string $pluralModelLabel = 'Oprema';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Artikal')->schema([
                Select::make('sport_id')->label('Sport')->relationship('sport', 'name')->searchable()->preload(),
                TextInput::make('name')->label('Naziv')->required()->maxLength(255),
                TextInput::make('sku')->label('Sifra artikla')->maxLength(255)->unique(ignoreRecord: true),
                FileUpload::make('image')
                    ->label('Slika')
                    ->image()
                    ->acceptedFileTypes(['image/avif', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1600')
                    ->imageResizeTargetHeight('1200')
                    ->imageResizeUpscale(false)
                    ->disk('public')
                    ->directory('equipment'),
                Textarea::make('short_description')->label('Kratak opis')->maxLength(255)->rows(2)->columnSpanFull(),
                Textarea::make('description')->label('Opis')->rows(4)->columnSpanFull(),
                TextInput::make('rental_price')->label('Cena iznajmljivanja')->numeric()->minValue(0)->maxValue(99999999.99)->prefix('RSD')
                    ->visible(fn (Get $get): bool => (bool) $get('is_rentable'))
                    ->required(fn (Get $get): bool => (bool) $get('is_rentable'))
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn ($state, Get $get) => $get('is_rentable') ? $state : 0),
                TextInput::make('sale_price')->label('Prodajna cena')->numeric()->minValue(0)->maxValue(99999999.99)->prefix('RSD')
                    ->visible(fn (Get $get): bool => (bool) $get('is_sellable'))
                    ->required(fn (Get $get): bool => (bool) $get('is_sellable'))
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn ($state, Get $get) => $get('is_sellable') ? $state : 0),
                TextInput::make('stock_quantity')->label('Stanje na lageru')->integer()->minValue(0)->maxValue(4294967295)->default(0)
                    ->dehydrateStateUsing(fn ($state) => blank($state) ? 0 : $state),
                Toggle::make('is_rentable')->label('Moze iznajmljivanje')->default(true)->live(),
                Toggle::make('is_sellable')->label('Moze prodaja')->default(false)->live(),
                Toggle::make('is_active')->label('Aktivan artikal')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Artikal')->searchable()->sortable(),
                TextColumn::make('sport.name')->label('Sport')->badge(),
                TextColumn::make('stock_quantity')->label('Lager')->sortable(),
                TextColumn::make('rental_price')->label('Iznajmljivanje')->money('RSD', divideBy: 1),
                TextColumn::make('sale_price')->label('Prodaja')->money('RSD', divideBy: 1),
                IconColumn::make('is_rentable')->label('Rent')->boolean(),
                IconColumn::make('is_sellable')->label('Prodaja')->boolean(),
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
            'index' => ManageEquipment::route('/'),
        ];
    }
}
