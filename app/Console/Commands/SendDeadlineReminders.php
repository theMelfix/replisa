<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaign;
use App\Models\Campaign;
use App\Models\DeadlineReminder;
use Illuminate\Console\Command;

/**
 * Promemoria scadenze (E3.2.6): per ogni promemoria attivo la cui scadenza è
 * entro la finestra "giorni di anticipo", avvia una campagna verso i contatti
 * opted-in del tenant (riusa {@see SendCampaign}). `dispatched_at` evita
 * reinvii. Schedulato ogni ora (stesso cron `schedule:run`).
 */
class SendDeadlineReminders extends Command
{
    protected $signature = 'replisa:send-deadline-reminders';

    protected $description = 'Avvia le campagne di promemoria scadenza dovute';

    public function handle(): int
    {
        $today = today();

        $due = DeadlineReminder::query()
            ->where('active', true)
            ->whereNull('dispatched_at')
            ->with(['deadline', 'tenant'])
            ->get()
            ->filter(function (DeadlineReminder $reminder) use ($today): bool {
                $deadline = $reminder->deadline;

                if (! $deadline || ! $deadline->active) {
                    return false;
                }

                $trigger = $deadline->due_date->copy()->subDays($reminder->days_before);

                return $today->between($trigger, $deadline->due_date);
            });

        $fired = 0;

        foreach ($due as $reminder) {
            $tenant = $reminder->tenant;

            if (! $tenant) {
                continue;
            }

            $recipients = $tenant->contacts()->where('opted_in', true)->count();

            if ($recipients === 0) {
                continue; // riprova al giro dopo finché ci sono contatti
            }

            $campaign = Campaign::create([
                'tenant_id' => $tenant->id,
                'name' => 'Promemoria: '.$reminder->deadline->name,
                'template_name' => $reminder->template_name,
                'language' => $reminder->language,
                'include_name' => $reminder->include_name,
                'status' => Campaign::STATUS_PENDING,
                'total' => $recipients,
            ]);

            SendCampaign::dispatch($campaign);
            $reminder->update(['dispatched_at' => now()]);
            $fired++;
        }

        $this->info("Promemoria scadenza avviati: {$fired}.");

        return self::SUCCESS;
    }
}
