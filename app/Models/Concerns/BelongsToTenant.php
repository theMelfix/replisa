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
            }
        });
    }
}
