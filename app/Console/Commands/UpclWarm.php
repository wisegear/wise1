<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UpclWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Keep the same signature so your existing schedule line still works.
     */
    protected $signature = 'upcl:warm';

    /**
     * The console command description.
     */
    protected $description = 'Warm the Ultra Prime Central London cache';

    public function handle(): int
    {
        $this->info('Starting Ultra Prime cache warm...');

        // Ultra Prime postcode districts
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Ultra Prime')
            ->pluck('postcode')
            ->unique()
            ->values();

        if ($districts->isEmpty()) {
            $this->warn('No Ultra Prime districts found.');
            return self::SUCCESS;
        }

        // Reduce memory usage during large aggregations
        DB::connection()->disableQueryLog();

        // TTL in seconds (45 days)
        $ttl = 60 * 60 * 24 * 45;

        $this->withProgressBar($districts, function (string $district) use ($ttl) {
            $keyBase = 'upcl:v5:catA:' . $district . ':';

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

            // 90th percentile (threshold) per year via window function
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

            // Top 5% average per year via window ranking
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

        $this->newLine(2);

        // ===== Warm ALL Ultra Prime (aggregate across all UPCL outward codes) =====
        $this->info('Warming ALL Ultra Prime (aggregate)');
        $keyBaseAll = 'upcl:v5:catA:ALL:';

        $baseAll = DB::table('land_registry as lr')
            ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Ultra Prime') as pp"),
                DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
            ->where('lr.PPDCategoryType', 'A');

        // Avg price
        $avgPriceAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'avgPrice', $avgPriceAll, $ttl);

        // Sales
        $salesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'sales', $salesAll, $ttl);

        // Property types
        $propertyTypesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
            ->groupBy('lr.YearDate','type')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'propertyTypes', $propertyTypesAll, $ttl);

        // P90
        $decilesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, NTILE(10) OVER (PARTITION BY lr.YearDate ORDER BY lr.Price) as decile');
        $p90All = DB::query()->fromSub($decilesAll,'t')
            ->selectRaw('YearDate as year, MIN(Price) as p90')
            ->where('decile',10)
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll.'p90',$p90All,$ttl);

        // Top 5% avg
        $rankedTop5All = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');
        $top5All = DB::query()->fromSub($rankedTop5All,'ranked')
            ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
            ->whereRaw('rn <= CEIL(cnt * 0.05)')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll.'top5',$top5All,$ttl);

        // Top sale per year
        $topSalePerYearAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'topSalePerYear',$topSalePerYearAll,$ttl);

        // Top 3 per year
        $rankedTop3All = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.Date, lr.Postcode, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');
        $top3PerYearAll = DB::query()->fromSub($rankedTop3All,'r')
            ->select('year','Date','Postcode','Price','rn')
            ->where('rn','<=',3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBaseAll.'top3PerYear',$top3PerYearAll,$ttl);

        Cache::put('upcl:v5:catA:last_warm', now()->toIso8601String(), $ttl);

        // Record per-district last warm timestamp
        Cache::put('upcl:v5:catA:last_warm', now()->toIso8601String(), $ttl);

        $this->info('Ultra Prime cache warm complete.');

        return self::SUCCESS;
    }
}