<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Invio di un messaggio singolo via API (task 4.3.2). Due modalità mutuamente
 * esclusive: `text` (solo dentro la finestra di servizio 24h) o `template`
 * (approvato, consentito sempre).
 */
class SendMessageRequest extends FormRequest
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
            'to' => ['required', 'string', 'max:20'],
            'type' => ['required', Rule::in([Message::TYPE_TEXT, Message::TYPE_TEMPLATE])],

            // type=text
            'text' => ['required_if:type,text', 'string', 'max:4096'],

            // type=template
            'template' => ['required_if:type,template', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'language' => ['sometimes', 'string', 'max:10', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            'params' => ['sometimes', 'array'],
            'params.*' => ['string', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template.regex' => 'Il nome del template Meta può contenere solo lettere minuscole, numeri e underscore.',
            'language.regex' => 'Codice lingua non valido: usa il formato "it" oppure "en_US".',
        ];
    }
}
