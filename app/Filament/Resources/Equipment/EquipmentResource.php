<?php

namespace App\Filament\Resources\Equipment;

use App\Filament\Resources\Equipment\Pages\ManageEquipment;
use App\Models\Equipment;
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
                TextInput::make('name')->label('Naziv')->required(),
                TextInput::make('sku')->label('Sifra artikla')->unique(ignoreRecord: true),
                FileUpload::make('image')->label('Slika')->image()->disk('public')->directory('equipment'),
                Textarea::make('short_description')->label('Kratak opis')->rows(2)->columnSpanFull(),
                Textarea::make('description')->label('Opis')->rows(4)->columnSpanFull(),
                TextInput::make('rental_price')->label('Cena iznajmljivanja')->numeric()->prefix('RSD'),
                TextInput::make('sale_price')->label('Prodajna cena')->numeric()->prefix('RSD'),
                TextInput::make('stock_quantity')->label('Stanje na lageru')->numeric()->default(0),
                Toggle::make('is_rentable')->label('Moze iznajmljivanje')->default(true),
                Toggle::make('is_sellable')->label('Moze prodaja')->default(false),
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
