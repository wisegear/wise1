<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomepageStatsWarm extends Command
{
    protected $signature = 'home:stats-warm';
    protected $description = 'Warm cached stats displayed on the homepage';

    public function handle(): int
    {
        $this->info('Warming homepage stats cache...');

        $ttl = now()->addDays(30);

        // Total property records in land_registry
        $this->line('→ Counting property records...');
        $propertyCount = DB::table('land_registry')->count();
        $this->line("   " . number_format($propertyCount) . " records");

        // UK average property price from HPI (latest)
        $this->line('→ Fetching UK average price from HPI...');
        $latestHpi = DB::table('hpi_monthly')
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date', 'desc')
            ->first();
        $ukAvgPrice = $latestHpi->AveragePrice ?? 0;
        $this->line("   £" . number_format($ukAvgPrice));

        // Latest bank rate
        $this->line('→ Fetching latest bank rate...');
        $latestRate = DB::table('interest_rates')
            ->orderBy('effective_date', 'desc')
            ->first();
        $bankRate = $latestRate->rate ?? 0;
        $this->line("   {$bankRate}%");

        // Total EPC records
        $this->line('→ Counting EPC records...');
        $epcCount = DB::table('epc_certificates')->count();
        $this->line("   " . number_format($epcCount) . " records");

        // Store in cache
        $stats = [
            'property_records' => $propertyCount,
            'uk_avg_price' => round($ukAvgPrice),
            'bank_rate' => $bankRate,
            'epc_count' => $epcCount,
        ];

        Cache::put('homepage_stats', $stats, $ttl);
        $this->line('→ homepage_stats cached for 30 days');

        // Record last warm time
        Cache::put('homepage_stats:last_warm', now());

        $this->info('Homepage stats cache warmed successfully.');

        return Command::SUCCESS;
    }
}
