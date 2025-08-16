<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LandRegistry;
use Illuminate\Support\Facades\Cache;

/**
 * PagesController
 *
 * Responsible for the public pages (home/about). The home action can:
 *  - Perform a postcode search with sorting + pagination
 *  - Compute/cached site-wide aggregates for charts (sales counts, avg prices, prime/ultra prime slices)
 */
class PagesController extends Controller
{
    /**
     * Home page
     *
     * Steps:
     *  1) Read & normalise the incoming postcode (if provided)
     *  2) If a postcode is present, validate it, apply sorting, run the search, paginate results
     *  3) Always compute (via cache) the yearly aggregates used by charts
     *  4) Return the view with all variables
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function home(Request $request)
    {
        // =========================================================
        // 1) INPUT: read and normalise the postcode from the querystring
        //    e.g. "WR53EU" or "WR5 3EU" → store as upper-case string
        // =========================================================
        $postcode = strtoupper(trim((string) $request->query('postcode', '')));

        $results = null;

        if ($postcode !== '') {
            // -----------------------------------------------------
            // Validate basic UK postcode format (pragmatic regex)
            // This accepts with/without space; detailed edge-cases
            // are out of scope for speed/robustness here.
            // -----------------------------------------------------
            $request->validate([
                'postcode' => [
                    'required',
                    'regex:/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/i'
                ],
            ]);

            // -----------------------------------------------------
            // Sorting: whitelist of sortable columns exposed to UI
            // -----------------------------------------------------
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
                'PPDCategoryType',
            ];

            // Read desired sort field & direction; fall back safely
            $sort = $request->query('sort', 'Date');
            $dir = strtolower($request->query('dir', 'desc'));

            if (!in_array($sort, $sortableColumns)) {
                $sort = 'Date';
            }

            if (!in_array($dir, ['asc', 'desc'])) {
                $dir = 'desc';
            }

            // -----------------------------------------------------
            // QUERY: Postcode search
            //  - Only runs if a postcode is provided and valid
            //  - Returns a paginated, sortable result-set
            // -----------------------------------------------------
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
                    'Postcode',
                    'PPDCategoryType',
                ])
                ->where('Postcode', $postcode)
                ->orderBy($sort, $dir)
                ->Paginate(15)
                ->appends(['postcode' => $postcode, 'sort' => $sort, 'dir' => $dir]); // keep query on pagination links
        }

        // =========================================================
        // 2) CACHED AGGREGATES FOR HOMEPAGE CHARTS
        //    These are cached for 1 day (86400s). When new monthly
        //    data is imported you can clear cache to refresh.
        // =========================================================

        // Total number of rows in land_registry
        $records = Cache::remember('land_registry_total_count', 86400, function () {
            return LandRegistry::count();
        });

        // Yearly transaction counts (all categories)
        $salesByYear = Cache::remember('land_registry_sales_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        // Yearly average price (all categories)
        $avgPriceByYear = Cache::remember('land_registry_avg_price_by_year', 86400, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        // Prime Central London: yearly average price
        //  - Uses prime_postcodes table, matching the outward code prefix
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

        // Prime Central London: yearly sales counts
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

        // Ultra Prime: yearly average price
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

        // Ultra Prime: yearly sales counts
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

        // =========================================================
        // 3) RENDER: pass both search results (if any) and all
        //    cached aggregates for charts to the Blade view
        // =========================================================
        return view('pages.home', compact('postcode', 'results', 'records', 'salesByYear', 'avgPriceByYear', 'avgPricePrimeCentralByYear', 'primeCentralSalesByYear', 'avgPriceUltraPrimeByYear', 'ultraPrimeSalesByYear'))
            ->with(['sort' => $sort ?? 'Date', 'dir' => $dir ?? 'desc']);
    }

    /**
     * Static About page.
     */
    public function about()
    {
        return view('pages.about');
    }

}
