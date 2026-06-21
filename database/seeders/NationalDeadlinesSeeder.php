<?php

namespace Database\Seeders;

use App\Models\Deadline;
use Illuminate\Database\Seeder;

/**
 * Scadenze fiscali nazionali (E3.2.6). Date 2026 note al 2026-06; vanno
 * verificate/aggiornate dal super-admin a ogni annuncio ufficiale (l'UI admin
 * permette di modificarle). Idempotente.
 */
class NationalDeadlinesSeeder extends Seeder
{
    public function run(): void
    {
        $deadlines = [
            ['IMU 2026 — acconto', '2026-06-16'],
            ['Versamenti redditi 2026 (saldo 2025 + 1° acconto)', '2026-06-30'],
            ['Dichiarazione 730/2026', '2026-09-30'],
            ['Dichiarazione Redditi PF 2026', '2026-11-02'],
            ['IMU 2026 — saldo', '2026-12-16'],
        ];

        foreach ($deadlines as [$name, $date]) {
            Deadline::updateOrCreate(
                ['tenant_id' => null, 'name' => $name],
                [
                    'sector' => 'commercialista', // scadenze fiscali: rilevanti per commercialisti/CAF
                    'due_date' => $date,
                    'description' => 'Scadenza nazionale — verificare a ogni annuncio ufficiale',
                    'active' => true,
                ],
            );
        }
    }
}
