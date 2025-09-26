<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-submit of expired declarations
Schedule::command('declarations:auto-submit')
    ->dailyAt('09:20')
    ->withoutOverlapping()
    ->runInBackground();
