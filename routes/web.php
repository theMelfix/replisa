<?php

use App\Http\Controllers\ReviewRedirectController;
use App\Livewire\AcceptInvitation;
use App\Livewire\Admin\Deadlines as AdminDeadlines;
use App\Livewire\Admin\Leads as AdminLeads;
use App\Livewire\Admin\Tenants;
use App\Livewire\ApiTokens;
use App\Livewire\Appointments;
use App\Livewire\Automations;
use App\Livewire\Billing;
use App\Livewire\Campaigns;
use App\Livewire\Contacts;
use App\Livewire\Dashboard;
use App\Livewire\Deadlines;
use App\Livewire\Messages;
use App\Livewire\WhatsAppSettings;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Short-link tracciato per le richieste recensione (E3.3.3): pubblico, registra
// il click e reindirizza all'URL recensioni Google del tenant.
Route::get('r/{token}', ReviewRedirectController::class)
    ->name('review.redirect');

// Pagine legali (richieste da Meta per la configurazione dell'app).
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/termini', 'legal.terms')->name('terms');
Route::view('/eliminazione-dati', 'legal.data-deletion')->name('data-deletion');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('dashboard');

Route::get('automations', Automations::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('automations');

Route::get('campaigns', Campaigns::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('campaigns');

Route::get('scadenze', Deadlines::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('deadlines');

Route::get('contacts', Contacts::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('contacts');

Route::get('messages', Messages::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('messages');

Route::get('appointments', Appointments::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('appointments');

Route::get('billing', Billing::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('billing');

Route::get('whatsapp', WhatsAppSettings::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('whatsapp');

// API pubblica (E4.3): gestione chiavi + documentazione per integrazioni.
Route::get('api-tokens', ApiTokens::class)
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('api-tokens');

Route::view('api-docs', 'pages.api-docs')
    ->middleware(['auth', 'verified'])
    ->name('api.docs');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Pagina mostrata ai tenant bloccati dall'admin (vedi middleware tenant.active).
Route::view('suspended', 'suspended')
    ->middleware(['auth'])
    ->name('suspended');

// Attivazione account su invito (E5): URL firmato inviato via email dall'admin.
Route::get('invito/{user}', AcceptInvitation::class)
    ->middleware('signed')
    ->name('invitation.accept');

// Area super-admin (E4.1.4): gestione di tutti i tenant.
Route::middleware(['auth', 'role:'.User::ROLE_SUPER_ADMIN])
    ->prefix('admin')
    ->group(function () {
        Route::get('tenants', Tenants::class)->name('admin.tenants');
        Route::get('deadlines', AdminDeadlines::class)->name('admin.deadlines');
        Route::get('leads', AdminLeads::class)->name('admin.leads');
    });

require __DIR__.'/auth.php';
