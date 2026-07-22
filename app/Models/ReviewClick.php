<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Short-link tracciato di una richiesta recensione (E3.3.3): mappa un `token`
 * univoco all'URL recensioni Google del tenant e conta i click. La rotta
 * pubblica `/r/{token}` registra il click e reindirizza.
 *
 * Scoped per-tenant dal TenantScope in contesto autenticato (dashboard); la
 * rotta di redirect è pubblica (nessun auth) → lì lo scope è no-op e la ricerca
 * per `token` (unico) è globale, come da pattern esistente.
 */
class ReviewClick extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'appointment_id',
        'token',
        'destination_url',
        'clicks',
        'first_clicked_at',
        'last_clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'first_clicked_at' => 'datetime',
            'last_clicked_at' => 'datetime',
        ];
    }

    /** Genera un token univoco per lo short-link. */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(16);
        } while (static::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
    }

    /** Registra un click (conteggio + timestamp primo/ultimo). */
    public function registerClick(): void
    {
        $now = now();

        $this->forceFill([
            'clicks' => $this->clicks + 1,
            'first_clicked_at' => $this->first_clicked_at ?? $now,
            'last_clicked_at' => $now,
        ])->save();
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
