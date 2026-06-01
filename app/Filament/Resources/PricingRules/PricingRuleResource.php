<?php

namespace App\Filament\Resources\PricingRules;

use App\Filament\Resources\PricingRules\Pages\ManagePricingRules;
use App\Models\PricingRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Cenovnici termina';

    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function daysOfWeekOptions(): array
    {
        return [
            1 => 'Pon',
            2 => 'Uto',
            3 => 'Sre',
            4 => 'Cet',
            5 => 'Pet',
            6 => 'Sub',
            0 => 'Ned',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pravilo cene')->schema([
                Select::make('sport_id')
                    ->label('Sport')
                    ->relationship('sport', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(3),
                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->columnSpan(9),
                CheckboxList::make('days_of_week')
                    ->label('Dani u nedelji')
                    ->options(static::daysOfWeekOptions())
                    ->columns(7)
                    ->gridDirection('row')
                    ->helperText('Ako nista ne oznacis, pravilo vazi za sve dane.')
                    ->columnSpanFull(),
                TimePicker::make('start_time')->label('Od')->seconds(false)->required()->columnSpan(2),
                TimePicker::make('end_time')->label('Do')->seconds(false)->required()->columnSpan(2),
                Toggle::make('is_active')->label('Aktivno pravilo')->default(true)->inline(false)->columnSpan(2),
                DatePicker::make('valid_from')->label('Vazi od')->columnSpan(3),
                DatePicker::make('valid_to')->label('Vazi do')->columnSpan(3),
                TextInput::make('price_60')->label('Cena za 1h')->numeric()->prefix('RSD')->required()->columnSpan(4),
                TextInput::make('price_90')->label('Cena za 1,5h')->numeric()->prefix('RSD')->required()->columnSpan(4),
                TextInput::make('price_120')->label('Cena za 2h')->numeric()->prefix('RSD')->required()->columnSpan(4),
            ])
                ->columns(12)
                ->columnSpanFull()
                ->maxWidth(Width::Full),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Pravilo')->searchable(),
                TextColumn::make('sport.name')->label('Sport')->badge(),
                TextColumn::make('days_label')->label('Dani')->wrap(),
                TextColumn::make('start_time')->label('Od'),
                TextColumn::make('end_time')->label('Do'),
                TextColumn::make('price_60')->label('1h')->money('RSD', divideBy: 1),
                TextColumn::make('price_90')->label('1,5h')->money('RSD', divideBy: 1),
                TextColumn::make('price_120')->label('2h')->money('RSD', divideBy: 1),
                IconColumn::make('is_active')->label('Aktivno')->boolean(),
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
            'index' => ManagePricingRules::route('/'),
        ];
    }
}
