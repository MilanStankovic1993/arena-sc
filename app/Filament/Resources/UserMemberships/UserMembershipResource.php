<?php

namespace App\Filament\Resources\UserMemberships;

use App\Filament\Resources\UserMemberships\Pages\ManageUserMemberships;
use App\Models\MembershipPlan;
use App\Models\UserMembership;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class UserMembershipResource extends Resource
{
    protected static ?string $model = UserMembership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Članarine korisnika';

    protected static string|UnitEnum|null $navigationGroup = 'Korisnici i analitika';

    protected static ?string $modelLabel = 'Članarina korisnika';

    protected static ?string $pluralModelLabel = 'Članarine korisnika';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Članarina korisnika')->schema([
                Select::make('user_id')
                    ->label('Korisnik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(6),
                Select::make('membership_plan_id')
                    ->label('Članarina')
                    ->relationship(
                        'membershipPlan',
                        'name',
                        fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('price')
                    )
                    ->getOptionLabelFromRecordUsing(fn (MembershipPlan $record): string => sprintf(
                        '%s%s - %s RSD',
                        $record->name,
                        $record->sport ? ' ('.$record->sport->name.')' : ' (svi sportovi)',
                        number_format((float) $record->price, 0, ',', '.'),
                    ))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(6),
                DatePicker::make('starts_at')
                    ->label('Vazi od')
                    ->default(now()->toDateString())
                    ->required()
                    ->columnSpan(4),
                DatePicker::make('ends_at')
                    ->label('Vazi do')
                    ->required()
                    ->columnSpan(4),
                Toggle::make('is_active')
                    ->label('Aktivna')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(4),
                Textarea::make('notes')
                    ->label('Napomena')
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
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')->label('Korisnik')->searchable()->sortable(),
                TextColumn::make('membershipPlan.name')->label('Članarina')->searchable()->badge(),
                TextColumn::make('membershipPlan.sport.name')->label('Sport')->placeholder('Svi sportovi'),
                TextColumn::make('starts_at')->label('Vazi od')->date('d.m.Y')->sortable(),
                TextColumn::make('ends_at')->label('Vazi do')->date('d.m.Y')->sortable(),
                TextColumn::make('membershipPlan.reservation_limit')->label('Ukupno termina')->suffix(' termina'),
                IconColumn::make('is_active')->label('Aktivna')->boolean(),
            ])
            ->filters([
                SelectFilter::make('membership_plan_id')
                    ->label('Članarina')
                    ->relationship('membershipPlan', 'name'),
                SelectFilter::make('user_id')
                    ->label('Korisnik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('ends_at', 'desc')
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
            'index' => ManageUserMemberships::route('/'),
        ];
    }
}
