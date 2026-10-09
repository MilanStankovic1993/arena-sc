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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use RuntimeException;

class PhoneReservationForm
{
    public static function action(): CreateAction
    {
        return CreateAction::make()->model(Reservation::class)
            ->label('Nova rezervacija')->modalHeading('Rezervacija telefonom')
            ->modalWidth(Width::ThreeExtraLarge)->modalSubmitActionLabel('Sacuvaj rezervaciju')
            ->createAnother(false)->schema(static::components())
            ->using(fn (array $data): Reservation => app(AdminReservationService::class)->create($data));
    }

    public static function components(): array
    {
        $resetTime = fn (Set $set) => $set('booking_time', null);

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
            ]),
            Section::make('2. Za koga rezervisemo?')->columns(2)->columnSpanFull()->schema([
                ToggleButtons::make('customer_type')->label('')->options(['guest' => 'Gost', 'user' => 'Postojeci korisnik'])->inline()->default('guest')->required()->live()->columnSpanFull(),
                Select::make('user_id')->label('Pronađi korisnika')->options(fn () => User::query()->orderBy('name')->get()->mapWithKeys(fn (User $user) => [$user->id => $user->name.' · '.($user->phone ?: $user->email)])->all())
                    ->searchable()->visible(fn (Get $get) => $get('customer_type') === 'user')->required(fn (Get $get) => $get('customer_type') === 'user')->columnSpanFull(),
                TextInput::make('guest_name')->label('Ime i prezime')->maxLength(255)->visible(fn (Get $get) => $get('customer_type') !== 'user')->required(fn (Get $get) => $get('customer_type') !== 'user'),
                TextInput::make('guest_phone')->label('Telefon')->tel()->maxLength(50)->visible(fn (Get $get) => $get('customer_type') !== 'user')->required(fn (Get $get) => $get('customer_type') !== 'user'),
            ]),
            Section::make('Oprema (opciono)')->collapsible()->collapsed()->columnSpanFull()->schema([
                Repeater::make('equipment')->label('')->defaultItems(0)->addActionLabel('Dodaj opremu')->columns(2)->live()->schema([
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
            ]),
        ];
    }

    public static function summary(Get $get): string
    {
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
