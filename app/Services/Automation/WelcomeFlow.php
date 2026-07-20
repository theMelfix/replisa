<?php

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppService;

/**
 * Welcome Flow (E3.1): alla prima interazione di un contatto risponde con un
 * menu interattivo a bottoni e ne traccia l'opt-in. Le risposte ai bottoni
 * vengono instradate verso il messaggio configurato dal tenant.
 *
 * Il flusso è scoped per-tenant (come {@see WhatsAppService}) e si attiva solo
 * se il tenant ha un'Automation `welcome` attiva (tabella `automations`,
 * task 2.3.5). Il menu viaggia come messaggio interattivo free-form: è quindi
 * consentito dentro la finestra di servizio 24h aperta dal messaggio in ingresso
 * del contatto, senza bisogno di un template Meta approvato.
 */
class WelcomeFlow
{
    /** Prefisso degli id bottone, per riconoscere i reply che competono a questo flusso. */
    public const BUTTON_PREFIX = 'welcome:';

    public const DEFAULT_GREETING = 'Ciao! 👋 Grazie per averci scritto. Come possiamo aiutarti?';

    /** @var array<int, array{id: string, title: string}> */
    public const DEFAULT_BUTTONS = [
        ['id' => 'info_servizi', 'title' => 'Info Servizi'],
        ['id' => 'prenota', 'title' => 'Prenota'],
        ['id' => 'parla_con_noi', 'title' => 'Parla con noi'],
    ];

    public function __construct(private readonly Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    /**
     * Trigger primo contatto (task 3.1.2/3.1.3/3.1.5): registra l'opt-in e invia
     * il menu interattivo di benvenuto. No-op se il flusso non è attivo per il tenant.
     */
    public function greet(Contact $contact): ?Message
    {
        $automation = $this->automation();

        if (! $automation) {
            return null;
        }

        $this->recordOptIn($contact);

        $config = $automation->config ?? [];

        return WhatsAppService::for($this->tenant)->sendButtons(
            $contact,
            body: $config['greeting'] ?? self::DEFAULT_GREETING,
            buttons: $this->buttons($config),
            header: $config['header'] ?? null,
            footer: $config['footer'] ?? null,
        );
    }

    /**
     * Routing della risposta a un bottone del menu (task 3.1.4).
     *
     * @param  string  $buttonId  l'`id` del reply button ricevuto via webhook
     */
    public function handleButtonReply(Contact $contact, string $buttonId): ?Message
    {
        if (! str_starts_with($buttonId, self::BUTTON_PREFIX)) {
            return null;
        }

        $automation = $this->automation();

        if (! $automation) {
            return null;
        }

        $action = substr($buttonId, strlen(self::BUTTON_PREFIX));
        $reply = $automation->config['replies'][$action] ?? null;

        if ($reply === null) {
            return null;
        }

        return WhatsAppService::for($this->tenant)->sendText($contact, $reply);
    }

    /** L'Automation `welcome` attiva del tenant, se presente. */
    private function automation(): ?Automation
    {
        return $this->tenant->automations()
            ->where('type', Automation::TYPE_WELCOME)
            ->where('active', true)
            ->first();
    }

    /**
     * Opt-in tracking (task 3.1.5): il contatto ha scritto per primo all'azienda,
     * azione affermativa che vale come consenso a ricevere risposte. Registrato
     * una sola volta (idempotente). Le campagne marketing richiederanno comunque
     * un opt-in esplicito dedicato (GDPR).
     */
    private function recordOptIn(Contact $contact): void
    {
        if ($contact->opted_in) {
            return;
        }

        $contact->forceFill([
            'opted_in' => true,
            'opted_in_at' => now(),
        ])->save();
    }

    /**
     * Costruisce i reply button dal config, con prefisso namespaced sugli id.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array{id: string, title: string}>
     */
    private function buttons(array $config): array
    {
        $buttons = $config['buttons'] ?? self::DEFAULT_BUTTONS;

        return array_map(fn (array $b) => [
            'id' => self::BUTTON_PREFIX.$b['id'],
            'title' => $b['title'],
        ], array_values($buttons));
    }
}
