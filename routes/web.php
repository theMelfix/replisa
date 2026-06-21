<?php

use App\Livewire\AcceptInvitation;
use App\Livewire\Admin\Tenants;
use App\Livewire\Appointments;
use App\Livewire\Automations;
use App\Livewire\Billing;
use App\Livewire\Campaigns;
use App\Livewire\Contacts;
use App\Livewire\Dashboard;
use App\Livewire\Messages;
use App\Livewire\WhatsAppSettings;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

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
    });

require __DIR__.'/auth.php';
