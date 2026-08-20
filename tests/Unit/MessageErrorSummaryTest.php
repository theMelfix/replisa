<?php

use App\Models\Message;

/**
 * `Message::errorSummary()` deve reggere le tre forme diverse con cui la colonna
 * `error` viene scritta nel codice, perché la Panoramica admin le mostra tutte
 * nello stesso elenco.
 */
it('legge l\'oggetto errore restituito da Meta sull\'invio', function () {
    $message = new Message(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190]]);

    expect($message->errorSummary())->toBe('[190] Invalid OAuth access token');
});

it('preferisce il dettaglio di error_data, che è la spiegazione più specifica', function () {
    $message = new Message(['error' => [
        'message' => 'Message Undeliverable',
        'code' => 131047,
        'error_data' => ['details' => 'More than 24 hours have passed since the last message'],
    ]]);

    expect($message->errorSummary())->toBe('[131047] More than 24 hours have passed since the last message');
});

it('legge la lista di errori che arriva dagli status del webhook', function () {
    $message = new Message(['error' => [
        ['code' => 131026, 'title' => 'Message undeliverable'],
        ['code' => 999, 'title' => 'Secondo errore'],
    ]]);

    expect($message->errorSummary())->toBe('[131026] Message undeliverable');
});

it('legge un errore di connessione senza codice', function () {
    $message = new Message(['error' => ['type' => 'connection', 'message' => 'Connection timed out']]);

    expect($message->errorSummary())->toBe('Connection timed out');
});

it('non inventa nulla quando l\'errore manca o è vuoto', function () {
    expect((new Message(['error' => null]))->errorSummary())->toBeNull()
        ->and((new Message(['error' => []]))->errorSummary())->toBeNull()
        ->and((new Message(['error' => ['code' => 42]]))->errorSummary())->toBeNull();
});
