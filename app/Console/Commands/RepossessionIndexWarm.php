<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class RepossessionIndexWarm extends Command
{
    protected $signature = 'repos:warm-index';
    protected $description = 'Warm repossession index page caches';

    public function handle()
    {
        $this->info('Warming repossession index…');

        Cache::forget('repos:index:v1');

        // Call the same logic as controller
        app(\App\Http\Controllers\RepossessionsController::class)
            ->index(request());

        $this->info('Repossession index warmed.');
    }
}
