<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'duration_minutes' => ['required', 'integer', 'min:60', 'max:180'],
            'players_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*.equipment_id' => ['nullable', 'exists:equipment,id'],
            'equipment.*.quantity' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }
}
