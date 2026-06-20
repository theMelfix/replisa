<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use Billable, HasFactory;

    protected $fillable = [
        'name',
        'vat_number',
        'tax_code',
        'address',
        'city',
        'postal_code',
        'province',
        'country',
        'sdi_code',
        'pec',
        'vat_validated_at',
        'phone_number_id',
        'waba_id',
        'access_token',
        'plan',
        'active',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted', // ADR-003: criptato a riposo
            'active' => 'boolean',
            'vat_validated_at' => 'datetime',
        ];
    }

    /** @return HasMany<Contact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** @return HasMany<Automation, $this> */
    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
