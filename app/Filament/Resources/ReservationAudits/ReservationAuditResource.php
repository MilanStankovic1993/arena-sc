<?php

namespace App\Filament\Resources\ReservationAudits;

use App\Filament\Resources\ReservationAudits\Pages\ListReservationAudits;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\ReservationAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReservationAuditResource extends Resource
{
    protected static ?string $model = ReservationAudit::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';
    protected static ?string $navigationLabel = 'Istorija rezervacija';
    protected static ?string $modelLabel = 'Promena rezervacije';
    protected static ?string $pluralModelLabel = 'Istorija rezervacija';

    public static function canViewAny(): bool
    {
        return ReservationResource::canViewAny();
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->description('Promene se beleže od uvođenja ove evidencije. Ranija brisanja nije moguće naknadno rekonstruisati.')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Kada')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('reservation_id')->label('Rezervacija #')->searchable()->sortable(),
                TextColumn::make('reservation.customer_display_name')->label('Korisnik/gost')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('reservation', fn ($r) => app(\App\Services\ReservationListService::class)->search($r, $search))),
                TextColumn::make('reservation.court.name')->label('Teren'),
                TextColumn::make('reservation.starts_at')->label('Termin')->dateTime('d.m.Y H:i'),
                TextColumn::make('event')->label('Radnja')->formatStateUsing(fn (ReservationAudit $record): string => $record->event_label)->badge(),
                TextColumn::make('actor_name')->label('Ko')->searchable(),
                TextColumn::make('actor_id')->label('Nalog #')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('event')->label('Radnja')->options([
                    'created' => 'Kreirana', 'updated' => 'Izmenjena', 'deleted' => 'Obrisana', 'restored' => 'Vraćena',
                    'participants' => 'Izmenjeni učesnici', 'equipment_added' => 'Dodata oprema',
                    'equipment_updated' => 'Izmenjena oprema', 'equipment_deleted' => 'Uklonjena oprema',
                ]),
                Filter::make('period')->label('Period')->schema([DatePicker::make('from')->label('Od'), DatePicker::make('until')->label('Do')])
                    ->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                Action::make('details')->label('Detalji')->modalHeading('Detalji promene')->modalWidth(Width::FiveExtraLarge)
                    ->modalSubmitAction(false)->modalCancelActionLabel('Zatvori')
                    ->modalContent(fn (ReservationAudit $record) => view('filament.reservations.history', ['audits' => collect([$record])])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReservationAudits::route('/')];
    }
}
