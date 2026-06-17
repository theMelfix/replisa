<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Pagine legali (richieste da Meta per la configurazione dell'app).
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/termini', 'legal.terms')->name('terms');
Route::view('/eliminazione-dati', 'legal.data-deletion')->name('data-deletion');
