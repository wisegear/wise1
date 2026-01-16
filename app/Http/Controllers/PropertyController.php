<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // Helper: normalise a postcode to the standard spaced form used by ONSPD `pcds`.
        // Input may be "WR53EU" or "WR5 3EU" → output "WR5 3EU".
        $toPcds = function (string $pc) {
            $pc = strtoupper(trim($pc));
            $pc = preg_replace('/\s+/', '', $pc);
            if ($pc === '' || strlen($pc) < 5) {
                return null;
            }
            // Insert a space before the last 3 characters
            return substr($pc, 0, -3) . ' ' . substr($pc, -3);
        };

        $coordsByPostcode = [];

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

            // -----------------------------------------------------
            // ONSPD: Bulk fetch coordinates for the current page
            // (prevents per-row ONSPD lookups in the Blade).
            // -----------------------------------------------------
            $pagePostcodes = $results->getCollection()
                ->pluck('Postcode')
                ->filter()
                ->map(fn ($pc) => $toPcds((string) $pc))
                ->filter()
                ->unique()
                ->values();

            if ($pagePostcodes->isNotEmpty()) {
                // If multiple ONSPD rows exist historically, take the latest by `dointr`.
                // We do this by ordering desc and keeping the first seen per `pcds`.
                $rows = DB::table('onspd')
                    ->select(['pcds', 'lat', 'long', 'dointr'])
                    ->whereIn('pcds', $pagePostcodes)
                    ->orderBy('pcds')
                    ->orderByDesc('dointr')
                    ->get();

                foreach ($rows as $row) {
                    $pcds = (string) $row->pcds;
                    if (!isset($coordsByPostcode[$pcds])) {
                        $coordsByPostcode[$pcds] = [
                            'lat' => $row->lat !== null ? (float) $row->lat : null,
                            'lng' => $row->long !== null ? (float) $row->long : null,
                        ];
                    }
                }
            }
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
        return view('property.search', compact('postcode', 'results', 'records', 'coordsByPostcode'))
            ->with(['sort' => $sort ?? 'Date', 'dir' => $dir ?? 'desc']);
    }

    public function heatmap()
    {
        $cacheKey = 'land_registry_heatmap:lsoa21:v2';

        if (!Cache::has($cacheKey)) {
            return response()->json([
                'status' => 'warming',
                'message' => 'Heatmap cache is warming. Run `php artisan property:heatmap-warm` to generate it.',
            ], 202);
        }

        $points = Cache::get($cacheKey, []);

        return response()->json($points);
    }

    public function points(Request $request)
    {
        $south = (float) $request->query('south');
        $west  = (float) $request->query('west');
        $north = (float) $request->query('north');
        $east  = (float) $request->query('east');
        $zoom  = (int) $request->query('zoom', 6);
        $limit = (int) $request->query('limit', 5000);

        $limit = max(1000, min($limit, 15000));

        if ($zoom < 12) {
            return response()->json([
                'status' => 'zoom',
                'message' => 'Zoom in to load property points.',
            ], 202);
        }

        $bboxKey = sprintf('%.3f:%.3f:%.3f:%.3f:z%d:l%d', $south, $west, $north, $east, $zoom, $limit);

        $payload = Cache::remember('land_registry_points:' . $bboxKey, now()->addMinutes(10), function () use ($south, $west, $north, $east, $limit) {
            $rows = DB::table('onspd as o')
                ->join('land_registry as lr', 'lr.Postcode', '=', 'o.pcds')
                ->whereIn('lr.PPDCategoryType', ['A', 'B'])
                ->whereNotNull('o.lat')
                ->whereNotNull('o.long')
                ->whereBetween('o.lat', [$south, $north])
                ->whereBetween('o.long', [$west, $east])
                ->select([
                    'o.lat',
                    'o.long',
                    'lr.Postcode',
                    'lr.PAON',
                    'lr.SAON',
                    'lr.Street',
                    'lr.Price',
                    'lr.Date',
                    'lr.PPDCategoryType',
                ])
                ->limit($limit + 1)
                ->get();

            $truncated = $rows->count() > $limit;
            if ($truncated) {
                $rows = $rows->take($limit);
            }

            $points = $rows->map(function ($row) {
                $postcode = (string) ($row->Postcode ?? '');
                $paon = (string) ($row->PAON ?? '');
                $street = (string) ($row->Street ?? '');
                $saon = (string) ($row->SAON ?? '');

                return [
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->long,
                    'price' => $row->Price !== null ? (int) $row->Price : null,
                    'date' => $row->Date ? (string) $row->Date : null,
                    'address' => trim($paon . ' ' . $street),
                    'postcode' => $postcode,
                    'category' => (string) ($row->PPDCategoryType ?? ''),
                    'url' => route('property.show', [
                        'postcode' => $postcode,
                        'paon' => $paon,
                        'street' => $street,
                        'saon' => $saon,
                    ], false),
                ];
            })->values();

            return [
                'points' => $points,
                'truncated' => $truncated,
            ];
        });

        return response()->json($payload);
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

        // -----------------------------------------------------
        // ONSPD: Fetch centroid coordinates for this postcode
        // Use indexed `pcds` lookup (avoid REPLACE/UPPER scans).
        // -----------------------------------------------------
        $toPcds = function (?string $pc) {
            $pc = strtoupper(trim((string) $pc));
            $pc = preg_replace('/\s+/', '', $pc);
            if ($pc === '' || strlen($pc) < 5) {
                return null;
            }
            return substr($pc, 0, -3) . ' ' . substr($pc, -3);
        };

        $pcds = $toPcds($postcode);
        $mapLat = null;
        $mapLong = null;

        if ($pcds) {
            $coordCacheKey = 'onspd:coords:pcds:' . $pcds;
            $coords = Cache::remember($coordCacheKey, now()->addDays(90), function () use ($pcds) {
                return DB::table('onspd')
                    ->select(['lat', 'long'])
                    ->where('pcds', $pcds)
                    ->first();
            });

            if ($coords) {
                $mapLat = $coords->lat !== null ? (float) $coords->lat : null;
                $mapLong = $coords->long !== null ? (float) $coords->long : null;
            }
        }

        // -----------------------------------------------------
        // Deprivation (IMD) — resolve via ONSPD → LSOA (England/Wales)
        // -----------------------------------------------------
        $depr = null;
        $deprMsg = null;
        $lsoaLink = null;

        // Helper: check if a DB table exists
        $tableExists = function (string $table): bool {
            return Schema::hasTable($table);
        };

        // Helper: check if a column exists on a table
        $hasColumn = function (string $table, string $column): bool {
            return Schema::hasColumn($table, $column);
        };

        // Helper: fetch IMD row for an LSOA from whichever table/column exists
        $resolveImdForLsoa = function (string $lsoa) use ($tableExists, $hasColumn) {
            // Candidate tables (adjust if yours differs)
            $tables = [
                'imd2025',   // England (IoD/IMD 2025)
                'wimd2019',  // Wales (WIMD 2019)
            ];

            // Candidate key columns
            $keyCols = [
                // England IMD 2025
                'LSOA_Code_2021',

                // Wales WIMD 2019
                'LSOA_code',

                // Common variants
                'lsoa21cd', 'lsoa21', 'LSOA21CD',
                'lsoa11cd', 'lsoa11', 'LSOA11CD',
                'lsoa_code', 'lsoa', 'LSOA',
            ];

            // Candidate data columns (we'll also auto-detect below from information_schema)
            $rankCols   = [
                // common
                'Index_of_Multiple_Deprivation_Rank',
                'rank', 'Rank', 'imd_rank', 'IMD_RANK', 'wimd_rank', 'WIMD_RANK',
                // likely overall fields
                'Overall_Rank', 'overall_rank', 'IMD_Rank', 'IMD_rank', 'WIMD_Rank', 'WIMD_rank',
                // versioned fields
                'IMD_Rank_2025', 'IMD_rank_2025', 'Rank_2025',
                'WIMD_Rank_2019', 'WIMD_rank_2019', 'Rank_2019',
            ];
            $decileCols = [
                'Index_of_Multiple_Deprivation_Decile',
                'decile', 'Decile', 'imd_decile', 'IMD_DECILE', 'wimd_decile', 'WIMD_DECILE',
                'Overall_Decile', 'overall_decile', 'IMD_Decile', 'IMD_decile', 'WIMD_Decile', 'WIMD_decile',
                'IMD_Decile_2025', 'IMD_decile_2025', 'Decile_2025',
                'WIMD_Decile_2019', 'WIMD_decile_2019', 'Decile_2019',
            ];
            $nameCols   = ['LSOA_Name_2021', 'name', 'lsoa_name', 'lsoa21nm', 'LSOA21NM', 'lsoa11nm', 'LSOA11NM', 'LSOA_Name', 'LSOA_name'];

            foreach ($tables as $table) {
                if (!$tableExists($table)) {
                    continue;
                }

                // Cache column list for this table so we can auto-detect rank/decile/name columns
                $cols = Cache::remember('depr:cols:' . $table, now()->addDays(90), function () use ($table) {
                    try {
                        $dbName = DB::getDatabaseName();
                        return DB::table('information_schema.columns')
                            ->where('table_schema', $dbName)
                            ->where('table_name', $table)
                            ->orderBy('ordinal_position')
                            ->pluck('column_name')
                            ->map(fn ($c) => (string) $c)
                            ->toArray();
                    } catch (\Throwable $e) {
                        return [];
                    }
                });

                $pickCol = function (array $preferred) use ($cols) {
                    if (empty($cols)) return null;
                    // 1) exact match priority
                    foreach ($preferred as $p) {
                        if (in_array($p, $cols, true)) return $p;
                    }
                    // 2) case-insensitive exact match
                    $lc = array_map('strtolower', $cols);
                    foreach ($preferred as $p) {
                        $idx = array_search(strtolower($p), $lc, true);
                        if ($idx !== false) return $cols[$idx];
                    }
                    // 3) contains match (try to find an overall/imd/wimd rank/decile)
                    foreach ($preferred as $p) {
                        $needle = strtolower($p);
                        foreach ($cols as $c) {
                            if (str_contains(strtolower($c), $needle)) {
                                return $c;
                            }
                        }
                    }
                    return null;
                };

                // Prefer "overall" columns first if present, then fall back
                $autoRankCol = $pickCol([
                    'Index_of_Multiple_Deprivation_Rank',
                    'overall_rank', 'Overall_Rank',
                    'imd_rank', 'IMD_RANK',
                    'wimd_rank', 'WIMD_RANK',
                    'rank', 'Rank'
                ]);
                $autoDecileCol = $pickCol([
                    'Index_of_Multiple_Deprivation_Decile',
                    'overall_decile', 'Overall_Decile',
                    'imd_decile', 'IMD_DECILE',
                    'wimd_decile', 'WIMD_DECILE',
                    'decile', 'Decile'
                ]);
                $autoNameCol = $pickCol(['LSOA_Name_2021', 'lsoa_name', 'LSOA_Name', 'lsoa21nm', 'LSOA21NM', 'name']);

                // Prefer explicit mappings for known tables
                $forced = null;
                if ($table === 'imd2025') {
                    $forced = [
                        'key' => 'LSOA_Code_2021',
                        'name' => 'LSOA_Name_2021',
                        'rank' => 'Index_of_Multiple_Deprivation_Rank',
                        'decile' => 'Index_of_Multiple_Deprivation_Decile',
                    ];
                } elseif ($table === 'wimd2019') {
                    $forced = [
                        'key' => 'LSOA_code',
                        'name' => 'LSOA_name',
                        // WIMD dataset provided here includes an overall field `WIMD_2019`.
                        // We treat it as an overall rank and derive decile from rank/total.
                        'rank' => 'WIMD_2019',
                        'decile' => null,
                    ];
                }

                $keyCol = null;
                if ($forced && $hasColumn($table, $forced['key'])) {
                    $keyCol = $forced['key'];
                } else {
                    foreach ($keyCols as $c) {
                        if ($hasColumn($table, $c)) {
                            $keyCol = $c;
                            break;
                        }
                    }
                }

                if (!$keyCol) {
                    continue;
                }

                // Build a select list based on what actually exists
                $select = [$keyCol];

                // If we have explicit mappings, set them up first
                $rankCol = null;
                $decileCol = null;
                $nameCol = null;

                if ($forced) {
                    if (!empty($forced['rank']) && $hasColumn($table, $forced['rank'])) {
                        $rankCol = $forced['rank'];
                        $select[] = $rankCol;
                    }
                    if (!empty($forced['decile']) && $hasColumn($table, $forced['decile'])) {
                        $decileCol = $forced['decile'];
                        $select[] = $decileCol;
                    }
                    if (!empty($forced['name']) && $hasColumn($table, $forced['name'])) {
                        $nameCol = $forced['name'];
                        $select[] = $nameCol;
                    }
                }

                // If not forced, use auto-detection / fallbacks
                if (!$rankCol) {
                    $rankCol = $autoRankCol;
                    if (!$rankCol) {
                        foreach ($rankCols as $c) {
                            if ($hasColumn($table, $c)) {
                                $rankCol = $c;
                                break;
                            }
                        }
                    }
                    if ($rankCol) {
                        $select[] = $rankCol;
                    }
                }

                if (!$decileCol) {
                    $decileCol = $autoDecileCol;
                    if (!$decileCol) {
                        foreach ($decileCols as $c) {
                            if ($hasColumn($table, $c)) {
                                $decileCol = $c;
                                break;
                            }
                        }
                    }
                    if ($decileCol) {
                        $select[] = $decileCol;
                    }
                }

                if (!$nameCol) {
                    $nameCol = $autoNameCol;
                    if (!$nameCol) {
                        foreach ($nameCols as $c) {
                            if ($hasColumn($table, $c)) {
                                $nameCol = $c;
                                break;
                            }
                        }
                    }
                    if ($nameCol) {
                        $select[] = $nameCol;
                    }
                }

                $row = DB::table($table)
                    ->select($select)
                    ->where($keyCol, trim((string) $lsoa))
                    ->first();

                if ($row) {
                    // Total rows (for % calc) — cache it because COUNT(*) can be expensive
                    $total = Cache::remember('imd:total:' . $table, now()->addDays(90), function () use ($table) {
                        return (int) DB::table($table)->count();
                    });

                    $rank = $rankCol ? (int) ($row->{$rankCol} ?? 0) : 0;
                    $decile = $decileCol ? (int) ($row->{$decileCol} ?? 0) : 0;

                    // For Wales (wimd2019), decile is not present in this dataset — derive from rank/total
                    if ($table === 'wimd2019' && $decile === 0 && $rank > 0 && $total > 0) {
                        $decile = (int) max(1, min(10, ceil(($rank / $total) * 10)));
                    }

                    // If decile is missing but rank exists, derive decile from rank/total (fallback for other tables)
                    if ($decile === 0 && $rank > 0 && $total > 0) {
                        $decile = (int) max(1, min(10, ceil(($rank / $total) * 10)));
                    }

                    $pct = null;
                    if ($rank > 0 && $total > 0) {
                        $pct = round(($rank / $total) * 100, 1);
                    }

                    $name = $nameCol ? (string) ($row->{$nameCol} ?? '') : '';

                    return [
                        'table' => $table,
                        'rank' => $rank ?: null,
                        'decile' => $decile ?: null,
                        'name' => $name !== '' ? $name : null,
                        'total' => $total ?: null,
                        'pct' => $pct,
                    ];
                }
            }

            return null;
        };

        // Resolve the postcode to an LSOA via ONSPD.
        // Show deprivation if it looks like an English (E01...) or Welsh (W01...) LSOA code.
        if ($pcds) {
            $onspdRow = Cache::remember('onspd:row:pcds:' . $pcds, now()->addDays(90), function () use ($pcds) {
                return DB::table('onspd')->where('pcds', $pcds)->first();
            });

            if (!$onspdRow) {
                $deprMsg = 'Unable to resolve this postcode to ONSPD.';
            } else {
                $lsoa = $onspdRow->lsoa21 ?? $onspdRow->lsoa21cd ?? $onspdRow->LSOA21CD ?? null;
                if (!$lsoa) {
                    $lsoa = $onspdRow->lsoa11 ?? $onspdRow->lsoa11cd ?? $onspdRow->LSOA11CD ?? null;
                }

                // England LSOAs typically start with E01; Wales typically start with W01
                $lsoa = $lsoa ? trim((string) $lsoa) : null;
                $isEngland = $lsoa && str_starts_with($lsoa, 'E01');
                $isWales   = $lsoa && str_starts_with($lsoa, 'W01');

                if (!$lsoa || (!$isEngland && !$isWales)) {
                    $deprMsg = 'Unable to resolve this postcode to an English or Welsh LSOA.';
                } else {
                    $tableHint = $isEngland ? 'imd2025' : 'wimd2019';
                    $imd = Cache::remember('depr:lsoa:' . $tableHint . ':' . $lsoa, now()->addDays(90), function () use ($resolveImdForLsoa, $lsoa) {
                        return $resolveImdForLsoa((string) $lsoa);
                    });

                    if (!$imd) {
                        $deprMsg = 'LSOA found, but no deprivation record could be located in the database.';
                    } else {
                        $depr = [
                            'lsoa21' => (string) $lsoa,
                            'name' => $imd['name'] ?? null,
                            'rank' => $imd['rank'] ?? null,
                            'decile' => $imd['decile'] ?? null,
                            'pct' => $imd['pct'] ?? null,
                            'total' => $imd['total'] ?? null,
                            // Reuse postcode centroid for map link
                            'lat' => $mapLat,
                            'long' => $mapLong,
                        ];

                    // Link to the deprivation detail page
                    if ($isEngland) {
                        $lsoaLink = route('deprivation.show', ['lsoa21cd' => (string) $lsoa]);
                    } elseif ($isWales) {
                        $lsoaLink = route('deprivation.wales.show', ['lsoa' => (string) $lsoa]);
                    } else {
                        $lsoaLink = null;
                    }
                    }
                }
            }
        } else {
            $deprMsg = 'Postcode missing; cannot resolve deprivation.';
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
            'pcds' => $pcds,
            'mapLat' => $mapLat,
            'mapLong' => $mapLong,
            'depr' => $depr,
            'deprMsg' => $deprMsg,
            'lsoaLink' => $lsoaLink,
        ]);


    }


}
