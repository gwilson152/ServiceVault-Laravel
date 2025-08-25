<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule import sync command to run hourly
Schedule::command('import:sync')
    ->hourly()
    ->withoutOverlapping(30) // Prevent overlapping runs with 30-minute timeout
    ->onFailure(function () {
        \Log::error('Scheduled import sync failed');
    })
    ->appendOutputTo(storage_path('logs/import-sync.log'));
