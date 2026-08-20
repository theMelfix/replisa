<?php

namespace App\Livewire\Admin;

use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Panoramica di piattaforma: la home del super-admin.
 *
 * Serve a rispondere a una domanda sola — *sta funzionando tutto?* — prima che
 * sia il cliente a telefonare. Finora i due segnali che contano erano invisibili:
 * i messaggi con `status = failed` (la colonna `error` esiste ed è popolata da
 * tre punti diversi del codice, ma nessuna schermata la leggeva) e la tabella
 * `failed_jobs`, che raccoglie i fallimenti di promemoria, campagne e webhook,
 * cioè proprio le automazioni che il cliente paga.
 *
 * Tutte le query girano **senza** {@see TenantScope}: qui si guarda l'intera
 * piattaforma, e dirlo esplicitamente evita di dipendere dal fatto che lo scope
 * sia un no-op per i super-admin.
 */
#[Layout('layouts.app')]
class Overview extends Component
{
    /** Finestra di osservazione per i messaggi falliti. */
    public const FAILURE_DAYS = 7;

    /** Soglia di preavviso per le prove gratuite in scadenza. */
    public const TRIAL_WARNING_DAYS = 7;

    /** Quante righe mostrare in ciascun elenco di dettaglio. */
    public const LIST_LIMIT = 5;

    /**
     * Messaggi falliti nella finestra, con tenant e motivo leggibile.
     *
     * @return Collection<int, Message>
     */
    protected function recentFailures(): Collection
    {
        return Message::withoutGlobalScope(TenantScope::class)
            ->with('tenant')
            ->where('status', Message::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDays(self::FAILURE_DAYS))
            ->latest()
            ->limit(self::LIST_LIMIT)
            ->get();
    }

    /**
     * Ultimi job falliti. Il nome leggibile sta dentro il payload serializzato:
     * `displayName` è la classe del job (es. App\Jobs\SendCampaign).
     *
     * @return Collection<int, array{id: int, name: string, queue: string, failed_at: string}>
     */
    protected function recentFailedJobs(): Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(self::LIST_LIMIT)
            ->get(['id', 'queue', 'payload', 'failed_at'])
            ->map(fn ($job) => [
                'id' => (int) $job->id,
                'name' => class_basename(json_decode($job->payload, true)['displayName'] ?? 'Job sconosciuto'),
                'queue' => (string) $job->queue,
                'failed_at' => (string) $job->failed_at,
            ]);
    }

    public function render(): View
    {
        $tenants = Tenant::query()->orderBy('name')->get();

        $failuresQuery = fn () => Message::withoutGlobalScope(TenantScope::class)
            ->where('status', Message::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDays(self::FAILURE_DAYS));

        return view('livewire.admin.overview', [
            // Numeri di piattaforma.
            'activeCount' => $tenants->where('active', true)->count(),
            'blockedCount' => $tenants->where('active', false)->count(),
            'trialCount' => $tenants->filter(fn (Tenant $t) => PlanLimits::for($t)->planSource() === 'trial')->count(),
            'mrr' => $tenants->sum(fn (Tenant $t) => PlanLimits::for($t)->planSource() === 'default'
                ? 0
                : PlanLimits::for($t)->price()),
            'messagesThisMonth' => Message::withoutGlobalScope(TenantScope::class)
                ->where('direction', Message::DIRECTION_OUTBOUND)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),

            // Segnali che richiedono attenzione.
            'failuresCount' => $failuresQuery()->count(),
            'recentFailures' => $this->recentFailures(),
            'failedJobsCount' => DB::table('failed_jobs')->count(),
            'recentFailedJobs' => $this->recentFailedJobs(),
            'notConnected' => $tenants->filter(fn (Tenant $t) => ! $t->hasWhatsAppConfigured()),
            'expiringTrials' => $tenants->filter(fn (Tenant $t) => $t->trial_ends_at
                && $t->trial_ends_at->isFuture()
                && $t->trial_ends_at->lte(now()->addDays(self::TRIAL_WARNING_DAYS))
            )->sortBy('trial_ends_at'),
            'expiredLicenses' => $tenants->filter(fn (Tenant $t) => $t->manual_plan
                && ! $t->hasActiveOfflineLicense()
            ),
        ]);
    }
}
