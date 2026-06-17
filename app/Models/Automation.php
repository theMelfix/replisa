<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AutomationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    /** @use HasFactory<AutomationFactory> */
    use BelongsToTenant, HasFactory;

    public const TYPE_WELCOME = 'welcome';

    public const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';

    public const TYPE_REVIEW_REQUEST = 'review_request';

    protected $fillable = [
        'tenant_id',
        'type',
        'trigger',
        'config',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
