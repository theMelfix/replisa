<?php

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

require __DIR__.'/auth.php';
