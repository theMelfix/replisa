<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Tenant;
use App\Services\Automation\ReviewRequest;
use App\Support\PlanLimits;
use Illuminate\Console\Command;

/**
 * Comando schedulato (task 3.3.2): per ogni tenant con il flusso recensioni
 * attivo, invia le richieste dovute dopo gli appuntamenti completati. Pensato
 * per girare ogni ora via Laravel Scheduler (vedi routes/console.php).
 */
class SendReviewRequests extends Command
{
    protected $signature = 'replisa:send-review-requests';

    protected $description = 'Invia le richieste di recensione dovute (post appuntamento completato)';

    public function handle(): int
    {
        $total = 0;

        Tenant::query()
            ->whereHas('automations', fn ($q) => $q
                ->where('type', Automation::TYPE_REVIEW_REQUEST)
                ->where('active', true))
            ->each(function (Tenant $tenant) use (&$total) {
                // Gating add-on: la Richiesta recensione richiede l'add-on Recensioni
                // (incluso nel piano Business o concesso dall'admin).
                if (! PlanLimits::for($tenant)->hasReviewsAddon()) {
                    return;
                }

                $total += ReviewRequest::for($tenant)->dispatchDue();
            });

        $this->info("Richieste recensione inviate: {$total}");

        return self::SUCCESS;
    }
}
