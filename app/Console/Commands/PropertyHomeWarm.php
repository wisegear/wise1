<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PropertyHomeWarm extends Command
{
    /**
     * Keep the same signature so your existing schedule still works.
     */
    protected $signature = 'property:home-warm';

    protected $description = 'Warm the PropertyController homepage cache (England & Wales, Category A aggregates only)';

    public function handle(): int
    {
        $this->info('Starting PropertyController home cache warm (EW Cat A only)...');

        // Reduce memory usage during large aggregations
        DB::connection()->disableQueryLog();

        // TTL in seconds (45 days)
        $ttl = 60 * 60 * 24 * 45;

        // Each step warms one cache key; we wrap them to drive a progress bar
        $steps = collect([
            // England & Wales Cat A: sales by year
            function () use ($ttl) {
                $data = DB::table('land_registry')
                    ->selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('year')->orderBy('year')->get();

                Cache::put('land_registry_sales_by_year:catA:v2', $data, $ttl);
            },

            // England & Wales Cat A: average price by year
            function () use ($ttl) {
                $data = DB::table('land_registry')
                    ->selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('year')->orderBy('year')->get();

                Cache::put('land_registry_avg_price_by_year:catA:v2', $data, $ttl);
            },

            // England & Wales Cat A: P90 (threshold) by year
            function () use ($ttl) {
                $sub = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);

                $data = DB::query()->fromSub($sub, 't')
                    ->selectRaw('year, MIN(Price) as p90_price')
                    ->where('cd', '>=', 0.9)
                    ->groupBy('year')->orderBy('year')->get();

                Cache::put('ew:p90:catA:v1', $data, $ttl);
            },

            // England & Wales Cat A: Top 5% average by year
            function () use ($ttl) {
                $sub = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);

                $data = DB::query()->fromSub($sub, 'r')
                    ->selectRaw('year, ROUND(AVG(`Price`)) as top5_avg')
                    ->whereColumn('rn', '<=', DB::raw('CEIL(0.05*cnt)'))
                    ->groupBy('year')->orderBy('year')->get();

                Cache::put('ew:top5avg:catA:v1', $data, $ttl);
            },

            // England & Wales Cat A: Top sale per year (for scatter markers)
            function () use ($ttl) {
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0)
                    ->groupBy('YearDate')
                    ->orderBy('year')
                    ->get();
                Cache::put('ew:topSalePerYear:catA:v1', $data, $ttl);
            },
  
            // England & Wales Cat A: Top 3 sales per year (for scatter tooltip detail)
            function () use ($ttl) {
                $rankedTop3 = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);
  
                $data = DB::query()
                    ->fromSub($rankedTop3, 'r')
                    ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                    ->where('rn', '<=', 3)
                    ->orderBy('year')
                    ->orderBy('rn')
                    ->get();
  
                Cache::put('ew:top3PerYear:catA:v1', $data, $ttl);
            },
        ]);

        $this->withProgressBar($steps, function ($step) {
            $step();
        });

        $this->newLine(2);
        Cache::put('property:home:catA:last_warm', now()->toIso8601String(), $ttl);
        $this->info('PropertyController home cache warm complete (EW Cat A only).');

        return self::SUCCESS;
    }
}
