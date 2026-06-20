<?php

namespace App\Rules;

use App\Support\ItalianVat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regola di validazione: la Partita IVA italiana ha formato e cifra di controllo
 * validi. La verifica di esistenza su VIES avviene separatamente nel componente
 * di registrazione (per registrare l'esito e applicare la policy fail-open).
 */
class ItalianVatChecksum implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ItalianVat::isChecksumValid((string) $value)) {
            $fail('La Partita IVA non è valida.');
        }
    }
}
