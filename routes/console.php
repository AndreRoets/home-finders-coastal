<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
*/

// Ship the website's listing engagement counters back to CoreX. Hourly is
// enough for a CRM dashboard and keeps each batch small; anything a run fails
// to deliver stays outstanding and rides along with the next one. The daily
// pass also prunes fully-pushed rows outside the retention window.
Schedule::command('corex:push-stats')->hourly()->withoutOverlapping();
Schedule::command('corex:push-stats --prune')->dailyAt('03:20')->withoutOverlapping();
