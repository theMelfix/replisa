<?php

/*
 * Copre solo i percorsi deterministici del comando: quelli che NON chiamano
 * l'API Stripe. La verifica vera (importi/modalità dei prezzi reali) si esegue
 * a mano con le chiavi in .env — vedi docs/GO-LIVE.md.
 */

it('fallisce con un messaggio chiaro se STRIPE_SECRET non è configurata', function () {
    config(['cashier.secret' => null]);

    $this->artisan('replisa:stripe-check')
        ->expectsOutputToContain('STRIPE_SECRET non configurata')
        ->assertFailed();
});

it('segnala i Price ID mancanti senza interrogare Stripe', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
        'plans.plans' => [
            'starter' => [
                'name' => 'Starter',
                'price' => 19,
                'stripe_price_id' => null,   // non configurato → deve emergere
            ],
        ],
        'plans.addons' => [],
    ]);

    $this->artisan('replisa:stripe-check')
        ->expectsOutputToContain('problema')
        ->assertFailed();
});

it('deduce la modalità dal prefisso della chiave senza stamparla', function () {
    config([
        'cashier.secret' => 'sk_live_segretissimo',
        'plans.plans' => [
            'starter' => ['name' => 'Starter', 'price' => 19, 'stripe_price_id' => null],
        ],
        'plans.addons' => [],
    ]);

    $this->artisan('replisa:stripe-check')
        ->expectsOutputToContain('LIVE')
        ->doesntExpectOutputToContain('sk_live_segretissimo')
        ->assertFailed();
});
