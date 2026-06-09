<?php

use App\Http\Controllers\WhatsApp\WebhookController;
use Illuminate\Support\Facades\Route;

/*
| Webhook Meta WhatsApp Cloud API (api.replisa.com/webhook).
| Registrate fuori dal gruppo `web`: nessun CSRF/sessione. Il POST è
| protetto dalla firma X-Hub-Signature-256 (middleware meta.signature).
*/

Route::get('/webhook', [WebhookController::class, 'verify']);

Route::post('/webhook', [WebhookController::class, 'handle'])
    ->middleware('meta.signature');
