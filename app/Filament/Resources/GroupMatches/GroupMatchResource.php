<?php

namespace App\Filament\Resources\GroupMatches;

use App\Enums\UserRole;
use App\Filament\Resources\GroupMatches\Pages\ListGroupMatches;
use App\Models\GroupMatch;
use App\Services\GroupMatchService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class GroupMatchResource extends Resource
{
    protected static ?string $model = GroupMatch::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static string|UnitEnum|null $navigationGroup = 'Rezervacije';
    protected static ?string $navigationLabel = 'Grupni termini';
    protected static ?string $pluralModelLabel = 'Grupni termini';
    protected static ?string $slug = 'grupni-termini';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->where('status', '!=', GroupMatch::Draft)
            ->with(['sport', 'reservation', 'confirmedPlayers', 'playersAwaitingLevelReview']))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('sport.name')->label('Sport'),
                TextColumn::make('starts_at')->label('Početak')->dateTime('d.m.Y. H:i')->sortable(),
                TextColumn::make('ends_at')->label('Kraj')->dateTime('H:i'),
                TextColumn::make('status_label')->label('Status')->badge()
                    ->color(fn (GroupMatch $record): string => match ($record->status) {
                        GroupMatch::Review => 'warning',
                        GroupMatch::Booked => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('confirmed_players_count')->label('Igrači')
                    ->state(fn (GroupMatch $record): string => $record->confirmedPlayers->count().'/'.(2 * $record->team_size)),
                TextColumn::make('players_awaiting_level_review')->label('Prijave na pregledu')
                    ->state(fn (GroupMatch $record): int => $record->playersAwaitingLevelReview->count())
                    ->badge()->color('warning'),
                TextColumn::make('closes_at')->label('Rok')->dateTime('d.m.Y. H:i'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    'needs_review' => 'Sve za pregled',
                    GroupMatch::Open => 'Traži igrače', GroupMatch::Ready => 'Popunjen',
                    GroupMatch::Review => 'Na pregledu Arene', GroupMatch::Booked => 'Rezervisan',
                    GroupMatch::Closed => 'Zatvoren',
                ])->default('needs_review')
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'needs_review' => $query->where('closes_at', '>', now())->where(fn (Builder $review) => $review
                            ->where('status', GroupMatch::Review)
                            ->orWhere(fn (Builder $open) => $open->where('status', GroupMatch::Open)->whereHas('playersAwaitingLevelReview'))),
                        null => $query,
                        default => $query->where('status', $data['value']),
                    }),
            ])
            ->recordActions([
                Action::make('players')->label('Igrači i detalji')
                    ->modalContent(fn (GroupMatch $record) => view('filament.group-match-players', [
                        'match' => $record,
                        'courts' => $record->status === GroupMatch::Review
                            ? app(GroupMatchService::class)->availableCourtNames($record) : null,
                    ]))
                    ->modalSubmitAction(false)->modalCancelActionLabel('Zatvori'),
                Action::make('review_player')->label('Pregledaj prijavu igrača')
                    ->visible(fn (GroupMatch $record): bool => $record->status === GroupMatch::Open
                        && $record->closes_at->isFuture() && $record->playersAwaitingLevelReview->isNotEmpty())
                    ->schema([
                        Select::make('player')->label('Igrač')->required()
                            ->options(fn (GroupMatch $record): array => $record->playersAwaitingLevelReview
                                ->mapWithKeys(fn ($player) => [$player->id => "{$player->name} · {$player->self_rating}/10 · ekipa {$player->side}"])->all()),
                        Radio::make('decision')->label('Odluka')->required()
                            ->options(['approve' => 'Odobri mesto', 'decline' => 'Odbij prijavu']),
                    ])
                    ->action(function (GroupMatch $record, array $data): void {
                        $approve = $data['decision'] === 'approve';
                        try {
                            app(GroupMatchService::class)->reviewPlayer($record, $data['player'], $approve, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title($approve ? 'Mesto je odobreno.' : 'Prijava je odbijena.')->send();
                    }),
                Action::make('approve')->label('Rezerviši teren')->requiresConfirmation()
                    ->modalDescription('Sistem još jednom proverava slobodne terene i prikazanu cenu. Svi potvrđeni igrači dobijaju mejl o rezervaciji.')
                    ->visible(fn (GroupMatch $record): bool => $record->status === GroupMatch::Review && $record->closes_at->isFuture())
                    ->action(function (GroupMatch $record): void {
                        try {
                            app(GroupMatchService::class)->approve($record, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title('Grupni termin je rezervisan.')->send();
                    }),
                Action::make('decline')->label('Zatvori ili ponudi drugo vreme')->color('danger')
                    ->visible(fn (GroupMatch $record): bool => $record->status === GroupMatch::Review && $record->closes_at->isFuture())
                    ->schema([Textarea::make('note')->label('Poruka igračima')->required()->maxLength(500)
                        ->helperText('Možeš navesti drugi predlog. Igrači se prijavljuju za novo vreme kroz novi oglas.')])
                    ->action(function (GroupMatch $record, array $data): void {
                        app(GroupMatchService::class)->decline($record, auth()->user(), $data['note']);
                        Notification::make()->success()->title('Oglas je zatvoren.')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListGroupMatches::route('/')];
    }
}
