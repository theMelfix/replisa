<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

/**
 * Verifica che i Price ID configurati (`STRIPE_PRICE_*`) esistano davvero su
 * Stripe e corrispondano ai prezzi dichiarati in `config/plans.php`.
 *
 * Serve soprattutto al passaggio TEST → LIVE: copiando i prodotti in live mode
 * dal dashboard, Stripe genera **nuovi** Price ID. Se il .env resta con quelli
 * di test, il checkout fallisce ("No such price") al primo cliente reale.
 *
 * Controlla per ogni prezzo: esistenza, modalità (live/test), importo,
 * intervallo di fatturazione, valuta, stato attivo del prezzo e del prodotto.
 * Non stampa mai chiavi o segreti.
 */
class StripeCheck extends Command
{
    protected $signature = 'replisa:stripe-check';

    protected $description = 'Verifica i Price ID Stripe configurati contro config/plans.php';

    public function handle(): int
    {
        $secret = config('cashier.secret');

        if (! $secret) {
            $this->error('STRIPE_SECRET non configurata: non posso interrogare Stripe.');

            return self::FAILURE;
        }

        // Modalità dedotta dal prefisso della chiave (la chiave non viene mai stampata).
        $keyMode = str_starts_with($secret, 'sk_live_') ? 'LIVE' : 'TEST';
        $this->line("Chiave Stripe in uso: <options=bold>{$keyMode}</>");

        $expected = $this->expectedPrices();

        if ($expected === []) {
            $this->error('Nessun piano configurato in config/plans.php.');

            return self::FAILURE;
        }

        $rows = [];
        $problems = 0;
        $modes = [];

        foreach ($expected as $entry) {
            [$row, $ok, $mode] = $this->checkPrice($entry, $keyMode);

            $rows[] = $row;
            $problems += $ok ? 0 : 1;

            if ($mode !== null) {
                $modes[$mode] = true;
            }
        }

        $this->newLine();
        $this->table(['Voce', 'Price ID', 'Atteso', 'Su Stripe', 'Esito'], $rows);

        // Un mix di prezzi live e test è quasi sempre un .env aggiornato a metà.
        if (count($modes) > 1) {
            $this->newLine();
            $this->error('Prezzi in modalità MISTA (live + test): il .env è aggiornato solo in parte.');
            $problems++;
        }

        $this->newLine();

        if ($problems > 0) {
            $this->error("{$problems} problema/i rilevato/i. Il checkout NON è affidabile in questo stato.");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Tutti i %d prezzi sono coerenti con config/plans.php (modalità %s).',
            count($expected),
            $keyMode,
        ));

        return self::SUCCESS;
    }

    /**
     * Costruisce l'elenco dei prezzi attesi da config/plans.php: per ogni piano
     * il mensile e l'annuale, più gli add-on (solo mensili).
     *
     * @return array<int, array{label: string, id: ?string, amount: int, interval: string}>
     */
    private function expectedPrices(): array
    {
        $expected = [];

        foreach (config('plans.plans', []) as $key => $plan) {
            $expected[] = [
                'label' => $plan['name'].' mensile',
                'id' => $plan['stripe_price_id'] ?? null,
                'amount' => (int) round($plan['price'] * 100),
                'interval' => 'month',
            ];

            if (isset($plan['price_annual'])) {
                $expected[] = [
                    'label' => $plan['name'].' annuale',
                    'id' => $plan['stripe_price_id_annual'] ?? null,
                    'amount' => (int) round($plan['price_annual'] * 100),
                    'interval' => 'year',
                ];
            }
        }

        foreach (config('plans.addons', []) as $addon) {
            $expected[] = [
                'label' => 'Add-on '.$addon['name'],
                'id' => $addon['stripe_price_id'] ?? null,
                'amount' => (int) round($addon['price'] * 100),
                'interval' => 'month',
            ];
        }

        return $expected;
    }

    /**
     * Verifica un singolo prezzo su Stripe.
     *
     * @param  array{label: string, id: ?string, amount: int, interval: string}  $entry
     * @return array{0: array<int, string>, 1: bool, 2: ?string} riga tabella, esito, modalità
     */
    private function checkPrice(array $entry, string $keyMode): array
    {
        $atteso = $this->money($entry['amount']).'/'.($entry['interval'] === 'year' ? 'anno' : 'mese');

        if (! $entry['id']) {
            return [[$entry['label'], '—', $atteso, '—', '<fg=red>MANCANTE nel .env</>'], false, null];
        }

        try {
            $price = Cashier::stripe()->prices->retrieve($entry['id'], ['expand' => ['product']]);
        } catch (ApiErrorException $e) {
            // Il caso tipico è un Price ID di test con chiave live (o viceversa):
            // Stripe lo dice esplicitamente nel messaggio.
            return [[
                $entry['label'],
                $entry['id'],
                $atteso,
                '—',
                '<fg=red>'.$this->shorten($e->getMessage()).'</>',
            ], false, null];
        }

        $mode = $price->livemode ? 'LIVE' : 'TEST';
        $currency = strtolower((string) $price->currency);
        $interval = $price->recurring->interval ?? '—';
        $trovato = $this->money((int) $price->unit_amount).'/'
            .($interval === 'year' ? 'anno' : ($interval === 'month' ? 'mese' : $interval));

        $errori = [];

        if ((int) $price->unit_amount !== $entry['amount']) {
            $errori[] = 'importo diverso';
        }

        if ($interval !== $entry['interval']) {
            $errori[] = 'intervallo diverso';
        }

        if ($currency !== strtolower((string) config('cashier.currency', 'eur'))) {
            $errori[] = 'valuta '.$currency;
        }

        if (! $price->active) {
            $errori[] = 'prezzo archiviato';
        }

        if (is_object($price->product) && isset($price->product->active) && ! $price->product->active) {
            $errori[] = 'prodotto archiviato';
        }

        if ($mode !== $keyMode) {
            $errori[] = "prezzo {$mode} con chiave {$keyMode}";
        }

        $esito = $errori === []
            ? "<fg=green>ok ({$mode})</>"
            : '<fg=red>'.implode(', ', $errori).'</>';

        return [[$entry['label'], $entry['id'], $atteso, $trovato, $esito], $errori === [], $mode];
    }

    private function money(int $cents): string
    {
        return '€'.number_format($cents / 100, 2, ',', '.');
    }

    private function shorten(string $message): string
    {
        $message = trim(explode("\n", $message)[0]);

        return mb_strlen($message) > 70 ? mb_substr($message, 0, 67).'…' : $message;
    }
}
