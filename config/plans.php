<?php

/*
|--------------------------------------------------------------------------
| Piani di abbonamento (E4.2.6)
|--------------------------------------------------------------------------
|
| I 4 piani mappati ai Price ID di Stripe (mensile e annuale -20%; i valori
| vivono in .env). `limits.campaign_messages` = messaggi campagna inclusi al
| mese (null = illimitato; l'extra a consumo è un follow-up). `features` =
| funzioni incluse. Trial: i nuovi tenant provano `trial_plan` per `trial_days`.
|
*/

return [

    // Piano applicato ai tenant senza abbonamento/licenza/trial attivi.
    'default' => 'starter',

    // Prova gratuita (no carta): i nuovi tenant usano questo piano per N giorni.
    'trial_plan' => 'pro',
    'trial_days' => 14,

    'plans' => [

        'starter' => [
            'name' => 'Starter',
            'price' => 19,
            'price_annual' => 182, // ~ -20% su 12 mesi
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'stripe_price_id_annual' => env('STRIPE_PRICE_STARTER_ANNUAL'),
            'limits' => [
                'contacts' => 500,
                'automations' => 1,
                'campaign_messages' => 0,
            ],
            'features' => [
                'campaigns' => false,
                'deadlines' => false,
            ],
        ],

        'base' => [
            'name' => 'Base',
            'price' => 49,
            'price_annual' => 470,
            'stripe_price_id' => env('STRIPE_PRICE_BASE'),
            'stripe_price_id_annual' => env('STRIPE_PRICE_BASE_ANNUAL'),
            'limits' => [
                'contacts' => 2000,
                'automations' => null,
                'campaign_messages' => 1000,
            ],
            'features' => [
                'campaigns' => true,
                'deadlines' => true,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price' => 99,
            'price_annual' => 950,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'stripe_price_id_annual' => env('STRIPE_PRICE_PRO_ANNUAL'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
                'campaign_messages' => 5000,
            ],
            'features' => [
                'campaigns' => true,
                'deadlines' => true,
            ],
        ],

        'business' => [
            'name' => 'Business',
            'price' => 199,
            'price_annual' => 1910,
            'stripe_price_id' => env('STRIPE_PRICE_BUSINESS'),
            'stripe_price_id_annual' => env('STRIPE_PRICE_BUSINESS_ANNUAL'),
            'limits' => [
                'contacts' => null,
                'automations' => null,
                'campaign_messages' => 20000,
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
