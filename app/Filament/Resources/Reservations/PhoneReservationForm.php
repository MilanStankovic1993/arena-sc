<?php

namespace App\Filament\Resources\Reservations;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminReservationService;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PhoneReservationForm
{
    public static function action(): CreateAction
    {
        return CreateAction::make()->model(Reservation::class)
            ->label('Nova rezervacija')->modalHeading('Rezervacija telefonom')
            ->modalWidth(Width::ThreeExtraLarge)->modalSubmitActionLabel('Sacuvaj rezervaciju')
            ->createAnother(false)->schema(static::components())
            ->using(function (array $data, $livewire): Reservation {
                try {
                    return app(AdminReservationService::class)->create($data);
                } catch (ValidationException $exception) {
                    $errors = [];
                    foreach ($exception->errors() as $field => $messages) {
                        $statePath = $livewire->getSchema($livewire->getMountedActionSchemaName())->getStatePath();
                        $errors[$statePath.'.'.$field] = $messages;
                    }
                    throw ValidationException::withMessages($errors);
                }
            });
    }

    public static function components(): array
    {
        $resetTime = function (Set $set): void {
            $set('booking_time', null);
            $set('skip_dates', []);
        };

        return [
            Section::make('1. Izaberite termin')->columns(2)->columnSpanFull()->schema([
                Select::make('court_id')->label('Teren')->options(fn () => Court::query()
                    ->with('sport')->where('is_active', true)->whereHas('sport', fn ($query) => $query->where('is_active', true))
                    ->orderBy('name')->get()->mapWithKeys(fn (Court $court) => [$court->id => $court->name.' · '.$court->sport->name])->all())
                    ->required()->live()->afterStateUpdated($resetTime),
                DatePicker::make('booking_date')->label('Datum')->native(false)->displayFormat('d.m.Y')
                    ->default(now()->toDateString())->minDate(today())->required()->live()->afterStateUpdated($resetTime),
                ToggleButtons::make('duration_minutes')->label('Trajanje')->options([60 => '60 min', 90 => '90 min', 120 => '120 min'])
                    ->inline()->default(60)->required()->live()->afterStateUpdated($resetTime),
                Select::make('booking_time')->label('Slobodan termin')->placeholder('Izaberite vreme')
                    ->options(fn (Get $get) => app(AdminReservationService::class)->times($get('court_id'), $get('booking_date'), (int) ($get('duration_minutes') ?: 60)))
                    ->disabled(fn (Get $get) => ! $get('court_id') || ! $get('booking_date'))->required()->live()
                    ->helperText('Prikazani su slobodni termini za izabrano trajanje.'),
                Toggle::make('repeat_weekly')->label('Ponavljaj svake nedelje')->default(false)->live()->columnSpanFull()
                    ->helperText('Ponavljanje je istog dana u nedelji i u isto vreme kao prvi termin.'),
                DatePicker::make('repeat_until')->label('Ponavljaj zakljucno sa datumom')->native(false)->displayFormat('d.m.Y')
                    ->default(now()->addMonthsNoOverflow(3)->toDateString())
                    ->minDate(fn (Get $get) => $get('booking_date') ?: today())
                    ->maxDate(fn (Get $get) => Carbon::parse($get('booking_date') ?: today())->addYear())
                    ->required(fn (Get $get) => (bool) $get('repeat_weekly'))->visible(fn (Get $get) => (bool) $get('repeat_weekly'))
                    ->live()->columnSpanFull()->helperText('Do 12 meseci. Cena i dostupnost se proveravaju za svaki datum.'),
            ]),
            Section::make('2. Za koga rezervisemo?')->columns(2)->columnSpanFull()->schema([
                ToggleButtons::make('customer_type')->label('Vrsta korisnika')->options(['guest' => 'Gost', 'user' => 'Postojeci korisnik'])->inline()->default('guest')->required()->live()->columnSpanFull(),
                Select::make('user_id')->label('Pronađi korisnika')->options(fn () => User::query()->orderBy('name')->get()->mapWithKeys(fn (User $user) => [$user->id => $user->name.' · '.($user->phone ?: $user->email)])->all())
                    ->searchable()->visible(fn (Get $get) => $get('customer_type') === 'user')->required(fn (Get $get) => $get('customer_type') === 'user')->columnSpanFull(),
                TextInput::make('guest_name')->label('Ime i prezime')->maxLength(255)->visible(fn (Get $get) => $get('customer_type') !== 'user')->required(fn (Get $get) => $get('customer_type') !== 'user'),
                TextInput::make('guest_phone')->label('Telefon')->tel()->maxLength(50)->visible(fn (Get $get) => $get('customer_type') !== 'user')->required(fn (Get $get) => $get('customer_type') !== 'user'),
            ]),
            Section::make('Oprema (opciono)')->collapsible()->collapsed()->columnSpanFull()->schema([
                Repeater::make('equipment')->label('Stavke opreme')->defaultItems(0)->addActionLabel('Dodaj opremu')->columns(2)->live()->schema([
                    Select::make('equipment_id')->label('Artikal')->options(fn (Get $get) => Equipment::query()
                        ->where('is_active', true)->where('is_rentable', true)->where('stock_quantity', '>', 0)
                        ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', Court::find($get('../../court_id'))?->sport_id))
                        ->orderBy('name')->get()->mapWithKeys(fn (Equipment $item) => [$item->id => $item->name.' · '.number_format((float) $item->rental_price, 0, ',', '.').' RSD'])->all())->required()->searchable(),
                    TextInput::make('quantity')->label('Kolicina')->integer()->minValue(1)->default(1)->required(),
                ]),
            ]),
            Section::make('Napomene i izmena cene')->collapsible()->collapsed()->columns(2)->columnSpanFull()->schema([
                TextInput::make('guest_email')->label('Email gosta (opciono)')->email()->maxLength(255)->visible(fn (Get $get) => $get('customer_type') !== 'user'),
                TextInput::make('court_price_override')->label('Posebna cena terena (opciono)')->numeric()->minValue(0)->prefix('RSD')->live(onBlur: true)->helperText('Ostavite prazno za cenu iz cenovnika.'),
                Textarea::make('customer_note')->label('Napomena gosta')->rows(2),
                Textarea::make('admin_note')->label('Interna napomena')->rows(2),
            ]),
            Section::make('3. Pregled rezervacije')->columnSpanFull()->schema([
                Text::make(fn (Get $get) => static::summary($get)),
                CheckboxList::make('skip_dates')->label('Datumi koje zelite da preskocite')
                    ->options(fn (Get $get) => app(AdminReservationService::class)->preview(static::recurringData($get))
                        ->mapWithKeys(fn ($row) => [$row['date'] => Carbon::parse($row['date'])->format('d.m.Y').' · '.($row['reason'] ?: 'Slobodno')])->all())
                    ->default([])->columns(2)->live()->visible(fn (Get $get) => (bool) $get('repeat_weekly'))
                    ->helperText('Zauzeti termini se ne preskacu automatski. Oznacite ih ovde ili promenite izbor termina.'),
            ]),
        ];
    }

    private static function recurringData(Get $get): array
    {
        return collect(['court_id', 'booking_date', 'booking_time', 'duration_minutes', 'repeat_until', 'equipment', 'court_price_override'])
            ->mapWithKeys(fn ($field) => [$field => $get($field)])->all();
    }

    public static function summary(Get $get): string|HtmlString
    {
        if ($get('repeat_weekly')) {
            $rows = app(AdminReservationService::class)->preview(static::recurringData($get));
            if ($rows->isEmpty()) {
                return 'Izaberite teren, prvi slobodan termin i datum zavrsetka ponavljanja.';
            }
            $skip = $get('skip_dates') ?? [];
            $included = $rows->whereNotIn('date', $skip);
            $html = '<div><p><strong>Svake nedelje · '.e(Carbon::parse($get('booking_date'))->translatedFormat('l')).' · '.e($rows->first()['time']).'</strong></p><table style="width:100%;margin-top:12px;text-align:left"><thead><tr><th>Datum</th><th>Status</th><th>Cena</th></tr></thead><tbody>';
            foreach ($rows as $row) {
                $status = in_array($row['date'], $skip, true) ? 'Preskoceno' : ($row['reason'] ?: 'Slobodno');
                $html .= '<tr><td style="padding:6px 0">'.e(Carbon::parse($row['date'])->format('d.m.Y')).'</td><td>'.e($status).'</td><td>'.($row['reason'] ? '—' : e(number_format($row['price'], 0, ',', '.').' RSD')).'</td></tr>';
            }
            $html .= '</tbody></table><p style="margin-top:12px"><strong>Izabrano: '.$included->count().' termina · Ukupno: '.e(number_format($included->whereNull('reason')->sum('price'), 0, ',', '.')).' RSD</strong></p>';
            if ($included->whereNotNull('reason')->isNotEmpty()) {
                $html .= '<p>Postoje nedostupni termini. Oznacite datume za preskakanje ili promenite izbor pre cuvanja.</p>';
            }

            return new HtmlString($html.'</div>');
        }
        $court = Court::find($get('court_id'));
        if (! $court || ! $get('booking_date') || ! $get('booking_time')) {
            return 'Izaberite teren i slobodan termin za pregled cene.';
        }
        $start = Carbon::parse($get('booking_date').' '.$get('booking_time'));
        $end = $start->copy()->addMinutes((int) $get('duration_minutes'));
        try {
            $price = app(ReservationPricingService::class)->calculateCourtPrice($court, $start, $end);
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }
        if ($get('court_price_override') !== null && $get('court_price_override') !== '') {
            $price = (float) $get('court_price_override');
        }
        foreach ($get('equipment') ?? [] as $item) {
            $price += (float) Equipment::find($item['equipment_id'] ?? null)?->rental_price * (int) ($item['quantity'] ?? 0);
        }

        return $court->name.' · '.$start->format('d.m.Y').' · '.$start->format('H:i').'–'.$end->format('H:i').' · Ukupno: '.number_format($price, 0, ',', '.').' RSD';
    }
}
