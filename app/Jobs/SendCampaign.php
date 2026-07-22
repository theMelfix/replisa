<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Invia una campagna (E3.4): manda il template MARKETING della campagna a tutti
 * i contatti opted-in del tenant, loggando ogni messaggio (via WhatsAppService)
 * e aggiornando i conteggi. Gira in coda (worker già attivo in prod).
 */
class SendCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Campaign $campaign) {}

    public function handle(): void
    {
        $campaign = $this->campaign;
        $tenant = $campaign->tenant;

        if (! $tenant || $campaign->status !== Campaign::STATUS_PENDING) {
            return;
        }

        $campaign->update(['status' => Campaign::STATUS_SENDING, 'started_at' => now()]);

        $service = WhatsAppService::for($tenant);
        $sent = 0;
        $failed = 0;

        // Destinatari del segmento della campagna (opt-in + eventuale etichetta).
        // Stessa query del conteggio nel compositore (Contact::scopeCampaignRecipients).
        $tenant->contacts()->campaignRecipients($campaign->tag_id)
            ->chunkById(200, function ($contacts) use ($campaign, $service, &$sent, &$failed): void {
                foreach ($contacts as $contact) {
                    try {
                        $service->sendTemplate(
                            $contact,
                            $campaign->template_name,
                            $campaign->language,
                            $campaign->include_name ? $this->nameComponents($contact) : [],
                        );
                        $sent++;
                    } catch (Throwable $e) {
                        report($e);
                        $failed++;
                    }
                }

                $campaign->update(['sent_count' => $sent, 'failed_count' => $failed]);
            });

        $campaign->update([
            'status' => Campaign::STATUS_COMPLETED,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'completed_at' => now(),
        ]);
    }

    /** Body param {{1}} = nome del contatto. */
    private function nameComponents(Contact $contact): array
    {
        return [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $contact->name ?: 'Cliente'],
            ],
        ]];
    }
}
