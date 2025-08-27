<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OuterPrimeLondonController extends Controller
{
    /**
     * Show the Outer Prime London dashboard.
     * Mirrors PrimeLondonController but uses category 'Outer Prime London'.
     */
    public function home(Request $request)
    {
        // Page title
        $pageTitle = 'Outer Prime Central London';

        // Fetch Outer Prime postcode districts
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Outer Prime London')
            ->orderBy('postcode')
            ->pluck('postcode')
            ->unique()
            ->values();

        // If no districts, render a minimal view
        if ($districts->isEmpty()) {
            return view('prime.home', [
                'pageTitle' => $pageTitle,
                'districts' => collect(),
                'charts' => [],
                'notes' => [],
            ]);
        }

        // TTL for cached datasets (45 days)
        $ttl = 60 * 60 * 24 * 45;

        // Build charts data per district
        $charts = [];

        foreach ($districts as $district) {
            // Key base (separate namespace from PCL/UPCL)
            $keyBase = 'outerprime:v1:catA:' . $district . ':';

            // Average price by year
            $avgPrice = Cache::remember($keyBase . 'avgPrice', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();
            });

            // Sales count by year
            $sales = Cache::remember($keyBase . 'sales', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, COUNT(*) as sales')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();
            });

            // Property types by year
            $propertyTypes = Cache::remember($keyBase . 'propertyTypes', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate', 'type')
                    ->orderBy('YearDate')
                    ->get();
            });

            // 90th percentile (threshold) per year via window function
            $p90 = Cache::remember($keyBase . 'p90', $ttl, function () use ($district) {
                $deciles = DB::table('land_registry')
                    ->selectRaw('`YearDate`, `Price`, NTILE(10) OVER (PARTITION BY `YearDate` ORDER BY `Price`) as decile')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0);

                return DB::query()
                    ->fromSub($deciles, 't')
                    ->selectRaw('`YearDate` as year, MIN(`Price`) as p90')
                    ->where('decile', 10)
                    ->groupBy('year')
                    ->orderBy('year')
                    ->get();
            });

            // Top 5% average per year via window ranking
            $top5 = Cache::remember($keyBase . 'top5', $ttl, function () use ($district) {
                $rankedTop5 = DB::table('land_registry')
                    ->selectRaw('`YearDate`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0);

                return DB::query()
                    ->fromSub($rankedTop5, 'ranked')
                    ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as top5_avg')
                    ->whereRaw('rn <= CEIL(cnt * 0.05)')
                    ->groupBy('year')
                    ->orderBy('year')
                    ->get();
            });

            // Top sale per year (for spike marker)
            $topSalePerYear = Cache::remember($keyBase . 'topSalePerYear', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();
            });

            // Top 3 sales per year (for tooltip/context)
            $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($district) {
                $rankedTop3 = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0);

                return DB::query()
                    ->fromSub($rankedTop3, 'r')
                    ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                    ->where('rn', '<=', 3)
                    ->orderBy('year')
                    ->orderBy('rn')
                    ->get();
            });

            $charts[$district] = [
                'avgPrice'       => $avgPrice,
                'sales'          => $sales,
                'propertyTypes'  => $propertyTypes,
                'p90'            => $p90,
                'top5'           => $top5,
                'topSalePerYear' => $topSalePerYear,
                'top3PerYear'    => $top3PerYear,
            ];
        }

        // Optional notes per district (placeholder for parity with Prime controller)
        $notes = [];

        // Reuse the same view as Prime controller for consistency
        return view('outerprime.home', compact('pageTitle', 'districts', 'charts', 'notes'));
    }
}
