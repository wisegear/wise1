<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PrimeLondonController extends Controller
{
    // Prime Central London – Home
    public function home()
    {
        // Prime districts from lookup table
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Prime Central')
            ->pluck('postcode')
            ->unique()
            ->values();

        // Notes per postcode (for display in the blade)
        $notesByPostcode = DB::table('prime_postcodes')
            ->where('category', 'Prime Central')
            ->pluck('notes', 'postcode');

        // Include an aggregate "ALL" bucket to show charts across all PCL postcodes
        $allDistricts = collect(['ALL'])->merge($districts);

        $charts = [];
        $ttl = now()->addDays(45);

        foreach ($allDistricts as $district) {
            // Build a cache key base (ALL uses a dedicated namespace)
            $keyBase = $district === 'ALL' ? 'pcl:v3:catA:ALL:' : 'pcl:v3:catA:' . $district . ':';

            if ($district === 'ALL') {
                // Treat ALL PCL as one area by joining to prime_postcodes (avoids IN/prefix mismatches)
                $applyAllPcl = function ($q) {
                    // Join to DISTINCT list of PCL outward codes and match by prefix
                    $q->join(DB::raw("(
                        SELECT DISTINCT postcode
                        FROM prime_postcodes
                        WHERE category = 'Prime Central'
                    ) as pp"),
                    function ($join) {
                        $join->on(DB::raw("SUBSTRING_INDEX(land_registry.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"));
                    });
                };

                // ***** Aggregate across ALL Prime Central districts *****
                // Average price by year (YearDate)
                $avgPrice = Cache::remember($keyBase . 'avgPrice', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
                        ->where('PPDCategoryType', 'A')
                        ->when(true, $applyAllPcl)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Sales count by year
                $sales = Cache::remember($keyBase . 'sales', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, COUNT(*) as sales')
                        ->where('PPDCategoryType', 'A')
                        ->when(true, $applyAllPcl)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Property types by year (for stacked bar)
                $propertyTypes = Cache::remember($keyBase . 'propertyTypes', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                        ->where('PPDCategoryType', 'A')
                        ->when(true, $applyAllPcl)
                        ->groupBy('YearDate', 'type')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Average price by property type by year (D/S/T/F)
                $avgPriceByType = Cache::remember($keyBase . 'avgPriceByType', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw("`YearDate` as year, LEFT(`PropertyType`, 1) as type, ROUND(AVG(`Price`)) as avg_price")
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('PropertyType')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->whereRaw("LEFT(`PropertyType`, 1) IN ('D','S','T','F')")
                        ->when(true, $applyAllPcl)
                        ->groupBy('YearDate', 'type')
                        ->orderBy('YearDate')
                        ->get();
                });

                // New build vs existing (% of sales) per year
                $newBuildPct = Cache::remember($keyBase . 'newBuildPct', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw(
                            "`YearDate` as year, " .
                            "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'Y' THEN 1 ELSE 0 END) / COUNT(*), 1) as new_pct, " .
                            "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'N' THEN 1 ELSE 0 END) / COUNT(*), 1) as existing_pct"
                        )
                        ->where('PPDCategoryType', 'A')
                        ->when(true, $applyAllPcl)
                        ->whereNotNull('NewBuild')
                        ->whereIn('NewBuild', ['Y', 'N'])
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Freehold vs Leasehold (% of sales) per year (Duration: F/L)
                $tenurePct = Cache::remember($keyBase . 'tenurePct', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw(
                            "`YearDate` as year, " .
                            "ROUND(100 * SUM(CASE WHEN `Duration` = 'F' THEN 1 ELSE 0 END) / COUNT(*), 1) as free_pct, " .
                            "ROUND(100 * SUM(CASE WHEN `Duration` = 'L' THEN 1 ELSE 0 END) / COUNT(*), 1) as lease_pct"
                        )
                        ->where('PPDCategoryType', 'A')
                        ->when(true, $applyAllPcl)
                        ->whereNotNull('Duration')
                        ->whereIn('Duration', ['F','L'])
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // 90th percentile (decile threshold) per year via window function
                $p90 = Cache::remember($keyBase . 'p90', $ttl, function () use ($applyAllPcl) {
                    $deciles = DB::table('land_registry')
                        ->selectRaw('`YearDate`, `Price`, NTILE(10) OVER (PARTITION BY `YearDate` ORDER BY `Price`) as decile')
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->when(true, $applyAllPcl);

                    return DB::query()
                        ->fromSub($deciles, 't')
                        ->selectRaw('`YearDate` as year, MIN(`Price`) as p90')
                        ->where('decile', 10)
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Top 5% average per year via window ranking
                $top5 = Cache::remember($keyBase . 'top5', $ttl, function () use ($applyAllPcl) {
                    $ranked = DB::table('land_registry')
                        ->selectRaw('`YearDate`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->when(true, $applyAllPcl);

                    return DB::query()
                        ->fromSub($ranked, 'ranked')
                        ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as top5_avg')
                        ->whereRaw('rn <= CEIL(cnt * 0.05)')
                        ->groupBy('year')
                        ->orderBy('year')
                        ->get();
                });

                // Top sale per year (for scatter)
                $topSalePerYear = Cache::remember($keyBase . 'topSalePerYear', $ttl, function () use ($applyAllPcl) {
                    return DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->when(true, $applyAllPcl)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Top 3 sales per year (for tooltip)
                $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($applyAllPcl) {
                    $ranked = DB::table('land_registry')
                        ->selectRaw('`land_registry`.`YearDate` as year, `land_registry`.`Date` as Date, `land_registry`.`Postcode` as Postcode, `land_registry`.`Price` as Price, ROW_NUMBER() OVER (PARTITION BY `land_registry`.`YearDate` ORDER BY `land_registry`.`Price` DESC) as rn')
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->when(true, $applyAllPcl);

                    return DB::query()
                        ->fromSub($ranked, 'r')
                        ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                        ->where('rn', '<=', 3)
                        ->orderBy('year')
                        ->orderBy('rn')
                        ->get();
                });

            } else {
                // ***** Per-district (existing logic) *****
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

                // Average price by property type by year (D/S/T/F)
                $avgPriceByType = Cache::remember($keyBase . 'avgPriceByType', $ttl, function () use ($district) {
                    return DB::table('land_registry')
                        ->selectRaw("`YearDate` as year, LEFT(`PropertyType`, 1) as type, ROUND(AVG(`Price`)) as avg_price")
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('PropertyType')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->whereRaw("LEFT(`PropertyType`, 1) IN ('D','S','T','F')")
                        ->groupBy('YearDate', 'type')
                        ->orderBy('YearDate')
                        ->get();
                });

                // New build vs existing (% of sales) per year
                $newBuildPct = Cache::remember($keyBase . 'newBuildPct', $ttl, function () use ($district) {
                    return DB::table('land_registry')
                        ->selectRaw(
                            "`YearDate` as year, " .
                            "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'Y' THEN 1 ELSE 0 END) / COUNT(*), 1) as new_pct, " .
                            "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'N' THEN 1 ELSE 0 END) / COUNT(*), 1) as existing_pct"
                        )
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('NewBuild')
                        ->whereIn('NewBuild', ['Y', 'N'])
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate')
                        ->get();
                });

                // Freehold vs Leasehold (% of sales) per year (Duration: F/L)
                $tenurePct = Cache::remember($keyBase . 'tenurePct', $ttl, function () use ($district) {
                    return DB::table('land_registry')
                        ->selectRaw(
                            "`YearDate` as year, " .
                            "ROUND(100 * SUM(CASE WHEN `Duration` = 'F' THEN 1 ELSE 0 END) / COUNT(*), 1) as free_pct, " .
                            "ROUND(100 * SUM(CASE WHEN `Duration` = 'L' THEN 1 ELSE 0 END) / COUNT(*), 1) as lease_pct"
                        )
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Duration')
                        ->whereIn('Duration', ['F','L'])
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0)
                        ->groupBy('YearDate')
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
                $top5 = Cache::remember($keyBase . 'top5', $ttl, function () use ($district) {
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
                $top3PerYear = Cache::remember($keyBase . 'top3PerYear', $ttl, function () use ($district) {
                    $ranked = DB::table('land_registry')
                        ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                        ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                        ->where('PPDCategoryType', 'A')
                        ->whereNotNull('Price')
                        ->where('Price', '>', 0);

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
                'avgPrice'       => $avgPrice,
                'sales'          => $sales,
                'propertyTypes'  => $propertyTypes,
                'avgPriceByType' => $avgPriceByType,
                'newBuildPct'    => $newBuildPct,
                'tenurePct'      => $tenurePct,
                'p90'            => $p90,
                'top5'           => $top5,
                'topSalePerYear' => $topSalePerYear,
                'top3PerYear'    => $top3PerYear,
            ];
        }

        return view('prime.home', [
            'pageTitle' => 'Prime Central London',
            'districts' => $allDistricts,
            'charts' => $charts,
            'notes' => $notesByPostcode,
        ]);
    }
}
