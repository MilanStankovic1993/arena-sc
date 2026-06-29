<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'court_id' => ['required', 'exists:courts,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in([60, 90, 120])],
            'guest_name' => [Rule::requiredIf(fn (): bool => ! $this->user()), 'nullable', 'string', 'max:255'],
            'guest_phone' => [Rule::requiredIf(fn (): bool => ! $this->user()), 'nullable', 'string', 'max:50'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*.equipment_id' => ['nullable', 'required_with:equipment.*.quantity', 'integer', 'exists:equipment,id'],
            'equipment.*.quantity' => ['nullable', 'required_with:equipment.*.equipment_id', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'court_id.required' => 'Izaberi teren pre potvrde rezervacije.',
            'court_id.exists' => 'Izabrani teren vise nije dostupan.',
            'starts_at.required' => 'Izaberi termin pre potvrde rezervacije.',
            'starts_at.date' => 'Izabrani termin nije validan.',
            'starts_at.after' => 'Izabrani termin je vec prosao. Izaberi novi termin.',
            'duration_minutes.required' => 'Izaberi trajanje termina.',
            'duration_minutes.in' => 'Izabrano trajanje termina nije dostupno.',
            'guest_name.required' => 'Unesi ime i prezime za rezervaciju.',
            'guest_name.max' => 'Ime i prezime moze imati najvise 255 karaktera.',
            'guest_phone.required' => 'Unesi telefon za rezervaciju.',
            'guest_phone.max' => 'Telefon moze imati najvise 50 karaktera.',
            'guest_email.email' => 'Email adresa nije validna.',
            'guest_email.max' => 'Email moze imati najvise 255 karaktera.',
            'customer_note.max' => 'Napomena moze imati najvise 1000 karaktera.',
            'equipment.*.equipment_id.exists' => 'Izabrana oprema vise nije dostupna.',
            'equipment.*.quantity.integer' => 'Kolicina opreme mora biti ceo broj.',
            'equipment.*.quantity.min' => 'Kolicina opreme ne moze biti negativna.',
            'equipment.*.quantity.max' => 'Kolicina opreme moze biti najvise 20.',
        ];
    }
}
