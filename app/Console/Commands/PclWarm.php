<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PclWarm extends Command
{
    /**
     * Keep the same signature so existing schedules/crons still work.
     */
    protected $signature = 'pcl:warm';

    protected $description = 'Warm the Prime Central London cache (Category A only)';

    public function handle(): int
    {
        $this->info('Starting Prime Central cache warm...');

        // District list for Prime Central
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Prime Central')
            ->pluck('postcode')
            ->unique()
            ->values();

        if ($districts->isEmpty()) {
            $this->warn('No Prime Central districts found.');
            return self::SUCCESS;
        }

        // Reduce memory usage
        DB::connection()->disableQueryLog();

        // TTL = 45 days
        $ttl = 60 * 60 * 24 * 45;

        $this->withProgressBar($districts, function (string $district) use ($ttl) {
            $keyBase = 'pcl:v2:catA:' . $district . ':';

            // Average price by year
            $avgPrice = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

            // Sales count by year
            $sales = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, COUNT(*) as sales')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'sales', $sales, $ttl);

            // Property types by year
            $propertyTypes = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate', 'type')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);

            // 90th percentile threshold per year via NTILE window
            $deciles = DB::table('land_registry')
                ->selectRaw('`YearDate`, `Price`, NTILE(10) OVER (PARTITION BY `YearDate` ORDER BY `Price`) as decile')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);

            $p90 = DB::query()
                ->fromSub($deciles, 't')
                ->selectRaw('`YearDate` as year, MIN(`Price`) as p90')
                ->where('decile', 10)
                ->groupBy('year')
                ->orderBy('year')
                ->get();
            Cache::put($keyBase . 'p90', $p90, $ttl);

            // Top 5% average per year via row_number/count windows
            $rankedTop5 = DB::table('land_registry')
                ->selectRaw('`YearDate`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);

            $top5 = DB::query()
                ->fromSub($rankedTop5, 'ranked')
                ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as top5_avg')
                ->whereRaw('rn <= CEIL(cnt * 0.05)')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
            Cache::put($keyBase . 'top5', $top5, $ttl);

            // Top sale per year (for spike marker)
            $topSalePerYear = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0)
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'topSalePerYear', $topSalePerYear, $ttl);

            // Top 3 sales per year (for context/tooltips)
            $rankedTop3 = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);

            $top3PerYear = DB::query()
                ->fromSub($rankedTop3, 'r')
                ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                ->where('rn', '<=', 3)
                ->orderBy('year')
                ->orderBy('rn')
                ->get();
            Cache::put($keyBase . 'top3PerYear', $top3PerYear, $ttl);
        });

        // Global last warm timestamp
        Cache::put('pcl:v2:catA:last_warm', now()->toIso8601String(), $ttl);

        $this->newLine(2);
        $this->info('Prime Central cache warm complete.');

        return self::SUCCESS;
    }
}
