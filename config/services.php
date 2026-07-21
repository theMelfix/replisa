<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Meta WhatsApp Cloud API. Credenziali app-level (ADR-003); quelle
    // per-tenant (phone_number_id, access_token) vivono sulla tabella `tenants`.
    'meta' => [
        'graph_version' => env('META_GRAPH_API_VERSION', 'v25.0'),
        'app_id' => env('META_WHATSAPP_APP_ID'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'webhook_verify_token' => env('META_WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

        // Fallback sandbox per i test in locale (vedi ADR-003: in prod è un tenant).
        'sandbox' => [
            'phone_number_id' => env('META_WHATSAPP_SANDBOX_PHONE_NUMBER_ID'),
            'waba_id' => env('META_WHATSAPP_SANDBOX_WABA_ID'),
            'access_token' => env('META_WHATSAPP_SANDBOX_ACCESS_TOKEN'),
        ],
    ],

    // Destinatario delle richieste di contatto/demo dalla landing (E5.1.2).
    // Default all'indirizzo di supporto già usato nelle pagine legali.
    'contact' => [
        'notify_email' => env('CONTACT_NOTIFY_EMAIL', 'info@giovannimelfi.it'),
    ],

];
