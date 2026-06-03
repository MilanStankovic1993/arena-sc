<?php

namespace App\Filament\Resources\Sports;

use App\Filament\Resources\Sports\Pages\ManageSports;
use App\Models\Sport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SportResource extends Resource
{
    protected static ?string $model = Sport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Sportovi';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'Sport';

    protected static ?string $pluralModelLabel = 'Sportovi';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Osnovni podaci')->schema([
                TextInput::make('name')->label('Naziv')->required()->live(onBlur: true),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Textarea::make('short_description')->label('Kratak opis')->rows(2),
                Textarea::make('description')->label('Opis')->rows(5)->columnSpanFull(),
                FileUpload::make('cover_image')->label('Naslovna slika')->image()->disk('public')->directory('sports'),
                TextInput::make('sort_order')->label('Redosled')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktivan sport')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Sport')->searchable()->sortable(),
                TextColumn::make('courts_count')->label('Terena')->counts('courts'),
                TextColumn::make('equipment_count')->label('Artikala')->counts('equipment'),
                IconColumn::make('is_active')->label('Aktivan')->boolean(),
            ])
            ->filters([])
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
            'index' => ManageSports::route('/'),
        ];
    }
}
