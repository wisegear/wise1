<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Schedule: run monthly (2nd of the month at 02:15)
Schedule::command('pcl:warm')->monthlyOn(2, '02:15');

// Schedule: run monthly (2nd of the month at 02:30)
Schedule::command('property:home-warm')->monthlyOn(2, '02:30');

// Schedule: run monthly (2nd of the month at 02:15)
Schedule::command('upcl:warm')->monthlyOn(2, '02:15');

// Schedule: run monthly (2nd of the month at 02:45)
Schedule::command('warm:all-county')->monthlyOn(2, '02:45');

// Schedule: run monthly (2nd of the month at 03:00)
Schedule::command('warm:all-district')->monthlyOn(2, '03:00');

// Schedule: run monthly (2nd of the month at 03:15)
Schedule::command('warm:all-town')->monthlyOn(2, '03:15');
