<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder appuntamento (E3.2): controlla ogni ora gli appuntamenti dovuti.
// Richiede un cron `php artisan schedule:run` sul VPS (vedi deploy/README.md).
Schedule::command('replisa:send-reminders')
    ->hourly()
    ->withoutOverlapping();

// Richiesta recensione (E3.3): controlla ogni ora gli appuntamenti completati
// pronti per la richiesta. Stesso cron `schedule:run` del reminder.
Schedule::command('replisa:send-review-requests')
    ->hourly()
    ->withoutOverlapping();
