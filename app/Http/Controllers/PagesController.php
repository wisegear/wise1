<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LandRegistry;
use Illuminate\Support\Facades\Cache;

class PagesController extends Controller
{
    public function home(Request $request)
    {
        $postcode = strtoupper(trim((string) $request->query('postcode', '')));

        $results = null;

        if ($postcode !== '') {
            // Pragmatic UK postcode validation (not 100% exhaustive, but solid)
            // Accepts forms with and without the space: e.g. "WR53EU" or "WR5 3EU"
            $request->validate([
                'postcode' => [
                    'required',
                    'regex:/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/i'
                ],
            ]);

            $sortableColumns = [
                'TransactionID',
                'Price',
                'Date',
                'PropertyType',
                'NewBuild',
                'Duration',
                'PAON',
                'SAON',
                'Street',
                'Locality',
                'TownCity',
                'District',
                'County',
            ];

            $sort = $request->query('sort', 'Date');
            $dir = strtolower($request->query('dir', 'desc'));

            if (!in_array($sort, $sortableColumns)) {
                $sort = 'Date';
            }

            if (!in_array($dir, ['asc', 'desc'])) {
                $dir = 'desc';
            }

            $results = LandRegistry::query()
                ->select([
                    'TransactionID',
                    'Price',
                    'Date',
                    'PropertyType',
                    'NewBuild',
                    'Duration',
                    'PAON',
                    'SAON',
                    'Street',
                    'Locality',
                    'TownCity',
                    'District',
                    'County',
                ])
                ->where('Postcode', $postcode)
                ->orderBy($sort, $dir)
                ->simplePaginate(50)
                ->appends(['postcode' => $postcode, 'sort' => $sort, 'dir' => $dir]); // keep query on pagination links
        }

        // Total record count (cached to avoid heavy COUNT(*) on every request)
        $records = Cache::remember('land_registry_total_count', 3600, function () {
            return LandRegistry::count();
        });

        $salesByYear = Cache::remember('land_registry_sales_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        $avgPriceByYear = Cache::remember('land_registry_avg_price_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        $avgPricePrimeCentralByYear = Cache::remember('land_registry_avg_price_prime_central_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM prime_postcodes
                        WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode), '%')
                        AND category = 'Prime Central'
                    )
                ")
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        $primeCentralSalesByYear = Cache::remember('prime_central_sales_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, COUNT(*) as total_sales')
                ->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM prime_postcodes
                        WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode), '%')
                        AND category = 'Prime Central'
                    )
                ")
                ->groupBy('year')
                ->orderBy('year', 'asc')
                ->get();
        });

        $avgPriceUltraPrimeByYear = Cache::remember('land_registry_avg_price_ultra_prime_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM prime_postcodes
                        WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode), '%')
                        AND category = 'Ultra Prime'
                    )
                ")
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        $ultraPrimeSalesByYear = Cache::remember('ultra_prime_sales_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, COUNT(*) as total_sales')
                ->whereRaw("
                    EXISTS (
                        SELECT 1
                        FROM prime_postcodes
                        WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode), '%')
                        AND category = 'Ultra Prime'
                    )
                ")
                ->groupBy('year')
                ->orderBy('year', 'asc')
                ->get();
        });

        return view('pages.home', compact('postcode', 'results', 'records', 'salesByYear', 'avgPriceByYear', 'avgPricePrimeCentralByYear', 'primeCentralSalesByYear', 'avgPriceUltraPrimeByYear', 'ultraPrimeSalesByYear'))
            ->with(['sort' => $sort ?? 'Date', 'dir' => $dir ?? 'desc']);
    }
}
