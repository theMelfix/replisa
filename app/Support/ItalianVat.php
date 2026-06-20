<?php

namespace App\Support;

/**
 * Validazione formale della Partita IVA italiana (11 cifre, algoritmo di Luhn
 * con raddoppio delle posizioni pari). Vedi anche {@see \App\Services\Vies\ViesClient}
 * per la verifica di esistenza/attività su VIES.
 */
class ItalianVat
{
    /** Rimuove spazi, punti e l'eventuale prefisso paese "IT". */
    public static function normalize(string $vat): string
    {
        $vat = strtoupper(trim($vat));
        $vat = preg_replace('/^IT/', '', $vat);

        return preg_replace('/\D+/', '', (string) $vat);
    }

    /** Verifica il formato (11 cifre) e la cifra di controllo. */
    public static function isChecksumValid(string $vat): bool
    {
        $vat = self::normalize($vat);

        if (! preg_match('/^\d{11}$/', $vat)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $vat[$i];

            if ($i % 2 === 1) { // posizioni pari (2ª, 4ª, …): raddoppio
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
