<?php

namespace App\Filament\Resources\Users;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\EmailCampaign;
use App\Models\MembershipPlan;
use App\Models\Reservation;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\EmailCampaignService;
use App\Services\ReservationParticipantService;
use App\Services\UserStatisticsService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Korisnici';

    protected static string|UnitEnum|null $navigationGroup = 'Korisnici i analitika';

    protected static ?string $modelLabel = 'Korisnik';

    protected static ?string $pluralModelLabel = 'Korisnici';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Korisnicki profil')->schema([
                TextInput::make('name')->label('Ime i prezime')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Lozinka')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->minLength(8)
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->label('Potvrda lozinke')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->visible(fn (string $operation): bool => $operation === 'create'),
                TextInput::make('phone')->label('Telefon'),
                Select::make('role')
                    ->label('Uloga')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])->all())
                    ->required(),
                DatePicker::make('registered_at')->label('Datum registracije'),
                Textarea::make('notes')->label('Napomena')->rows(4)->columnSpanFull(),
            ])->columns(2),
            Section::make('Statistika korisnika')
                ->visible(fn (?User $record): bool => filled($record))
                ->schema([
                    Placeholder::make('stats_total')
                        ->label('Ukupno rezervacija')
                        ->content(fn (?User $record): string => number_format(static::stats($record)['total'] ?? 0, 0, ',', '.')),
                    Placeholder::make('stats_revenue')
                        ->label('Ukupna potrosnja')
                        ->content(fn (?User $record): string => static::money(static::stats($record)['revenue'] ?? 0)),
                    Placeholder::make('stats_average')
                        ->label('Prosecna cena')
                        ->content(fn (?User $record): string => static::money(static::stats($record)['averageSpend'] ?? 0)),
                    Placeholder::make('stats_cancel_rate')
                        ->label('Stopa otkazivanja')
                        ->content(fn (?User $record): string => number_format(static::stats($record)['cancellationRate'] ?? 0, 1, ',', '.').'%'),
                    Placeholder::make('stats_last')
                        ->label('Poslednji termin')
                        ->content(fn (?User $record): string => static::dateTime(static::stats($record)['lastReservationAt'] ?? null)),
                    Placeholder::make('stats_favorite_sport')
                        ->label('Najcesci sport')
                        ->content(fn (?User $record): string => static::stats($record)['favoriteSport'] ?? 'Nema podataka'),
                ])
                ->columns(3),
            Section::make('Članarina korisnika')
                ->visible(fn (?User $record): bool => filled($record))
                ->schema([
                    Placeholder::make('membership_name')
                        ->label('Aktivna članarina')
                        ->content(fn (?User $record): string => static::membershipSummary($record)['name'] ?? 'Nema aktivnu članarinu'),
                    Placeholder::make('membership_valid_until')
                        ->label('Vazi do')
                        ->content(fn (?User $record): string => static::membershipSummary($record)['endsAt'] ?? 'Nema podataka'),
                    Placeholder::make('membership_reservation_limit')
                        ->label('Limit rezervacija')
                        ->content(fn (?User $record): string => static::membershipSummary($record)['limit'] ?? 'Nema limita'),
                    Placeholder::make('membership_sport')
                        ->label('Sport')
                        ->content(fn (?User $record): string => static::membershipSummary($record)['sport'] ?? 'Svi sportovi / nema članarine'),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSum('participatedReservations as participated_reservations_sum_total_price', 'total_price'))
            ->columns([
                TextColumn::make('name')->label('Korisnik')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon'),
                TextColumn::make('role')->label('Uloga')->badge(),
                TextColumn::make('active_membership_label')->label('Članarina')->badge(),
                TextColumn::make('total_reservations')->label('Rezervacije')->sortable(),
                TextColumn::make('cancelled_reservations')->label('Otkazivanja')->sortable(),
                TextColumn::make('last_reservation_at')->label('Poslednji termin')->dateTime()->sortable(),
                TextColumn::make('participated_reservations_sum_total_price')->label('Ukupna potrosnja')->money('RSD', divideBy: 1),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
                    UserRole::Customer->value => UserRole::Customer->label(),
                ]),
                Filter::make('active_period')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('until')->label('Do'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($builder, $date) => $builder->whereHas('participatedReservations', fn ($reservations) => $reservations->whereDate('starts_at', '>=', $date)))
                            ->when($data['until'] ?? null, fn ($builder, $date) => $builder->whereHas('participatedReservations', fn ($reservations) => $reservations->whereDate('starts_at', '<=', $date)));
                    }),
            ])
            ->recordActions([
                Action::make('statistika')
                    ->label('Statistika')
                    ->icon(Heroicon::OutlinedChartBarSquare)
                    ->color('info')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn (User $record): string => 'Statistika korisnika - '.$record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zatvori')
                    ->modalContent(fn (User $record): View => view('filament.resources.users.statistics', [
                        'record' => $record,
                        'stats' => static::stats($record),
                    ])),
                Action::make('dodeliČlanarinu')
                    ->label('Dodeli članarinu')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->color('success')
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('membership_plan_id')
                            ->label('Članarina')
                            ->options(fn (): array => MembershipPlan::query()
                                ->where('is_active', true)
                                ->with('sport')
                                ->orderBy('sort_order')
                                ->orderBy('price')
                                ->get()
                                ->mapWithKeys(fn (MembershipPlan $plan): array => [
                                    $plan->id => sprintf(
                                        '%s%s - %s RSD',
                                        $plan->name,
                                        $plan->sport ? ' ('.$plan->sport->name.')' : ' (svi sportovi)',
                                        number_format((float) $plan->price, 0, ',', '.'),
                                    ),
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        DatePicker::make('starts_at')
                            ->label('Vazi od')
                            ->default(now()->toDateString())
                            ->required(),
                        DatePicker::make('ends_at')
                            ->label('Vazi do')
                            ->helperText('Ako ostavis prazno, sistem racuna kraj prema trajanju iz izabrane članarine.'),
                        Textarea::make('notes')
                            ->label('Napomena')
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        $plan = MembershipPlan::query()->findOrFail($data['membership_plan_id']);
                        $startsAt = Carbon::parse($data['starts_at'])->startOfDay();
                        $endsAt = filled($data['ends_at'])
                            ? Carbon::parse($data['ends_at'])->startOfDay()
                            : ($plan->duration_days ? $startsAt->copy()->addDays($plan->duration_days - 1) : null);

                        if (! $endsAt) {
                            throw ValidationException::withMessages([
                                'ends_at' => 'Unesi datum do kada vazi članarina ili u sablonu članarine definisi trajanje u danima.',
                            ]);
                        }

                        if ($endsAt->lt($startsAt)) {
                            throw ValidationException::withMessages([
                                'ends_at' => 'Datum isteka ne moze biti pre datuma pocetka članarine.',
                            ]);
                        }

                        $record->memberships()->create([
                            'membership_plan_id' => $plan->id,
                            'starts_at' => $startsAt->toDateString(),
                            'ends_at' => $endsAt->toDateString(),
                            'is_active' => true,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Članarina je dodeljena korisniku.')
                            ->body('Vazi do '.$endsAt->format('d.m.Y').'.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->modalWidth(Width::Screen),
                DeleteAction::make()->visible(fn (User $record): bool => $record->role !== UserRole::SuperAdmin),
            ])
            ->toolbarActions([
                Action::make('posaljiSvima')
                    ->label('Posalji svim korisnicima')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Mail kampanja')
                            ->options(fn (): array => EmailCampaign::query()->where('is_active', true)->orderByDesc('created_at')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $campaign = EmailCampaign::query()->findOrFail($data['campaign_id']);
                        $sent = app(EmailCampaignService::class)->sendToUsers($campaign, app(EmailCampaignService::class)->allCustomers());

                        Notification::make()
                            ->title('Kampanja je poslata svim korisnicima.')
                            ->body("Poslato korisnicima: {$sent}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('posaljiOdabranima')
                        ->label('Posalji odabranima')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->color('success')
                        ->schema([
                            Select::make('campaign_id')
                                ->label('Mail kampanja')
                                ->options(fn (): array => EmailCampaign::query()->where('is_active', true)->orderByDesc('created_at')->pluck('name', 'id')->all())
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data): void {
                            $campaign = EmailCampaign::query()->findOrFail($data['campaign_id']);
                            $sent = app(EmailCampaignService::class)->sendToUsers($campaign, $records);

                            Notification::make()
                                ->title('Kampanja je poslata odabranim korisnicima.')
                                ->body("Poslato korisnicima: {$sent}.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('pridruziTerminu')
                        ->label('Pridruzi terminu')
                        ->icon(Heroicon::OutlinedCalendarDateRange)
                        ->color('warning')
                        ->modalWidth(Width::Large)
                        ->schema([
                            Select::make('reservation_id')
                                ->label('Termin')
                                ->options(fn (): array => Reservation::query()
                                    ->with(['user', 'sport', 'court'])
                                    ->where('status', ReservationStatus::Reserved->value)
                                    ->orderByDesc('starts_at')
                                    ->limit(150)
                                    ->get()
                                    ->mapWithKeys(fn (Reservation $reservation): array => [
                                        $reservation->id => sprintf(
                                            '#%s | %s | %s | %s | %s',
                                            $reservation->id,
                                            $reservation->starts_at?->format('d.m.Y H:i'),
                                            $reservation->sport?->name,
                                            $reservation->court?->name,
                                            $reservation->customer_display_name,
                                        ),
                                    ])
                                    ->all())
                                ->searchable()
                                ->required()
                                ->helperText('Izabrani korisnici ce biti dodati kao ucesnici termina, bez kreiranja novih rezervacija.'),
                        ])
                        ->action(function ($records, array $data): void {
                            $reservation = Reservation::query()->findOrFail($data['reservation_id']);
                            $attached = app(ReservationParticipantService::class)->attachUsers($reservation, $records);

                            Notification::make()
                                ->title('Korisnici su pridruzeni terminu.')
                                ->body("Dodato novih ucesnika: {$attached}.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    protected static function stats(?User $record): array
    {
        if (! $record) {
            return [];
        }

        return app(UserStatisticsService::class)->summary($record);
    }

    protected static function membershipSummary(?User $record): array
    {
        if (! $record) {
            return [];
        }

        $membership = $record->memberships()
            ->with('membershipPlan.sport')
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->orderByDesc('ends_at')
            ->first();

        if (! $membership instanceof UserMembership) {
            return [];
        }

        return [
            'name' => $membership->membershipPlan->name,
            'endsAt' => $membership->ends_at->format('d.m.Y'),
            'limit' => $membership->membershipPlan->reservation_limit.' termina ukupno',
            'sport' => $membership->membershipPlan->sport?->name ?? 'Svi sportovi',
        ];
    }

    protected static function money(float|int $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').' RSD';
    }

    protected static function dateTime($value): string
    {
        return $value?->format('d.m.Y H:i') ?? 'Nema podataka';
    }
}
