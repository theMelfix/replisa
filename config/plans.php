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
            'price' => 14,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'limits' => [
                'contacts' => 500,
                'automations' => 1,
            ],
        ],

        'base' => [
            'name' => 'Base',
            'price' => 39,
            'stripe_price_id' => env('STRIPE_PRICE_BASE'),
            'limits' => [
                'contacts' => 2000,
                'automations' => null,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price' => 79,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
            ],
        ],

        'business' => [
            'name' => 'Business',
            'price' => 149,
            'stripe_price_id' => env('STRIPE_PRICE_BUSINESS'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
            ],
        ],

    ],

];
