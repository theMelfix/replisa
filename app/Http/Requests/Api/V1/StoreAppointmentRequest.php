<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creazione di un appuntamento via API (task 4.3.3), tipicamente da un
 * gestionale esterno. Il reminder automatico scatta poi dallo scheduler
 * (E3.2) in base a `scheduled_at`.
 */
class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'La data dell\'appuntamento deve essere nel futuro.',
        ];
    }
}
