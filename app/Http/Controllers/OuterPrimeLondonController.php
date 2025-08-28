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

        // Include an aggregate "ALL" bucket to show charts across all Outer Prime postcodes
        $allDistricts = collect(['ALL'])->merge($districts);

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

        foreach ($allDistricts as $district) {
            // Key base (namespace): keep per-district in v1 for compatibility; use v2 for ALL aggregate
            $keyBase = $district === 'ALL' ? 'outerprime:v2:catA:ALL:' : 'outerprime:v1:catA:' . $district . ':';

            if ($district === 'ALL') {
                // ===== Aggregate across ALL Outer Prime London outward codes (prefix join) =====
                $baseAll = DB::table('land_registry as lr')
                    ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Outer Prime London') as pp"),
                        DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
                    ->where('lr.PPDCategoryType', 'A');

                // Average price by year
                $avgPrice = Cache::remember($keyBase . 'avgPrice', $ttl, function () use ($baseAll) {
                    return (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
                        ->groupBy('lr.YearDate')
                        ->orderBy('lr.YearDate')
                        ->get();
                });

                // Sales count by year
                $sales = Cache::remember($keyBase . 'sales', $ttl, function () use ($baseAll) {
                    return (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
                        ->groupBy('lr.YearDate')
                        ->orderBy('lr.YearDate')
                        ->get();
                });

                // Property types by year
                $propertyTypes = Cache::remember($keyBase . 'propertyTypes', $ttl, function () use ($baseAll) {
                    return (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
                        ->groupBy('lr.YearDate', 'type')
                        ->orderBy('lr.YearDate')
                        ->get();
                });

                // 90th percentile (decile threshold) per year via window function
                $p90 = Cache::remember($keyBase . 'p90', $ttl, function () use ($baseAll) {
                    $deciles = (clone $baseAll)
                        ->selectRaw('lr.YearDate, lr.Price, NTILE(10) OVER (PARTITION BY lr.YearDate ORDER BY lr.Price) as decile');

                    return DB::query()
                        ->fromSub($deciles, 't')
                        ->selectRaw('YearDate as year, MIN(Price) as p90')
                        ->where('decile', 10)
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Top 5% average per year via window ranking
                $top5 = Cache::remember($keyBase . 'top5', $ttl, function () use ($baseAll) {
                    $rankedTop5 = (clone $baseAll)
                        ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');

                    return DB::query()
                        ->fromSub($rankedTop5, 'ranked')
                        ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
                        ->whereRaw('rn <= CEIL(cnt * 0.05)')
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Top sale per year (for spike marker)
                $topSalePerYear = Cache::remember($keyBase . 'topSalePerYear', $ttl, function () use ($baseAll) {
                    return (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
                        ->groupBy('lr.YearDate')
                        ->orderBy('lr.YearDate')
                        ->get();
                });

                // Top 3 sales per year (for tooltip/context)
                $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($baseAll) {
                    $rankedTop3 = (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, lr.Date as Date, lr.Postcode as Postcode, lr.Price as Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');

                    return DB::query()
                        ->fromSub($rankedTop3, 'r')
                        ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                        ->where('rn', '<=', 3)
                        ->orderBy('year')
                        ->orderBy('rn')
                        ->get();
                });
            } else {
                // ===== Per-district (existing logic) =====
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
            }

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

        // Notes per district from the prime_postcodes table
        $notes = DB::table('prime_postcodes')
            ->where('category', 'Outer Prime London')
            ->pluck('notes', 'postcode')
            ->toArray();

        // Reuse the same view as Prime controller for consistency
        return view('outerprime.home', [
            'pageTitle' => $pageTitle,
            'districts' => $allDistricts,
            'charts'    => $charts,
            'notes'     => $notes,
        ]);
    }
}
