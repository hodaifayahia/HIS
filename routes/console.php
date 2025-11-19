<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule commands
Schedule::command('conventions:activate-scheduled')->dailyAt('00:01');
Schedule::command('reservations:cancel-expired')->everyMinute();
