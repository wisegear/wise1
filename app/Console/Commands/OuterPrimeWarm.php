<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OuterPrimeWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:outer-prime-warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm caches for Outer Prime London charts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Warming Outer Prime London caches...');

        $ttl = 60 * 60 * 24 * 45; // 45 days

        // Fetch Outer Prime districts
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Outer Prime London')
            ->orderBy('postcode')
            ->pluck('postcode')
            ->unique()
            ->values();

        if ($districts->isEmpty()) {
            $this->warn('No Outer Prime districts found.');
            return;
        }

        foreach ($districts as $district) {
            $this->info("Warming district: {$district}");

            $keyBase = 'outerprime:v1:catA:' . $district . ':';

            // Average price
            $avgPrice = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

            // Sales
            $sales = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, COUNT(*) as sales')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'sales', $sales, $ttl);

            // Property types
            $propertyTypes = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->groupBy('YearDate', 'type')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);

            // 90th percentile
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

            // Top 5% avg
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

            // Top sale
            $topSale = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0)
                ->groupBy('YearDate')
                ->orderBy('YearDate')
                ->get();
            Cache::put($keyBase . 'topSalePerYear', $topSale, $ttl);

            // Top 3 per year
            $rankedTop3 = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);
            $top3 = DB::query()
                ->fromSub($rankedTop3, 'r')
                ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                ->where('rn', '<=', 3)
                ->orderBy('year')
                ->orderBy('rn')
                ->get();
            Cache::put($keyBase . 'top3PerYear', $top3, $ttl);
        }

        Cache::put('outerprime:v1:catA:last_warm', now()->toDateTimeString(), $ttl);
        $this->info('Outer Prime London warming complete.');
    }
}
