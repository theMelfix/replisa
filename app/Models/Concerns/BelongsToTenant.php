<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

/**
 * Applica l'isolamento multi-tenant a un modello (E4.1.2):
 *  - registra il {@see TenantScope} globale (lettura filtrata per tenant);
 *  - in scrittura, compila automaticamente `tenant_id` dal tenant dell'utente
 *    autenticato, se non già impostato (es. dai job che usano la relazione
 *    `$tenant->messages()->create(...)`, dove il tenant_id è già valorizzato).
 *
 * I modelli che usano questo trait devono avere una colonna `tenant_id` e una
 * relazione `tenant()`.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            if (! empty($model->tenant_id)) {
                return;
            }

            $user = Auth::user();

            if ($user && $user->tenant_id) {
                $model->tenant_id = $user->tenant_id;

                return;
            }

            // Backstop (E4.2.3): un utente autenticato senza tenant — es. un
            // super-admin (tenant_id null) — non può creare record per-tenant.
            // Senza questa guardia MySQL solleverebbe un 1364 criptico
            // ("Field 'tenant_id' doesn't have a default value"); qui falliamo
            // con un messaggio chiaro. I contesti senza Auth (console, queue,
            // webhook) restano liberi: lì il tenant_id va passato esplicitamente
            // dalla relazione `$tenant->...()->create(...)`.
            if ($user) {
                throw new \RuntimeException(sprintf(
                    'Impossibile creare un record %s: l\'utente autenticato non appartiene a nessun tenant.',
                    class_basename($model)
                ));
            }
        });
    }
}
