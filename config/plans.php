<?php

/*
|--------------------------------------------------------------------------
| Piani di abbonamento (E4.2.6)
|--------------------------------------------------------------------------
|
| I 4 piani mostrati sulla landing, mappati ai Price ID di Stripe (prezzi
| ricorrenti mensili, creati nel dashboard Stripe — i valori vivono in .env,
| nessun segreto nel repo). I `limits` serviranno all'enforcement (numero
| contatti/automazioni); `null` = illimitato / tutte.
|
*/

return [

    // Piano applicato ai tenant senza abbonamento attivo (onboarding/free tier).
    'default' => 'starter',

    'plans' => [

        'starter' => [
            'name' => 'Starter',
            'price' => 29,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'limits' => [
                'contacts' => 500,
                'automations' => 1,
            ],
            'features' => [
                'campaigns' => false,
                'deadlines' => false,
            ],
        ],

        'base' => [
            'name' => 'Base',
            'price' => 69,
            'stripe_price_id' => env('STRIPE_PRICE_BASE'),
            'limits' => [
                'contacts' => 2000,
                'automations' => null,
            ],
            'features' => [
                'campaigns' => true,
                'deadlines' => true,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price' => 129,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
            ],
            'features' => [
                'campaigns' => true,
                'deadlines' => true,
            ],
        ],

        'business' => [
            'name' => 'Business',
            'price' => 249,
            'stripe_price_id' => env('STRIPE_PRICE_BUSINESS'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
            ],
            'features' => [
                'campaigns' => true,
                'deadlines' => true,
            ],
        ],

    ],

    /*
    | Servizi aggiuntivi (add-on) acquistabili separatamente o inclusi in alcuni
    | piani. `included_in`: chiavi piano che lo comprendono senza costo extra.
    */
    'addons' => [

        'reviews' => [
            'name' => 'Recensioni Google',
            'price' => 19,
            'stripe_price_id' => env('STRIPE_PRICE_REVIEWS_ADDON'),
            'included_in' => ['business'],
        ],

    ],

];
