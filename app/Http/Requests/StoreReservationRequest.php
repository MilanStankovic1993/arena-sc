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
}
