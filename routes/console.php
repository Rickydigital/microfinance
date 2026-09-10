<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('loans:send-reminders')->dailyAt('08:00')->withoutOverlapping()->onOneServer();
Schedule::command('loans:age-arrears')->dailyAt('00:30')->withoutOverlapping()->onOneServer();
