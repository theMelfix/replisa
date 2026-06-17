<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Crea (o aggiorna) un tenant di test a partire dalle credenziali sandbox in
 * `.env` (`META_WHATSAPP_SANDBOX_*`). Serve a esercitare il percorso reale
 * dell'app (ADR-003: invio/ricezione scoped per-tenant via DB), senza dover
 * incollare il token a mano.
 *
 * Idempotente: usa `phone_number_id` come chiave.
 */
class SeedSandboxTenant extends Command
{
    protected $signature = 'replisa:seed-sandbox-tenant {--name=Replisa Sandbox}';

    protected $description = 'Crea/aggiorna un tenant di test dalle credenziali sandbox del .env';

    public function handle(): int
    {
        $phoneNumberId = config('services.meta.sandbox.phone_number_id');
        $wabaId = config('services.meta.sandbox.waba_id');
        $token = config('services.meta.sandbox.access_token');

        if (! $phoneNumberId || ! $token) {
            $this->error('Mancano META_WHATSAPP_SANDBOX_PHONE_NUMBER_ID o _ACCESS_TOKEN nel .env.');

            return self::FAILURE;
        }

        $tenant = Tenant::updateOrCreate(
            ['phone_number_id' => $phoneNumberId],
            [
                'name' => $this->option('name'),
                'waba_id' => $wabaId,
                'access_token' => $token,
                'plan' => 'sandbox',
                'active' => true,
            ],
        );

        $this->info(sprintf(
            'Tenant %s (#%d, phone_number_id=%s).',
            $tenant->wasRecentlyCreated ? 'creato' : 'aggiornato',
            $tenant->id,
            $phoneNumberId,
        ));

        return self::SUCCESS;
    }
}
