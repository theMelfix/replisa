<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard overview (E4.2.1): numeri chiave, andamento messaggi degli ultimi
 * giorni e costo Meta stimato del mese. Tutte le query sono filtrate
 * automaticamente per tenant dal {@see TenantScope}.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    /** Giorni coperti dal grafico andamento messaggi. */
    public const TREND_DAYS = 14;

    /**
     * Il super-admin non appartiene ad alcun tenant e il {@see TenantScope} per
     * lui è un no-op: questa dashboard gli mostrerebbe i totali di *tutti* i
     * tenant sommati, un numero che non descrive niente. La sua home è l'area
     * di piattaforma.
     */
    public function mount(): void
    {
        if (auth()->user()->isSuperAdmin()) {
            $this->redirect(route('admin.overview'), navigate: true);
        }
    }

    public function render(): View
    {
        $trend = $this->messageTrend();

        return view('livewire.dashboard', [
            'inviati' => Message::where('direction', Message::DIRECTION_OUTBOUND)->count(),
            'ricevuti' => Message::where('direction', Message::DIRECTION_INBOUND)->count(),
            'contatti' => Contact::count(),
            'conversazioniAttive' => Conversation::where('expires_at', '>', now())->count(),
            'appuntamentiProssimi' => Appointment::where('status', Appointment::STATUS_SCHEDULED)
                ->where('scheduled_at', '>', now())
                ->count(),
            'trend' => $trend,
            'trendMax' => (int) $trend->flatMap(fn ($d) => [$d['inviati'], $d['ricevuti']])->max(),
            'costoStimato' => $this->estimatedMonthlyCost(),
            'templateMese' => $this->billableTemplatesThisMonth(),
        ]);
    }

    /**
     * Conteggio inviati/ricevuti per ciascuno degli ultimi TREND_DAYS giorni,
     * dal più vecchio al più recente (ogni giorno presente anche se a zero).
     *
     * @return Collection<int, array{label: string, inviati: int, ricevuti: int}>
     */
    private function messageTrend(): Collection
    {
        $from = now()->startOfDay()->subDays(self::TREND_DAYS - 1);

        // Aggrega in DB per giorno+direzione, poi riempi i buchi lato PHP così
        // i giorni senza messaggi restano visibili nel grafico.
        $rows = Message::query()
            ->where('created_at', '>=', $from)
            ->get(['direction', 'created_at'])
            ->groupBy(fn (Message $m) => $m->created_at->format('Y-m-d'));

        return collect(range(0, self::TREND_DAYS - 1))->map(function (int $i) use ($from, $rows) {
            $day = $from->copy()->addDays($i);
            $ofDay = $rows->get($day->format('Y-m-d'), collect());

            return [
                'label' => $day->format('d/m'),
                'inviati' => $ofDay->where('direction', Message::DIRECTION_OUTBOUND)->count(),
                'ricevuti' => $ofDay->where('direction', Message::DIRECTION_INBOUND)->count(),
            ];
        });
    }

    /**
     * Template inviati nel mese corrente: sono gli invii che aprono una
     * conversazione business-initiated, l'evento che Meta fattura.
     */
    private function billableTemplatesThisMonth(): int
    {
        return Message::where('direction', Message::DIRECTION_OUTBOUND)
            ->where('type', Message::TYPE_TEMPLATE)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    /**
     * Costo Meta stimato del mese: template fatturabili × tariffa stimata.
     * È un ordine di grandezza (la fattura reale la emette Meta) — vedi
     * config/whatsapp.php.
     */
    private function estimatedMonthlyCost(): float
    {
        return round(
            $this->billableTemplatesThisMonth() * (float) config('whatsapp.pricing.estimate_rate'),
            2,
        );
    }
}
