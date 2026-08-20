<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToTenant, HasFactory;

    public const DIRECTION_OUTBOUND = 'outbound';

    public const DIRECTION_INBOUND = 'inbound';

    public const TYPE_TEXT = 'text';

    public const TYPE_TEMPLATE = 'template';

    public const TYPE_INTERACTIVE = 'interactive';

    // Stati outbound allineati agli status update del webhook Meta.
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    // Stato dei messaggi inbound ricevuti via webhook.
    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'direction',
        'type',
        'content',
        'status',
        'meta_message_id',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'error' => 'array',
        ];
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

    /**
     * Testo leggibile del messaggio per l'interfaccia (log + conversazione),
     * normalizzato dai diversi formati di `content` (outbound testo/template/
     * interattivo; inbound testo/reply bottone/quick-reply template).
     */
    public function displayText(): string
    {
        $c = $this->content ?? [];

        return match (true) {
            filled($c['body'] ?? null) => $c['body'],           // testo, o corpo interattivo outbound
            filled($c['title'] ?? null) => $c['title'],         // reply a un bottone/lista (inbound)
            filled($c['text'] ?? null) => $c['text'],           // quick-reply template (inbound)
            filled($c['template'] ?? null) => 'Modello: '.$c['template'],
            default => '—',
        };
    }

    /**
     * Motivo leggibile del fallimento, per la Panoramica admin.
     *
     * La colonna `error` arriva da tre strade diverse e con tre forme diverse:
     * un errore di connessione scritto da `WhatsAppService`
     * (`['type' => 'connection', 'message' => ...]`), l'oggetto errore di Meta
     * sulla risposta di invio, e la **lista** di errori che il webhook consegna
     * negli status update. Qui le si normalizza in una riga sola.
     */
    public function errorSummary(): ?string
    {
        $error = $this->error;

        if (blank($error)) {
            return null;
        }

        // Il webhook consegna una lista; l'invio diretto un singolo oggetto.
        $first = array_is_list($error) ? ($error[0] ?? null) : $error;

        if (! is_array($first)) {
            return is_string($first) ? $first : null;
        }

        // `error_data.details` è di solito la spiegazione più specifica di Meta
        // ("more than 24 hours have passed..."); `message` e `title` sono più generici.
        $text = data_get($first, 'error_data.details')
            ?? ($first['message'] ?? null)
            ?? ($first['title'] ?? null);

        if (blank($text)) {
            return null;
        }

        $code = $first['code'] ?? null;

        return $code ? "[{$code}] {$text}" : (string) $text;
    }
}
