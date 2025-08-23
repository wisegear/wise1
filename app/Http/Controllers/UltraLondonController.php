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

        $charts = [];
        $ttl = now()->addDays(45);

        foreach ($districts as $district) {
            $keyBase = 'upcl:v3:' . $district . ':';

            // Average price by year (YearDate)
            $avgPrice = Cache::remember($keyBase . 'avgPrice', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();
            });

            // Sales count by year
            $sales = Cache::remember($keyBase . 'sales', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, COUNT(*) as sales')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();
            });

            // Property types by year (for stacked bar)
            $propertyTypes = Cache::remember($keyBase . 'propertyTypes', $ttl, function () use ($district) {
                return DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
                    ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
                    ->groupBy('YearDate', 'type')
                    ->orderBy('YearDate')
                    ->get();
            });

            $charts[$district] = [
                'avgPrice' => $avgPrice,
                'sales' => $sales,
                'propertyTypes' => $propertyTypes,
            ];
        }

        return view('ultra.home', [
            'pageTitle' => 'Ultra Prime Central London',
            'districts' => $districts,
            'charts' => $charts,
            'notes' => $notesByPostcode,
        ]);
    }
}
