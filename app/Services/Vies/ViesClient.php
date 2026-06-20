<?php

namespace App\Services\Vies;

use App\Support\ItalianVat;
use Illuminate\Support\Facades\Http;

/**
 * Verifica una Partita IVA sul servizio UE VIES (REST API). Restituisce:
 *  - true  → P.IVA esistente e attiva;
 *  - false → P.IVA inesistente/non attiva;
 *  - null  → esito sconosciuto (servizio irraggiungibile/errore) → fail-open,
 *            non blocchiamo la registrazione per un disservizio esterno.
 */
class ViesClient
{
    private const BASE_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api';

    public function isValid(string $vatNumber, string $country = 'IT'): ?bool
    {
        $number = ItalianVat::normalize($vatNumber);

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get(self::BASE_URL."/ms/{$country}/vat/{$number}");

            if (! $response->successful()) {
                return null;
            }

            $valid = $response->json('valid', $response->json('isValid'));

            return is_bool($valid) ? $valid : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
