<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LandRegistry;
use Illuminate\Support\Facades\Cache;

class PropertyController extends Controller
{

    public function home(Request $request)
    {
        return view('property.home');
    }

    public function search(Request $request)
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
        return view('property.search', compact('postcode', 'results', 'records', 'salesByYear', 'avgPriceByYear', 'avgPricePrimeCentralByYear', 'primeCentralSalesByYear', 'avgPriceUltraPrimeByYear', 'ultraPrimeSalesByYear'))
            ->with(['sort' => $sort ?? 'Date', 'dir' => $dir ?? 'desc']);
    }


    public function show(Request $request)
    {
        // Full match on address parts
        $postcode = strtoupper(trim($request->input('postcode')));
        $paon     = strtoupper(trim($request->input('paon')));
        $street   = strtoupper(trim($request->input('street')));
        $saon     = $request->filled('saon') ? strtoupper(trim($request->input('saon'))) : null;

        $query = DB::table(DB::raw('land_registry USE INDEX (idx_full_property, idx_postcode_date)'))
            ->select('Date', 'Price', 'PropertyType', 'NewBuild', 'Duration', 'PAON', 'SAON', 'Street', 'Postcode', 'Locality', 'TownCity', 'District', 'County', 'PPDCategoryType')
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $query->where('SAON', $saon);
        } else {
            // treat empty string and NULL the same to maximize matches and use index
            $query->where(function ($q) {
                $q->whereNull('SAON')->orWhere('SAON', '');
            });
        }

        $records = $query->orderBy('Date', 'desc')->limit(100)->get();

        if ($records->isEmpty()) {
            abort(404, 'Property not found');
        }

        // Build address (PAON, SAON, Street, Locality, Postcode, TownCity, District, County)
        $first = $records->first();
        $district = $first->District;
        $addressParts = [];
        $addressParts[] = trim($first->PAON);
        if (!empty(trim($first->SAON))) {
            $addressParts[] = trim($first->SAON);
        }
        $addressParts[] = trim($first->Street);
        if (!empty(trim($first->Locality))) {
            $addressParts[] = trim($first->Locality);
        }
        $addressParts[] = trim($first->Postcode);
        if (!empty(trim($first->TownCity))) {
            $addressParts[] = trim($first->TownCity);
        }
        if (!empty(trim($first->District))) {
            $addressParts[] = trim($first->District);
        }
        if (!empty(trim($first->County))) {
            $addressParts[] = trim($first->County);
        }
        $address = implode(', ', $addressParts);

        // --- Locality visibility gate to avoid unnecessary locality queries ---
        $locality     = trim((string) $first->Locality);
        $town         = trim((string) $first->TownCity);
        $districtName = trim((string) $first->District);
        $countyName   = trim((string) $first->County);
        $norm = function ($v) {
            return strtolower(trim((string) $v));
        };
        $showLocalityCharts = ($locality !== '')
            && ($norm($locality) !== $norm($town))
            && ($norm($locality) !== $norm($districtName))
            && ($norm($locality) !== $norm($countyName));

        // Determine if town charts should be shown (town must be non-empty and distinct from District, County)
        $showTownCharts = ($town !== '')
            && ($norm($town) !== $norm($districtName))
            && ($norm($town) !== $norm($countyName));

        $priceHistoryQuery = DB::table(DB::raw('land_registry USE INDEX (idx_full_property)'))
            ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $priceHistoryQuery->where('SAON', $saon);
        } else {
            $priceHistoryQuery->where(function ($q) {
                $q->whereNull('SAON')->orWhere('SAON', '');
            });
        }

        $priceHistory = $priceHistoryQuery->groupBy('YearDate')->orderBy('YearDate', 'asc')->get();

        $postcodePriceHistory = DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
            ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
            ->where('Postcode', $postcode)
            ->groupBy('YearDate')
            ->orderBy('YearDate', 'asc')
            ->get();

        $postcodeSalesHistory = DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
            ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
            ->where('Postcode', $postcode)
            ->groupBy('YearDate')
            ->orderBy('YearDate', 'asc')
            ->get();

        $countyPriceHistory = Cache::remember(
            'county:priceHistory:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('County', $first->County)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $districtPriceHistory = Cache::remember(
            'district:priceHistory:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('District', $first->District)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $countySalesHistory = Cache::remember(
            'county:salesHistory:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('County', $first->County)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $districtSalesHistory = Cache::remember(
            'district:salesHistory:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('District', $first->District)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $propertyTypeMap = [
            'D' => 'Detached',
            'S' => 'Semi',
            'T' => 'Terraced',
            'F' => 'Flat',
            'O' => 'Other',
        ];

        $countyPropertyTypes = Cache::remember(
            'county:types:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first, $propertyTypeMap) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                    ->where('County', $first->County)
                    ->groupBy('PropertyType')
                    ->orderByDesc('property_count')
                    ->get()
                    ->map(function ($row) use ($propertyTypeMap) {
                        return [
                            'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                            'value' => $row->property_count
                        ];
                    });
            }
        );

        $districtPropertyTypes = Cache::remember(
            'district:types:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first, $propertyTypeMap) {
                return DB::table('land_registry')
                    ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                    ->where('District', $first->District)
                    ->groupBy('PropertyType')
                    ->orderByDesc('property_count')
                    ->get()
                    ->map(function ($row) use ($propertyTypeMap) {
                        return [
                            'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                            'value' => $row->property_count,
                        ];
                    });
            }
        );

        // --- Town/City datasets (mirrors district/locality structures) ---
        if ($showTownCharts) {
            $townPriceHistory = Cache::remember(
                'town:priceHistory:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townSalesHistory = Cache::remember(
                'town:salesHistory:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townPropertyTypes = Cache::remember(
                'town:types:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('PropertyType')
                        ->orderByDesc('property_count')
                        ->get()
                        ->map(function ($row) use ($propertyTypeMap) {
                            return [
                                'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                                'value' => $row->property_count,
                            ];
                        });
                }
            );
        } else {
            $townPriceHistory = collect();
            $townSalesHistory = collect();
            $townPropertyTypes = collect();
        }

        // Locality datasets (only compute when locality is meaningful & distinct)
        if ($showLocalityCharts) {
            $localityPriceHistory = Cache::remember(
                'locality:priceHistory:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('Locality', $first->Locality)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $localitySalesHistory = Cache::remember(
                'locality:salesHistory:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('Locality', $first->Locality)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $propertyTypeMap = [
                'D' => 'Detached',
                'S' => 'Semi',
                'T' => 'Terraced',
                'F' => 'Flat',
                'O' => 'Other',
            ];

            $localityPropertyTypes = Cache::remember(
                'locality:types:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                        ->where('Locality', $first->Locality)
                        ->groupBy('PropertyType')
                        ->orderByDesc('property_count')
                        ->get()
                        ->map(function ($row) use ($propertyTypeMap) {
                            return [
                                'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                                'value' => $row->property_count,
                            ];
                        });
                }
            );
        } else {
            // Keep view happy but avoid work
            $localityPriceHistory = collect();
            $localitySalesHistory = collect();
            $localityPropertyTypes = collect();
        }

        return view('property.show', [
            'results' => $records,
            'address' => $address,
            'priceHistory' => $priceHistory,
            'postcodePriceHistory' => $postcodePriceHistory,
            'postcodeSalesHistory' => $postcodeSalesHistory,
            'countyPriceHistory' => $countyPriceHistory,
            'countySalesHistory' => $countySalesHistory,
            'countyPropertyTypes' => $countyPropertyTypes,
            'districtPriceHistory' => $districtPriceHistory,
            'districtSalesHistory' => $districtSalesHistory,
            'districtPropertyTypes' => $districtPropertyTypes,
            'townPriceHistory' => $townPriceHistory,
            'townSalesHistory' => $townSalesHistory,
            'townPropertyTypes' => $townPropertyTypes,
            'localityPriceHistory' => $localityPriceHistory,
            'localitySalesHistory' => $localitySalesHistory,
            'localityPropertyTypes' => $localityPropertyTypes,
        ]);


    }


}
