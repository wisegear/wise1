<?php

namespace App\Console\Commands;

use App\Http\Controllers\EconomicDashboardController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmEconomicDashboard extends Command
{
    protected $signature = 'eco:dashboard-warm';

    protected $description = 'Warm cached values used on the economic dashboard';

    public function handle(): int
    {
        $this->info('Warming economic dashboard caches...');

        $ttl = now()->addHours(6);
        $approvalsSeriesCode = 'LPMVTVX';

        // 1. Interest Rates (latest)
        $interest = DB::table('interest_rates')
            ->orderBy('effective_date', 'desc')
            ->first();
        Cache::put('eco:last_interest', $interest, $ttl);
        $this->line('→ eco:last_interest warmed');

        // 2. Inflation (latest CPIH)
        $inflation = DB::table('inflation_cpih_monthly')
            ->orderBy('date', 'desc')
            ->first();
        Cache::put('eco:last_inflation', $inflation, $ttl);
        $this->line('→ eco:last_inflation warmed');

        // 3. Wage Growth (latest)
        $wages = DB::table('wage_growth_monthly')
            ->orderBy('date', 'desc')
            ->first();
        Cache::put('eco:last_wages', $wages, $ttl);
        $this->line('→ eco:last_wages warmed');

        // 4. Unemployment (latest)
        $unemp = DB::table('unemployment_monthly')
            ->orderBy('date', 'desc')
            ->first();
        Cache::put('eco:last_unemployment', $unemp, $ttl);
        $this->line('→ eco:last_unemployment warmed');

        // 5. Mortgage Approvals (latest)
        $approvals = DB::table('mortgage_approvals')
            ->where('series_code', $approvalsSeriesCode)
            ->orderBy('period', 'desc')
            ->first();
        Cache::put('eco:last_approvals', $approvals, $ttl);
        $this->line('→ eco:last_approvals warmed');

        // 6. Repossessions (latest MLAR possessions series)
        $latestReposs = DB::table('mlar_arrears')
            ->where('description', 'In possession')
            ->orderBy('year', 'desc')
            ->orderByRaw("CASE quarter WHEN 'Q4' THEN 4 WHEN 'Q3' THEN 3 WHEN 'Q2' THEN 2 WHEN 'Q1' THEN 1 ELSE 0 END DESC")
            ->first();

        if ($latestReposs) {
            $repossObj = (object) [
                'year' => $latestReposs->year,
                'quarter' => $latestReposs->quarter,
                'total' => (float) $latestReposs->value,
            ];
            Cache::put('eco:last_reposs_v2', $repossObj, $ttl);
            $this->line('→ eco:last_reposs_v2 warmed');
        } else {
            Cache::forget('eco:last_reposs_v2');
            $this->line('→ eco:last_reposs_v2 cleared (no data)');
        }

        // 7. HPI (UK only, latest)
        $hpi = DB::table('hpi_monthly')
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date', 'desc')
            ->first();
        Cache::put('eco:last_hpi', $hpi, $ttl);
        $this->line('→ eco:last_hpi warmed');

        // Last warm marker (no TTL, keep forever)
        Cache::put('eco:dashboard:last_warm', now());
        $this->info('All economic dashboard caches warmed.');
        $this->line('Last warm recorded in eco:dashboard:last_warm');

        // Also compute total stress so the homepage gauge can render from cache.
        try {
            (new EconomicDashboardController)->index();
            $this->line('→ eco:total_stress refreshed');
        } catch (\Throwable $e) {
            $this->warn('→ eco:total_stress not refreshed: '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
