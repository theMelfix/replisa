<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Scadenza del calendario (E3.2.6). `tenant_id` null = scadenza nazionale
 * (gestita dal super-admin, visibile a tutti); valorizzato = scadenza propria
 * del tenant. NB: niente TenantScope, altrimenti le nazionali (tenant_id null)
 * sparirebbero ai tenant.
 */
class Deadline extends Model
{
    protected $fillable = [
        'tenant_id',
        'sector',
        'name',
        'due_date',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function isNational(): bool
    {
        return is_null($this->tenant_id);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<DeadlineReminder, $this> */
    public function reminders(): HasMany
    {
        return $this->hasMany(DeadlineReminder::class);
    }

    /** Scadenze nazionali (tenant_id null). */
    public function scopeNational(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scadenze visibili a un tenant: le proprie + le nazionali pertinenti al suo
     * settore (o senza settore = valide per tutti).
     */
    public function scopeVisibleTo(Builder $query, ?int $tenantId, ?string $sector): Builder
    {
        return $query->where(function (Builder $q) use ($tenantId, $sector): void {
            if ($tenantId) {
                $q->where('tenant_id', $tenantId);
            }

            $q->orWhere(fn (Builder $national) => $national
                ->whereNull('tenant_id')
                ->where(fn (Builder $s) => $s->whereNull('sector')->orWhere('sector', $sector)));
        });
    }
}
