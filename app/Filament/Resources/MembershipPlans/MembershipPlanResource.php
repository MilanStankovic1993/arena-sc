<?php

namespace App\Filament\Resources\MembershipPlans;

use App\Filament\Resources\MembershipPlans\Pages\ManageMembershipPlans;
use App\Models\MembershipPlan;
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
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MembershipPlanResource extends Resource
{
    protected static ?string $model = MembershipPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Članarine';

    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';

    protected static ?string $modelLabel = 'Članarina';

    protected static ?string $pluralModelLabel = 'Članarine';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Članarina')->schema([
                Select::make('sport_id')
                    ->label('Sport')
                    ->relationship('sport', 'name')
                    ->helperText('Ostavi prazno ako članarina vazi za sve sportove.')
                    ->searchable()
                    ->preload()
                    ->columnSpan(3),
                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->columnSpan(5),
                TextInput::make('period_label')
                    ->label('Period')
                    ->placeholder('Mesec dana, sezona, 10 termina...')
                    ->columnSpan(4),
                TextInput::make('duration_days')
                    ->label('Trajanje u danima')
                    ->numeric()
                    ->minValue(1)
                    ->columnSpan(3),
                TextInput::make('reservation_limit')
                    ->label('Ukupno termina u clanarini')
                    ->helperText('Ukupan broj termina koje korisnik moze da rezervise tokom celog trajanja clanarine.')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(1)
                    ->columnSpan(3),
                TextInput::make('price')
                    ->label('Cena')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('RSD')
                    ->required()
                    ->columnSpan(3),
                TextInput::make('sort_order')
                    ->label('Redosled')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label('Aktivna članarina')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1),
                Textarea::make('short_description')
                    ->label('Kratak opis')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Opis')
                    ->rows(4)
                    ->columnSpanFull(),
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
                TextColumn::make('name')->label('Članarina')->searchable()->sortable(),
                TextColumn::make('sport.name')->label('Sport')->placeholder('Svi sportovi')->badge(),
                TextColumn::make('period_label')->label('Period')->placeholder('-'),
                TextColumn::make('duration_days')->label('Dana')->suffix(' dana')->placeholder('-')->sortable(),
                TextColumn::make('reservation_limit')->label('Ukupno termina')->suffix(' termina')->sortable(),
                TextColumn::make('price')->label('Cena')->money('RSD', divideBy: 1)->sortable(),
                IconColumn::make('is_active')->label('Aktivna')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sport')->relationship('sport', 'name'),
            ])
            ->defaultSort('sort_order')
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
            'index' => ManageMembershipPlans::route('/'),
        ];
    }
}
