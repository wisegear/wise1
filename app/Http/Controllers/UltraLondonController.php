<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UltraLondonController extends Controller
{
    // Ultra Prime Central London – Home
    public function home()
    {
        // Ultra Prime districts from lookup table
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Ultra Prime')
            ->pluck('postcode')
            ->unique()
            ->values();

        // Notes per postcode (Ultra Prime)
        $notesByPostcode = DB::table('prime_postcodes')
            ->where('category', 'Ultra Prime')
            ->pluck('notes', 'postcode');

        // Include an aggregate "ALL" bucket (treat all UPCL postcodes as one area)
        $allDistricts = collect(['ALL'])->merge($districts);

        $charts = [];
        $ttl = now()->addDays(45);

        foreach ($allDistricts as $district) {
            // Separate cache namespaces for ALL vs per-district
            $keyBase = $district === 'ALL' ? 'upcl:v5:catA:ALL:' : 'upcl:v5:catA:' . $district . ':';

            if ($district === 'ALL') {
                // ===== Aggregate across ALL Ultra Prime outward codes (prefix join) =====
                // Base: land_registry joined to DISTINCT set of UPCL postcodes by prefix
                $baseAll = DB::table('land_registry as lr')
                    ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Ultra Prime') as pp"),
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

                // 90th percentile (decile threshold) per year
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

                // Top 5% average per year
                $top5 = Cache::remember($keyBase . 'top5Avg', $ttl, function () use ($baseAll) {
                    $ranked = (clone $baseAll)
                        ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');

                    return DB::query()
                        ->fromSub($ranked, 'ranked')
                        ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
                        ->whereRaw('rn <= CEIL(cnt * 0.05)')
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Top sale per year
                $topSalePerYear = Cache::remember($keyBase . 'topSalePerYear', $ttl, function () use ($baseAll) {
                    return (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
                        ->groupBy('lr.YearDate')
                        ->orderBy('lr.YearDate')
                        ->get();
                });

                // Top 3 sales per year (for tooltip)
                $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($baseAll) {
                    $ranked = (clone $baseAll)
                        ->selectRaw('lr.YearDate as year, lr.Date as Date, lr.Postcode as Postcode, lr.Price as Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');

                    return DB::query()
                        ->fromSub($ranked, 'r')
                        ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                        ->where('rn', '<=', 3)
                        ->orderBy('year')
                        ->orderBy('rn')
                        ->get();
                });

            } else {
                // ===== Per-district (existing logic) =====
                // Average price by year (YearDate)
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

                // Property types by year (for stacked bar)
                $propertyTypes = Cache::remember($keyBase . 'propertyTypes', $ttl, function () use ($district) {
                    return DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate', 'type')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Prime indicator – 90th percentile (approx via top decile threshold)
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

                // Prime indicator – Top 5% average (uses window functions)
                $top5 = Cache::remember($keyBase . 'top5Avg', $ttl, function () use ($district) {
                    $ranked = DB::table('land_registry')
                        ->selectRaw('`YearDate`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0);

                    return DB::query()
                        ->fromSub($ranked, 'ranked')
                        ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as top5_avg')
                        ->whereRaw('rn <= CEIL(cnt * 0.05)')
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Spike detector – Top sale per year (scatter ready)
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

                // Spike detector – Top 3 sales per year (for tooltip/context tables)
                $ranked = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0);

                $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($ranked) {
                    return DB::query()
                        ->fromSub($ranked, 'r')
                        ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                        ->where('rn', '<=', 3)
                        ->orderBy('year')
                        ->orderBy('rn')
                        ->get();
                });
            }

            $charts[$district] = [
                'avgPrice' => $avgPrice,
                'sales' => $sales,
                'propertyTypes' => $propertyTypes,
                'p90' => $p90,
                'top5' => $top5,
                'topSalePerYear' => $topSalePerYear,
                'top3PerYear' => $top3PerYear,
            ];
        }

        return view('ultra.home', [
            'pageTitle' => 'Ultra Prime Central London',
            'districts' => $allDistricts,
            'charts' => $charts,
            'notes' => $notesByPostcode,
        ]);
    }
}
