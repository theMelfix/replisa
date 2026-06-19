<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Sollevata quando un'operazione per-tenant viene tentata senza un tenant a cui
 * agganciarla — es. un super-admin (tenant_id null) che opera una UI per-tenant.
 *
 * Catturata dai componenti Livewire per mostrare un toast con il messaggio,
 * invece di un 500. Vedi {@see \App\Models\Concerns\BelongsToTenant}.
 */
class TenantContextException extends RuntimeException
{
}
