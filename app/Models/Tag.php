<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Etichetta contatti (E3.4.3): tag per-tenant usato per segmentare le campagne.
 * Scoped per-tenant dal TenantScope.
 */
class Tag extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    /** @return BelongsToMany<Contact, $this> */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }
}
