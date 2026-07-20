<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API pubblica v1 (E4.3)
|--------------------------------------------------------------------------
| Autenticazione via token Sanctum (`Authorization: Bearer <token>`), emesso
| per un utente e operante nel contesto del suo tenant. `tenant.active` blocca
| gli account sospesi (risposta JSON 403); `throttle` protegge dagli abusi.
*/

Route::middleware(['auth:sanctum', 'tenant.active', 'throttle:api'])
    ->prefix('v1')
    ->group(function () {
        Route::get('/user', fn (Request $request) => $request->user()->only('id', 'name', 'email', 'tenant_id'));

        Route::post('/messages/send', [MessageController::class, 'send']);
        Route::get('/messages', [MessageController::class, 'index']);

        Route::post('/appointments', [AppointmentController::class, 'store']);
    });
