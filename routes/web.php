<?php

use App\Livewire\Admin\Tenants;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Pagine legali (richieste da Meta per la configurazione dell'app).
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/termini', 'legal.terms')->name('terms');
Route::view('/eliminazione-dati', 'legal.data-deletion')->name('data-deletion');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Area super-admin (E4.1.4): gestione di tutti i tenant.
Route::middleware(['auth', 'role:'.User::ROLE_SUPER_ADMIN])
    ->prefix('admin')
    ->group(function () {
        Route::get('tenants', Tenants::class)->name('admin.tenants');
    });

require __DIR__.'/auth.php';
