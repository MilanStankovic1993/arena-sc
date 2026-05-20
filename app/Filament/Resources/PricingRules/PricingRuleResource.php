<?php

namespace App\Filament\Resources\PricingRules;

use App\Filament\Resources\PricingRules\Pages\ManagePricingRules;
use App\Models\PricingRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Cenovnici termina';

    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pravilo cene')->schema([
                Select::make('court_id')->label('Teren')->relationship('court', 'name')->required()->searchable()->preload(),
                TextInput::make('name')->label('Naziv')->required(),
                Select::make('day_of_week')
                    ->label('Dan u nedelji')
                    ->options([
                        0 => 'Nedelja',
                        1 => 'Ponedeljak',
                        2 => 'Utorak',
                        3 => 'Sreda',
                        4 => 'Cetvrtak',
                        5 => 'Petak',
                        6 => 'Subota',
                    ]),
                TimePicker::make('start_time')->label('Od')->seconds(false)->required(),
                TimePicker::make('end_time')->label('Do')->seconds(false)->required(),
                TextInput::make('price')->label('Cena')->numeric()->prefix('RSD')->required(),
                DatePicker::make('valid_from')->label('Vazi od'),
                DatePicker::make('valid_to')->label('Vazi do'),
                Toggle::make('is_active')->label('Aktivno pravilo')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Pravilo')->searchable(),
                TextColumn::make('court.name')->label('Teren')->badge(),
                TextColumn::make('day_of_week')->label('Dan'),
                TextColumn::make('start_time')->label('Od'),
                TextColumn::make('end_time')->label('Do'),
                TextColumn::make('price')->label('Cena')->money('RSD', divideBy: 1),
                IconColumn::make('is_active')->label('Aktivno')->boolean(),
            ])
            ->filters([
                SelectFilter::make('court')->relationship('court', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ManagePricingRules::route('/'),
        ];
    }
}
