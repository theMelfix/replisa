<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lead dalla landing (E5.1.2): una richiesta di contatto/demo dal form pubblico.
 * Non è tenant-owned (arriva da un visitatore anonimo), quindi niente
 * BelongsToTenant / TenantScope.
 */
class Lead extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'business',
        'message',
        'status',
        'ip',
    ];
}
