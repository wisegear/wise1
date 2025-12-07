<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LandRegistry;
use Illuminate\Support\Facades\Cache;
use App\Services\EpcMatcher;

class PropertyController extends Controller
{
    private const CACHE_TTL = 60 * 60 * 24 * 45; // 45 days

    public function home(Request $request)
    {

        // =========================================================
        // 2) CACHED AGGREGATES FOR HOMEPAGE CHARTS
        //    These are cached for 1 day (86400s). When new monthly
        //    data is imported you can clear cache to refresh.
        // =========================================================

        // Yearly transaction counts (all categories)
        $salesByYear = Cache::remember('land_registry_sales_by_year:catA:v2', self::CACHE_TTL, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                ->where('PPDCategoryType', 'A')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        // Yearly average price (all categories)
        $avgPriceByYear = Cache::remember('land_registry_avg_price_by_year:catA:v2', self::CACHE_TTL, function () {
            return LandRegistry::selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->where('PPDCategoryType', 'A')
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        // Monthly sales — last 24 months (England & Wales, Cat A)
        [$sales24Labels, $sales24Data] = Cache::remember(
            'dashboard:sales_last_24m:EW:catA:v2',
            self::CACHE_TTL,
            function () {
                // Seed a wider window so we can trim to the true last available month
                $seedMonths = 36;
                $seedStart  = now()->startOfMonth()->subMonths($seedMonths - 1);
                $seedEnd    = now()->startOfMonth();

                $raw = DB::table('land_registry')
                    ->selectRaw("DATE_FORMAT(`Date`, '%Y-%m-01') as month_start, COUNT(*) as sales")
                    ->where('PPDCategoryType', 'A')
                    ->whereDate('Date', '>=', $seedStart)
                    ->groupBy('month_start')
                    ->orderBy('month_start')
                    ->pluck('sales', 'month_start')
                    ->toArray();

                // Determine last month with data
                $keys = array_keys($raw);
                if (!empty($keys)) {
                    sort($keys); // ascending
                    $lastDataKey = end($keys); // e.g., '2025-08-01'
                    $seriesEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $lastDataKey)->startOfMonth();
                } else {
                    // If nothing in window, use end of previous month
                    $seriesEnd = $seedEnd->copy()->subMonth();
                }

                // Build exactly 24 months ending at last available month
                $start = $seriesEnd->copy()->subMonths(23)->startOfMonth();

                $labels = [];
                $data   = [];
                $cursor = $start->copy();
                while ($cursor->lte($seriesEnd)) {
                    $key = $cursor->format('Y-m-01');
                    $labels[] = $cursor->format('M Y');  // will be formatted to MM/YY in the tick callback
                    $data[]   = (int)($raw[$key] ?? 0);
                    $cursor->addMonth();
                }

                return [$labels, $data];
            }
        );


        // ========= ENGLAND & WALES (Cat A): P90 and Top 5% =========
        $ewP90 = Cache::remember('ew:p90:catA:v1', self::CACHE_TTL, function () {
            $sub = DB::table('land_registry')
                ->selectRaw("`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd")
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);

            return DB::query()
                ->fromSub($sub, 't')
                ->selectRaw('year, MIN(Price) as p90_price')
                ->where('cd', '>=', 0.9)
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        $ewTop5 = Cache::remember('ew:top5avg:catA:v1', self::CACHE_TTL, function () {
            $ranked = DB::table('land_registry')
                ->selectRaw("`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt")
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0);

            return DB::query()
                ->fromSub($ranked, 'r')
                ->selectRaw('year, ROUND(AVG(Price)) as top5_avg')
                ->whereColumn('rn', '<=', DB::raw('CEIL(0.05 * cnt)'))
                ->groupBy('year')
                ->orderBy('year')
                ->get();
        });

        // England & Wales (Cat A): Top sale per year (for scatter marker)
        $ewTopSalePerYear = Cache::remember('ew:topSalePerYear:catA:v1', self::CACHE_TTL, function () {
            return LandRegistry::selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')
                ->where('Price', '>', 0)
                ->groupBy('YearDate')
                ->orderBy('year')
                ->get();
        });

        // England & Wales (Cat A): Top 3 sales per year (for tooltip detail)
        $ewTop3PerYear = Cache::remember('ew:top3PerYear:catA:v1', self::CACHE_TTL, function () {
            $rankedTop3 = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
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


        // =========================================================
        // 3) RENDER: pass both search results (if any) and all
        //    cached aggregates for charts to the Blade view
        // =========================================================
        return view('property.home', compact(
            'salesByYear', 'avgPriceByYear', 'ewP90', 'ewTop5', 'ewTopSalePerYear', 'ewTop3PerYear',
            'sales24Labels', 'sales24Data'
        ));
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
                ->whereIn('PPDCategoryType', ['A', 'B'])
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
        $records = Cache::remember('land_registry_total_count', self::CACHE_TTL, function () {
            return LandRegistry::count();
        });



        // =========================================================
        // 3) RENDER: pass both search results (if any) and all
        //    cached aggregates for charts to the Blade view
        // =========================================================
        return view('property.search', compact('postcode', 'results', 'records'))
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
            ->whereIn('PPDCategoryType', ['A', 'B']);

        // Treat empty Street as NULL-or-empty to maximise matches
        if (!empty($street)) {
            $query->where('Street', $street);
        } else {
            $query->where(function ($q) {
                $q->whereNull('Street')->orWhere('Street', '');
            });
        }

        if (!empty($saon)) {
            $query->where('SAON', $saon);
        } else {
            // treat empty string and NULL the same to maximize matches and use index
            $query->where(function ($q) {
                $q->whereNull('SAON')->orWhere('SAON', '');
            });
        }

        // Build a base cache key for this property
        $saonKey = $saon !== null && $saon !== '' ? $saon : 'NOSAON';
        $propertyCacheKeyBase = sprintf('property:%s:%s:%s:%s', $postcode, $paon, $street, $saonKey);

        $records = Cache::remember(
            $propertyCacheKeyBase . ':records:v2:catAB',
            self::CACHE_TTL,
            function () use ($query) {
                return $query->orderBy('Date', 'desc')->limit(100)->get();
            }
        );

        if ($records->isEmpty()) {
            abort(404, 'Property not found');
        }

        // Build address (PAON, SAON, Street, Locality, Postcode, TownCity, District, County)
        $first = $records->first();

        // Determine property type from the most recent sale
        $propertyTypeCode = $first->PropertyType ?? null; // 'D','S','T','F','O'

        $propertyTypeMap = [
            'D' => 'Detached',
            'S' => 'Semi-Detached',
            'T' => 'Terraced',
            'F' => 'Flat',
            'O' => 'Other',
        ];

        $propertyTypeLabel = $propertyTypeMap[$propertyTypeCode] ?? 'property';

        // Normalised keys for cache lookups (trim to avoid trailing/leading space mismatches)
        $countyKey   = trim((string) $first->County);
        $districtKey = trim((string) $first->District);
        $townKey     = trim((string) $first->TownCity);
        $localityKey = trim((string) $first->Locality);
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

        // EPC matching (postcode + fuzzy)
        $matcher = new EpcMatcher();
        $epcMatches = $matcher->findForProperty(
            $postcode,
            $paon,
            $saon,
            $street,
            now(), // reference date (could be first/last sale date if preferred)
            5
        );

        // --- Locality visibility gate to avoid unnecessary locality queries ---
        $locality     = trim((string) $first->Locality);
        $town         = trim((string) $first->TownCity);
        $districtName = trim((string) $first->District);
        $countyName   = trim((string) $first->County);
        $norm = function ($v) {
            return strtolower(trim((string) $v));
        };
        $isSameCountyDistrict = ($norm($districtName) === $norm($countyName));
        $showLocalityCharts = ($locality !== '')
            && ($norm($locality) !== $norm($town))
            && ($norm($locality) !== $norm($districtName))
            && ($norm($locality) !== $norm($countyName));

        // Fallback: Always define district datasets if county==district, even if hidden
        if ($isSameCountyDistrict) {
            $districtPriceHistory = collect();
            $districtSalesHistory = collect();
            $districtPropertyTypes = collect();
        }

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
        $priceHistoryQuery->where('PPDCategoryType', 'A');

        $priceHistory = Cache::remember(
            $propertyCacheKeyBase . ':priceHistory:v2:catA',
            self::CACHE_TTL,
            function () use ($priceHistoryQuery) {
                return $priceHistoryQuery->groupBy('YearDate')->orderBy('YearDate', 'asc')->get();
            }
        );

        $postcodePriceHistory = Cache::remember(
            'postcode:' . $postcode . ':type:' . $propertyTypeCode . ':priceHistory:v3:catA',
            self::CACHE_TTL,
            function () use ($postcode, $propertyTypeCode) {
                return DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('Postcode', $postcode)
                    ->where('PropertyType', $propertyTypeCode)
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $postcodeSalesHistory = Cache::remember(
            'postcode:' . $postcode . ':type:' . $propertyTypeCode . ':salesHistory:v3:catA',
            self::CACHE_TTL,
            function () use ($postcode, $propertyTypeCode) {
                return DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('Postcode', $postcode)
                    ->where('PropertyType', $propertyTypeCode)
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $countyPriceHistory = Cache::remember(
            'county:priceHistory:v3:catA:' . $countyKey . ':type:' . $propertyTypeCode,
            self::CACHE_TTL,
            function () use ($countyKey, $propertyTypeCode) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('County', $countyKey)
                    ->where('PropertyType', $propertyTypeCode)
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        // Always assign districtPriceHistory
        if ($isSameCountyDistrict) {
            $districtPriceHistory = $countyPriceHistory;
        } else {
            $districtPriceHistory = Cache::remember(
                'district:priceHistory:v3:catA:' . $districtKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($districtKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('District', $districtKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );
        }

        $countySalesHistory = Cache::remember(
            'county:salesHistory:v3:catA:' . $countyKey . ':type:' . $propertyTypeCode,
            self::CACHE_TTL,
            function () use ($countyKey, $propertyTypeCode) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('County', $countyKey)
                    ->where('PropertyType', $propertyTypeCode)
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        // Always assign districtSalesHistory
        if ($isSameCountyDistrict) {
            $districtSalesHistory = $countySalesHistory;
        } else {
            $districtSalesHistory = Cache::remember(
                'district:salesHistory:v3:catA:' . $districtKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($districtKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('District', $districtKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );
        }

        $countyPropertyTypes = Cache::remember(
            'county:types:v2:catA:' . $countyKey,
            self::CACHE_TTL,
            function () use ($countyKey, $propertyTypeMap) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->select('PropertyType', DB::raw("COUNT(*) as property_count"))
                    ->where('County', $countyKey)
                    ->where('PPDCategoryType', 'A')
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

        // Always assign districtPropertyTypes
        if ($isSameCountyDistrict) {
            $districtPropertyTypes = $countyPropertyTypes;
        } else {
            $districtPropertyTypes = Cache::remember(
                'district:types:v2:catA:' . $districtKey,
                self::CACHE_TTL,
                function () use ($districtKey, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(*) as property_count"))
                        ->where('District', $districtKey)
                        ->where('PPDCategoryType', 'A')
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
        }

        // --- Town/City datasets (mirrors district/locality structures) ---
        if ($showTownCharts) {
            $townPriceHistory = Cache::remember(
                'town:priceHistory:v3:catA:' . $townKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($townKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('TownCity', $townKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townSalesHistory = Cache::remember(
                'town:salesHistory:v3:catA:' . $townKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($townKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('TownCity', $townKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townPropertyTypes = Cache::remember(
                'town:types:v2:catA:' . $townKey,
                self::CACHE_TTL,
                function () use ($townKey, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(*) as property_count"))
                        ->where('TownCity', $townKey)
                        ->where('PPDCategoryType', 'A')
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
            // Always define town datasets, even if not shown
            $townPriceHistory = collect();
            $townSalesHistory = collect();
            $townPropertyTypes = collect();
        }

        // Locality datasets (only compute when locality is meaningful & distinct)
        if ($showLocalityCharts) {
            $localityPriceHistory = Cache::remember(
                'locality:priceHistory:v3:catA:' . $localityKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($localityKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('Locality', $localityKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $localitySalesHistory = Cache::remember(
                'locality:salesHistory:v3:catA:' . $localityKey . ':type:' . $propertyTypeCode,
                self::CACHE_TTL,
                function () use ($localityKey, $propertyTypeCode) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('Locality', $localityKey)
                        ->where('PropertyType', $propertyTypeCode)
                        ->where('PPDCategoryType', 'A')
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $localityPropertyTypes = Cache::remember(
                'locality:types:v2:catA:' . $localityKey,
                self::CACHE_TTL,
                function () use ($localityKey, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(*) as property_count"))
                        ->where('Locality', $localityKey)
                        ->where('PPDCategoryType', 'A')
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
            // Always define locality datasets, even if not shown
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
            'epcMatches' => $epcMatches,
            'propertyTypeCode' => $propertyTypeCode,
            'propertyTypeLabel' => $propertyTypeLabel,
        ]);


    }


}
