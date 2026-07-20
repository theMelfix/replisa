<?php

namespace App\Livewire;

use App\Exceptions\TenantContextException;
use App\Models\Automation;
use App\Models\Scopes\TenantScope;
use App\Services\Automation\AppointmentReminder;
use App\Services\Automation\WelcomeFlow;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestione automazioni del tenant (E4.2.3): attiva/disattiva i 3 flussi MVP.
 * Le query sono scoped per-tenant dal {@see TenantScope};
 * in creazione il `tenant_id` è compilato dal trait BelongsToTenant.
 */
#[Layout('layouts.app')]
class Automations extends Component
{
    /** Modalità del link recensione: statico (nel template) o dinamico (param). */
    public string $reviewUrlMode = 'static';

    /** Suffisso del button URL dinamico del template recensione (solo modalità dinamica). */
    public ?string $reviewUrlParam = null;

    /** Numero di slot bottone del menu di benvenuto (limite Meta sui reply button). */
    public const WELCOME_BUTTON_SLOTS = 3;

    public string $welcomeGreeting = '';

    public ?string $welcomeHeader = null;

    public ?string $welcomeFooter = null;

    /**
     * Slot bottone del menu di benvenuto. L'`id` non è modificabile dall'utente:
     * resta stabile tra i salvataggi perché è quello che il webhook rimanda
     * indietro nel reply button (vedi WelcomeFlow::handleButtonReply()).
     *
     * @var array<int, array{id: string, title: string, reply: string}>
     */
    public array $welcomeButtons = [];

    public string $reminderTemplate = '';

    public string $reminderLanguage = 'it';

    /** Ore di anticipo dei promemoria, come lista separata da virgole (es. "24, 2"). */
    public string $reminderOffsets = '';

    public string $reminderConfirm = '';

    public string $reminderCancel = '';

    /** @var array<string, array{label: string, desc: string, trigger: string}> */
    public const FLOWS = [
        Automation::TYPE_WELCOME => [
            'label' => 'Benvenuto automatico',
            'desc' => 'Menu interattivo al primo messaggio di un nuovo contatto.',
            'trigger' => 'inbound',
        ],
        Automation::TYPE_APPOINTMENT_REMINDER => [
            'label' => 'Promemoria & Scadenze',
            'desc' => 'Promemoria automatici di appuntamenti e scadenze (IMU, 730, rinnovi), con o senza conferma.',
            'trigger' => 'schedule',
        ],
        Automation::TYPE_CAMPAIGN => [
            'label' => 'Campagne e comunicazioni',
            'desc' => 'Invii programmati a liste o segmenti di contatti: promozioni, avvisi e comunicazioni massive.',
            'trigger' => 'manual',
        ],
    ];

    public function mount(): void
    {
        $configs = Automation::pluck('config', 'type');

        $review = $configs[Automation::TYPE_REVIEW_REQUEST] ?? [];
        $param = $review['review_url_param'] ?? null;

        if (filled($param)) {
            $this->reviewUrlMode = 'dynamic';
            $this->reviewUrlParam = (string) $param;
        }

        $this->fillWelcomeForm($configs[Automation::TYPE_WELCOME] ?? []);
        $this->fillReminderForm($configs[Automation::TYPE_APPOINTMENT_REMINDER] ?? []);
    }

    /** @param  array<string, mixed>  $config */
    private function fillWelcomeForm(array $config): void
    {
        $this->welcomeGreeting = (string) ($config['greeting'] ?? WelcomeFlow::DEFAULT_GREETING);
        $this->welcomeHeader = $config['header'] ?? null;
        $this->welcomeFooter = $config['footer'] ?? null;

        $saved = array_values($config['buttons'] ?? WelcomeFlow::DEFAULT_BUTTONS);
        $replies = $config['replies'] ?? [];

        $this->welcomeButtons = [];

        for ($i = 0; $i < self::WELCOME_BUTTON_SLOTS; $i++) {
            // Slot vuoti oltre i bottoni salvati: l'id di default tiene lo slot
            // identificabile senza esporlo all'utente.
            $id = (string) ($saved[$i]['id'] ?? WelcomeFlow::DEFAULT_BUTTONS[$i]['id']);

            $this->welcomeButtons[] = [
                'id' => $id,
                'title' => (string) ($saved[$i]['title'] ?? ''),
                'reply' => (string) ($replies[$id] ?? ''),
            ];
        }
    }

    /** @param  array<string, mixed>  $config */
    private function fillReminderForm(array $config): void
    {
        $this->reminderTemplate = (string) ($config['template'] ?? AppointmentReminder::DEFAULT_TEMPLATE);
        $this->reminderLanguage = (string) ($config['language'] ?? 'it');
        $this->reminderOffsets = implode(', ', $config['offsets'] ?? AppointmentReminder::DEFAULT_OFFSETS);
        $this->reminderConfirm = (string) ($config['confirm_payload'] ?? AppointmentReminder::DEFAULT_CONFIRM_PAYLOAD);
        $this->reminderCancel = (string) ($config['cancel_payload'] ?? AppointmentReminder::DEFAULT_CANCEL_PAYLOAD);
    }

    public function toggle(string $type): void
    {
        if (! array_key_exists($type, self::FLOWS)) {
            return;
        }

        $tenant = auth()->user()?->tenant;

        try {
            $automation = Automation::where('type', $type)->first();

            $activating = $automation ? ! $automation->active : true;

            // Enforcement limiti di piano (E4.2.6): blocca l'attivazione di una
            // nuova automazione oltre il numero consentito dal piano.
            if ($activating && $tenant && ! PlanLimits::for($tenant)->canActivateAnotherAutomation()) {
                $limit = PlanLimits::for($tenant)->automationsLimit();
                $this->dispatch('toast', type: 'error', message: "Il tuo piano consente fino a {$limit} automazioni attive. Passa a un piano superiore per attivarne altre.");

                return;
            }

            if ($automation) {
                $automation->update(['active' => ! $automation->active]);

                return;
            }

            Automation::create([
                'type' => $type,
                'trigger' => self::FLOWS[$type]['trigger'],
                'config' => [],
                'active' => true,
            ]);
        } catch (TenantContextException $e) {
            // Errore di dominio atteso (es. super-admin senza tenant): mostra un
            // toast col messaggio invece di propagare un 500. Vedi <x-toast-hub />.
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Salva i testi del menu di benvenuto (E3.1 / E4.2.3): saluto, header/footer
     * e fino a tre bottoni con la risposta associata. Gli id dei bottoni non sono
     * modificabili: sono la chiave con cui il webhook instrada la risposta.
     */
    public function saveWelcomeSettings(): void
    {
        $this->validate([
            'welcomeGreeting' => ['required', 'string', 'max:1024'],
            'welcomeHeader' => ['nullable', 'string', 'max:60'],
            'welcomeFooter' => ['nullable', 'string', 'max:60'],
            'welcomeButtons.*.title' => ['nullable', 'string', 'max:20'],
            'welcomeButtons.*.reply' => ['nullable', 'string', 'max:1024'],
        ], attributes: [
            'welcomeGreeting' => 'messaggio di benvenuto',
            'welcomeHeader' => 'titolo',
            'welcomeFooter' => 'nota finale',
        ]);

        $slots = collect($this->welcomeButtons)
            ->map(fn (array $b) => [
                'id' => $b['id'],
                'title' => trim((string) $b['title']),
                'reply' => trim((string) $b['reply']),
            ])
            ->filter(fn (array $b) => $b['title'] !== '')
            ->values();

        // Meta accetta da 1 a 3 reply button: un menu senza bottoni fallirebbe in invio.
        if ($slots->isEmpty()) {
            $this->addError('welcomeButtons.0.title', 'Serve almeno un bottone nel menu.');

            return;
        }

        $saved = $this->updateConfig(Automation::TYPE_WELCOME, [
            'greeting' => trim($this->welcomeGreeting),
            'header' => filled($this->welcomeHeader) ? trim($this->welcomeHeader) : null,
            'footer' => filled($this->welcomeFooter) ? trim($this->welcomeFooter) : null,
            'buttons' => $slots->map(fn (array $b) => ['id' => $b['id'], 'title' => $b['title']])->all(),
            'replies' => $slots->filter(fn (array $b) => $b['reply'] !== '')
                ->pluck('reply', 'id')
                ->all(),
        ]);

        if ($saved) {
            $this->dispatch('toast', type: 'success', message: 'Menu di benvenuto salvato.');
        }
    }

    /**
     * Salva i parametri dei promemoria (E3.2 / E4.2.3): template Meta, lingua,
     * anticipi e testo dei bottoni di conferma/disdetta. I payload devono
     * coincidere con le label del template (vedi docs/META-TEMPLATES.md §1).
     */
    public function saveReminderSettings(): void
    {
        $this->validate([
            'reminderTemplate' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'reminderLanguage' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            'reminderOffsets' => ['required', 'string', 'max:100'],
            // Limite Meta sul testo di un quick-reply.
            'reminderConfirm' => ['required', 'string', 'max:25'],
            'reminderCancel' => ['required', 'string', 'max:25'],
        ], messages: [
            'reminderTemplate.regex' => 'Il nome del template Meta può contenere solo lettere minuscole, numeri e underscore.',
            'reminderLanguage.regex' => 'Codice lingua non valido: usa il formato "it" oppure "en_US".',
        ], attributes: [
            'reminderTemplate' => 'nome del template',
            'reminderOffsets' => 'anticipi',
            'reminderConfirm' => 'bottone di conferma',
            'reminderCancel' => 'bottone di disdetta',
        ]);

        $offsets = $this->parseOffsets($this->reminderOffsets);

        if ($offsets === null) {
            $this->addError('reminderOffsets', 'Indica gli anticipi come ore separate da virgola, tra 1 e 720 (es. 24, 2).');

            return;
        }

        $saved = $this->updateConfig(Automation::TYPE_APPOINTMENT_REMINDER, [
            'template' => trim($this->reminderTemplate),
            'language' => trim($this->reminderLanguage),
            'offsets' => $offsets,
            'confirm_payload' => trim($this->reminderConfirm),
            'cancel_payload' => trim($this->reminderCancel),
        ]);

        if ($saved) {
            $this->reminderOffsets = implode(', ', $offsets);
            $this->dispatch('toast', type: 'success', message: 'Promemoria salvati.');
        }
    }

    /**
     * Ore di anticipo da lista separata da virgole, ordinate dalla più lontana
     * alla più vicina. Null se un valore non è un intero di ore plausibile:
     * meglio un errore che scartare in silenzio un anticipo che il tenant crede attivo.
     *
     * @return array<int, int>|null
     */
    private function parseOffsets(string $raw): ?array
    {
        $tokens = array_filter(array_map('trim', explode(',', $raw)), fn (string $t) => $t !== '');

        if ($tokens === []) {
            return null;
        }

        $offsets = [];

        foreach ($tokens as $token) {
            if (! ctype_digit($token) || (int) $token < 1 || (int) $token > 720) {
                return null;
            }

            $offsets[] = (int) $token;
        }

        $offsets = array_values(array_unique($offsets));
        rsort($offsets);

        return $offsets;
    }

    /**
     * Merge dei valori nella config dell'Automation, preservando le altre chiavi.
     * Ritorna false (con toast) se il flusso non è ancora stato attivato.
     *
     * @param  array<string, mixed>  $values
     */
    private function updateConfig(string $type, array $values): bool
    {
        $automation = Automation::where('type', $type)->first();

        if (! $automation) {
            $this->dispatch('toast', type: 'error', message: 'Attiva prima il flusso per poterlo configurare.');

            return false;
        }

        try {
            $automation->update(['config' => array_merge($automation->config ?? [], $values)]);
        } catch (TenantContextException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Add-on Recensioni (E4.2.6): la Richiesta recensione non è un flusso core
     * ma un add-on. Attivabile solo se il tenant ne ha diritto (piano Business
     * o concesso). Non conta nel limite automazioni del piano.
     */
    public function toggleReviews(): void
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant || ! PlanLimits::for($tenant)->hasReviewsAddon()) {
            $this->dispatch('toast', type: 'error', message: 'La Richiesta recensione è un add-on: attivala dal piano Business o contatta l\'assistenza.');

            return;
        }

        try {
            $automation = Automation::where('type', Automation::TYPE_REVIEW_REQUEST)->first();

            if ($automation) {
                $automation->update(['active' => ! $automation->active]);

                return;
            }

            Automation::create([
                'type' => Automation::TYPE_REVIEW_REQUEST,
                'trigger' => 'schedule',
                'config' => [],
                'active' => true,
            ]);
        } catch (TenantContextException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Salva la configurazione del link recensione sull'Automation `review_request`
     * (E3.3): chiude gli stati incoerenti del button URL del template (vedi
     * docs/META-TEMPLATES.md §2). Modalità `static` → nessun param (link nel
     * template); `dynamic` → suffisso obbligatorio passato al button URL.
     */
    public function saveReviewSettings(): void
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant || ! PlanLimits::for($tenant)->hasReviewsAddon()) {
            $this->dispatch('toast', type: 'error', message: 'La Richiesta recensione è un add-on: attivala dal piano Business o contatta l\'assistenza.');

            return;
        }

        $this->validate([
            'reviewUrlMode' => ['required', 'in:static,dynamic'],
            // Suffisso del button URL, non un URL completo → niente regola `url`;
            // obbligatorio e senza spazi solo in modalità dinamica.
            'reviewUrlParam' => $this->reviewUrlMode === 'dynamic'
                ? ['required', 'string', 'max:200', 'regex:/^\S+$/']
                : ['nullable'],
        ], attributes: [
            'reviewUrlParam' => 'link recensione',
        ]);

        $param = $this->reviewUrlMode === 'dynamic' ? trim((string) $this->reviewUrlParam) : null;

        if ($this->updateConfig(Automation::TYPE_REVIEW_REQUEST, ['review_url_param' => $param])) {
            $this->reviewUrlParam = $param;
            $this->dispatch('toast', type: 'success', message: 'Impostazioni recensione salvate.');
        }
    }

    public function render(): View
    {
        $active = Automation::pluck('active', 'type');
        $tenant = auth()->user()?->tenant;

        return view('livewire.automations', [
            'flows' => self::FLOWS,
            'active' => $active,
            'hasReviews' => $tenant ? PlanLimits::for($tenant)->hasReviewsAddon() : false,
            'reviewActive' => (bool) ($active[Automation::TYPE_REVIEW_REQUEST] ?? false),
        ]);
    }
}
