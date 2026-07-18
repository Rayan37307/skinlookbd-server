<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared hosting has no long-running queue worker, so the cron-driven scheduler
// (`* * * * * php artisan schedule:run`) drains the queue once per minute instead.
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('carts:send-abandoned-reminders')->hourly()->withoutOverlapping();
