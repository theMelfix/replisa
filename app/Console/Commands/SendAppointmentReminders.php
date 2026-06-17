<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Tenant;
use App\Services\Automation\AppointmentReminder;
use Illuminate\Console\Command;

/**
 * Comando schedulato (task 3.2.2): per ogni tenant con il flusso reminder attivo,
 * invia i promemoria appuntamento dovuti. Pensato per girare ogni ora via
 * Laravel Scheduler (vedi routes/console.php).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'replisa:send-reminders';

    protected $description = 'Invia i reminder appuntamento dovuti (-24h / -2h)';

    public function handle(): int
    {
        $total = 0;

        Tenant::query()
            ->whereHas('automations', fn ($q) => $q
                ->where('type', Automation::TYPE_APPOINTMENT_REMINDER)
                ->where('active', true))
            ->each(function (Tenant $tenant) use (&$total) {
                $total += AppointmentReminder::for($tenant)->dispatchDue();
            });

        $this->info("Reminder appuntamento inviati: {$total}");

        return self::SUCCESS;
    }
}
