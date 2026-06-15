<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('videos:sync-youtube-playlist --promotion=wcw')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->environments(['production']);
