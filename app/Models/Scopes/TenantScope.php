<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Isolamento multi-tenant (E4.1.2): filtra automaticamente le query sui modelli
 * che appartengono a un tenant, in base al tenant dell'utente autenticato.
 *
 * No-op quando:
 *  - non c'è un utente autenticato (console, queue, webhook) → i job che
 *    interrogano esplicitamente per tenant continuano a funzionare;
 *  - l'utente è super-admin → vede i dati di tutti i tenant.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->isSuperAdmin() || ! $user->tenant_id) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $user->tenant_id);
    }
}
