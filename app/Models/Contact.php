<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'phone',
        'name',
        'opted_in',
        'opted_in_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'opted_in' => 'boolean',
            'opted_in_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Normalizza un numero a E.164 senza `+`, il formato richiesto dalla
     * Cloud API e quello con cui i numeri sono salvati (l'unique è
     * `tenant_id`+`phone`: senza normalizzazione lo stesso numero scritto in
     * due formati creerebbe due contatti).
     */
    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
