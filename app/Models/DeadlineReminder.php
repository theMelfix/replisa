<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Promemoria di scadenza configurato da un tenant (E3.2.6): template + giorni di
 * anticipo per una scadenza (nazionale o propria). Per-tenant (TenantScope).
 */
class DeadlineReminder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'deadline_id',
        'template_name',
        'language',
        'include_name',
        'days_before',
        'active',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'include_name' => 'boolean',
            'active' => 'boolean',
            'dispatched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Deadline, $this> */
    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
