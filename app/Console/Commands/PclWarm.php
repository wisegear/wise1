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

        // ===== Warm ALL Prime Central (aggregate across all PCL outward codes) =====
        $this->newLine();
        $this->info('Warming ALL Prime Central (aggregate)');

        $keyBaseAll = 'pcl:v3:catA:ALL:'; // v3 to avoid stale cache from prior logic

        // Base query joining to DISTINCT PCL outward codes and matching by prefix
        $baseAll = DB::table('land_registry as lr')
            ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Prime Central') as pp"),
                DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
            ->where('lr.PPDCategoryType', 'A');

        // Avg price by year
        $avgPriceAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'avgPrice', $avgPriceAll, $ttl);

        // Sales count by year
        $salesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'sales', $salesAll, $ttl);

        // Property types by year
        $propertyTypesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
            ->groupBy('lr.YearDate', 'type')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'propertyTypes', $propertyTypesAll, $ttl);

        // 90th percentile per year via NTILE window
        $decilesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, NTILE(10) OVER (PARTITION BY lr.YearDate ORDER BY lr.Price) as decile');
        $p90All = DB::query()
            ->fromSub($decilesAll, 't')
            ->selectRaw('YearDate as year, MIN(Price) as p90')
            ->where('decile', 10)
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll . 'p90', $p90All, $ttl);

        // Top 5% average per year via window ranking
        $rankedTop5All = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');
        $top5All = DB::query()
            ->fromSub($rankedTop5All, 'ranked')
            ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
            ->whereRaw('rn <= CEIL(cnt * 0.05)')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll . 'top5', $top5All, $ttl);

        // Top sale per year (for scatter)
        $topSalePerYearAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'topSalePerYear', $topSalePerYearAll, $ttl);

        // Top 3 sales per year (for tooltip)
        $rankedTop3All = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.Date, lr.Postcode, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');
        $top3PerYearAll = DB::query()
            ->fromSub($rankedTop3All, 'r')
            ->select('year', 'Date', 'Postcode', 'Price', 'rn')
            ->where('rn', '<=', 3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBaseAll . 'top3PerYear', $top3PerYearAll, $ttl);

        // Last warm timestamp for the ALL aggregate namespace
        Cache::put('pcl:v3:catA:last_warm', now()->toIso8601String(), $ttl);

        // Global last warm timestamp
        Cache::put('pcl:v2:catA:last_warm', now()->toIso8601String(), $ttl);

        $this->newLine(2);
        $this->info('Prime Central cache warm complete.');

        return self::SUCCESS;
    }
}
