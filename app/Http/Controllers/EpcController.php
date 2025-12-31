<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EpcController extends Controller
{
    /**
     * EPC Dashboard
     *
     * Shows high‑level stats and a few quick charts/tables backed by simple,
     * index‑friendly queries. Reads from epc_certificates (E&W) or epc_certificates_scotland.
     */
    public function home()
    {
        $nation = request()->query('nation', 'ew'); // 'ew' | 'scotland'

        // Nation-specific config to avoid duplicated query blocks
        $cfg = ($nation === 'scotland')
            ? [
                'table'        => 'epc_certificates_scotland',
                'dateExpr'     => "STR_TO_DATE(LODGEMENT_DATE, '%Y-%m-%d')",
                'yearExpr'     => "SUBSTRING(LODGEMENT_DATE,1,4)",
                'dateCol'      => 'LODGEMENT_DATE',
                'currentCol'   => 'CURRENT_ENERGY_RATING',
                'potentialCol' => 'POTENTIAL_ENERGY_RATING',
                'roomsCol'     => 'NUMBER_HABITABLE_ROOMS',
                'ageCol'       => 'CONSTRUCTION_AGE_BAND',
                'since'        => Carbon::create(2015, 1, 1),
              ]
            : [
                'table'        => 'epc_certificates',
                'dateExpr'     => 'lodgement_date',
                'yearExpr'     => 'YEAR(lodgement_date)',
                'dateCol'      => 'lodgement_date',
                'currentCol'   => 'current_energy_rating',
                'potentialCol' => 'potential_energy_rating',
                'roomsCol'     => 'number_habitable_rooms',
                'ageCol'       => 'construction_age_band',
                'since'        => Carbon::create(2008, 1, 1),
              ];

        $today   = Carbon::today();
        $ttl     = 60 * 60 * 24 * 45; // 45 days
        $ck      = fn(string $k) => "epc:{$nation}:{$k}"; // cache key helper
        $ratings = ['A','B','C','D','E','F','G'];

        // 1) Totals & recency
        $stats = Cache::remember($ck('stats'), $ttl, function () use ($cfg, $today) {
            // Latest date from dataset
            $maxDate = DB::table($cfg['table'])
                ->selectRaw("MAX({$cfg['dateExpr']}) as d")
                ->value('d');

            $last30FromLatest = $maxDate ? Carbon::parse($maxDate)->copy()->subDays(30) : $today->copy()->subDays(30);

            $last30Count = $maxDate
                ? (int) DB::table($cfg['table'])
                    ->whereBetween(DB::raw($cfg['dateExpr']), [$last30FromLatest, $maxDate])
                    ->count()
                : 0;

            $last365Count = (int) DB::table($cfg['table'])
                ->whereBetween(DB::raw($cfg['dateExpr']), [$today->copy()->subDays(365), $today])
                ->count();

            return [
                'total'            => (int) DB::table($cfg['table'])->count(),
                'latest_lodgement' => $maxDate,
                'last30_count'     => $last30Count,
                'last365_count'    => $last365Count,
            ];
        });

        // 2) Certificates by year
        $byYear = Cache::remember($ck('byYear'), $ttl, function () use ($cfg) {
            return DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->groupBy('yr')
                ->orderBy('yr', 'asc')
                ->get();
        });

        // 3) Actual energy ratings by year (A–G only)
        $ratingByYear = Cache::remember($ck('ratingByYear'), $ttl, function () use ($cfg, $ratings) {
            return DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$cfg['currentCol']} as rating, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereIn($cfg['currentCol'], $ratings)
                ->groupBy('yr', 'rating')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD({$cfg['currentCol']}, 'A','B','C','D','E','F','G')")
                ->get();
        });

        // 4) Potential energy ratings by year (A–G only)
        $potentialByYear = Cache::remember($ck('potentialByYear'), $ttl, function () use ($cfg, $ratings) {
            return DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$cfg['potentialCol']} as rating, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereIn($cfg['potentialCol'], $ratings)
                ->groupBy('yr', 'rating')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD({$cfg['potentialCol']}, 'A','B','C','D','E','F','G')")
                ->get();
        });

        // 6) Tenure by year: normalise variants into 3 buckets
        // EPC tenure values can vary (e.g. "Rented (private)" vs "Rental (private)").
        $tenureLabels = ['Owner-occupied','Rented (private)','Rented (social)'];

        $tenureByYear = Cache::remember($ck('tenureByYear'), $ttl, function () use ($cfg, $tenureLabels) {
            // Normalise common variants into our 3 labels. Anything else is ignored.
            $tenureCase = "CASE\n"
                . "  WHEN tenure IN ('Owner-occupied','Owner occupied','Owner Occupied','Owner-Occupied') THEN 'Owner-occupied'\n"
                . "  WHEN tenure IN ('Rented (private)','Rental (private)','Private rented','Private Rented','Rented - private','Rental - private') THEN 'Rented (private)'\n"
                . "  WHEN tenure IN ('Rented (social)','Rental (social)','Social rented','Social Rented','Rented - social','Rental - social') THEN 'Rented (social)'\n"
                . "  ELSE NULL\n"
                . "END";

            return DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$tenureCase} as tenure, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereNotNull('tenure')
                ->groupBy('yr', 'tenure')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD(tenure, '" . implode("','", $tenureLabels) . "')")
                ->get();
        });

        // 7) Habitable rooms by year (for charting)
        // Keep it simple + safe: only numeric values in a sensible range.
        $roomsByYear = Cache::remember($ck('roomsByYear'), $ttl, function () use ($cfg) {
            // Build a numeric expression that works for both tables.
            // Scotland columns are uppercase strings; E&W is typically numeric already.
            $roomsExpr = ($cfg['table'] === 'epc_certificates_scotland')
                ? "CAST(NULLIF({$cfg['roomsCol']}, '') AS UNSIGNED)"
                : "CAST({$cfg['roomsCol']} AS UNSIGNED)";

            return DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$roomsExpr} as rooms, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereRaw("{$roomsExpr} BETWEEN 1 AND 20")
                ->groupBy('yr', 'rooms')
                ->orderBy('yr', 'asc')
                ->orderBy('rooms', 'asc')
                ->get();
        });

        // 5) Distribution of current ratings (optional for Scotland too)
        $ratingDist = Cache::remember($ck('ratingDist'), $ttl, function () use ($cfg, $ratings) {
            return DB::table($cfg['table'])
                ->selectRaw("
                    CASE
                        WHEN {$cfg['currentCol']} IN ('A','B','C','D','E','F','G') THEN {$cfg['currentCol']}
                        WHEN {$cfg['currentCol']} IS NULL THEN 'Unknown'
                        ELSE 'Other'
                    END as rating,
                    COUNT(*) as cnt
                ")
                ->groupBy('rating')
                ->orderByRaw("FIELD(rating, 'A','B','C','D','E','F','G','Other','Unknown')")
                ->get();
        });

        // 8) Construction age band distribution (nation-specific rules)
        $ageDist = Cache::remember($ck('ageDist:v2'), $ttl, function () use ($cfg) {
            // Scotland: show the official bands including "2008 onwards"
            if ($cfg['table'] === 'epc_certificates_scotland') {
                $labels = [
                    'before 1919',
                    '1919-1929',
                    '1930-1949',
                    '1950-1964',
                    '1965-1975',
                    '1976-1983',
                    '1984-1991',
                    '1992-1998',
                    '1999-2002',
                    '2003-2007',
                    '2008 onwards',
                ];

                return DB::table($cfg['table'])
                    ->selectRaw("{$cfg['ageCol']} as band, COUNT(*) as cnt")
                    ->whereNotNull($cfg['ageCol'])
                    ->whereIn($cfg['ageCol'], $labels)
                    ->groupBy('band')
                    ->orderByRaw("FIELD(band, '" . implode("','", $labels) . "')")
                    ->get();
            }

            // England & Wales: bucket numeric/parsable years into broad ranges
            // Ignore:
            // - anything starting with "England and Wales:"
            // - INVALID!, NO DATA!, Not applicable
            // - anything > 2025 (bad inputs)
            //
            // Buckets:
            //   < 1900
            //   1900–1949
            //   1950–1999
            //   2000–2009
            //   2010–2019
            //   2020–2025
            $col = $cfg['ageCol'];
            $valExpr = "TRIM(CASE WHEN {$col} LIKE 'England and Wales:%' THEN SUBSTRING({$col}, LOCATE(':', {$col}) + 1) ELSE {$col} END)";

            $yearExpr = "CASE\n"
                . "  WHEN {$col} IS NULL THEN NULL\n"
                . "  WHEN {$valExpr} IN ('INVALID!', 'NO DATA!', 'Not applicable') THEN NULL\n"
                // "before 1900" style -> treat as 1899 so it lands in < 1900
                . "  WHEN LOWER({$valExpr}) LIKE 'before %' THEN CAST(REGEXP_REPLACE(LOWER({$valExpr}), '[^0-9]', '') AS UNSIGNED) - 1\n"
                // "1900-1929" style -> take first year
                . "  WHEN {$valExpr} REGEXP '^[0-9]{4}\\s*-\\s*[0-9]{4}$' THEN CAST(SUBSTRING_INDEX(REPLACE({$valExpr}, ' ', ''), '-', 1) AS UNSIGNED)\n"
                // "2007 onwards" style -> take the year
                . "  WHEN {$valExpr} REGEXP '^[0-9]{4}\\s*onwards$' THEN CAST(REGEXP_REPLACE({$valExpr}, '[^0-9]', '') AS UNSIGNED)\n"
                // plain "1900" style
                . "  WHEN {$valExpr} REGEXP '^[0-9]{4}$' THEN CAST({$valExpr} AS UNSIGNED)\n"
                . "  ELSE NULL\n"
                . "END";

            $bucketExpr = "CASE\n"
                . "  WHEN y IS NULL THEN NULL\n"
                . "  WHEN y > 2025 THEN NULL\n"
                . "  WHEN y < 1900 THEN '< 1900'\n"
                . "  WHEN y BETWEEN 1900 AND 1949 THEN '1900–1949'\n"
                . "  WHEN y BETWEEN 1950 AND 1999 THEN '1950–1999'\n"
                . "  WHEN y BETWEEN 2000 AND 2009 THEN '2000–2009'\n"
                . "  WHEN y BETWEEN 2010 AND 2019 THEN '2010–2019'\n"
                . "  WHEN y BETWEEN 2020 AND 2025 THEN '2020–2025'\n"
                . "  ELSE NULL\n"
                . "END";

            $order = [
                '< 1900',
                '1900–1949',
                '1950–1999',
                '2000–2009',
                '2010–2019',
                '2020–2025',
            ];

            return DB::query()
                ->fromSub(function ($q) use ($cfg, $yearExpr) {
                    $q->from($cfg['table'])
                        ->selectRaw("{$yearExpr} as y")
                        ->whereNotNull($cfg['ageCol']);
                }, 't')
                ->selectRaw("{$bucketExpr} as band, COUNT(*) as cnt")
                ->havingRaw("band IS NOT NULL")
                ->groupBy('band')
                ->orderByRaw("FIELD(band, '" . implode("','", $order) . "')")
                ->get();
        });

        return view('epc.home', [
            'stats'            => $stats,
            'byYear'           => $byYear,
            'ratingByYear'     => $ratingByYear,
            'potentialByYear'  => $potentialByYear,
            'tenureByYear'     => $tenureByYear,
            'roomsByYear'      => $roomsByYear,
            'ratingDist'       => $ratingDist ?? collect(),
            'ageDist'          => $ageDist ?? collect(),
            'nation'           => $nation,
        ]);
    }

    // Search for EPC certificates by postcode (exact match)
    public function search(Request $request)
    {
        // If no postcode provided, just render the form
        $postcodeInput = (string) $request->query('postcode', '');
        if (trim($postcodeInput) === '') {
            return view('epc.search');
        }

        // Validate: require a plausible UK postcode
        $request->validate([
            'postcode' => ['required','string','max:16','regex:/^[A-Za-z]{1,2}\\d[A-Za-z\\d]?\\s*\\d[A-Za-z]{2}$/'],
        ], [
            'postcode.regex' => 'Please enter a full UK postcode (e.g. W11 3TH).',
        ]);

        // Normalise to canonical form: uppercase and single space before last 3 chars
        $postcode = $this->normalisePostcode($postcodeInput);

        // Sorting (whitelist fields to avoid SQL injection)
        $allowedSorts = [
            'lodgement_date'          => 'lodgement_date',
            'address'                 => 'address',
            'current_energy_rating'   => 'current_energy_rating',
            'potential_energy_rating' => 'potential_energy_rating',
            'property_type'           => 'property_type',
            'total_floor_area'        => 'total_floor_area',
        ];
        $sort = $request->query('sort', 'lodgement_date');
        $dir  = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortCol = $allowedSorts[$sort] ?? 'lodgement_date';

        // Query by postcode only with dynamic sorting
        $query = DB::table('epc_certificates')
            ->select('lmk_key','address','postcode','lodgement_date','current_energy_rating','potential_energy_rating','property_type','total_floor_area','local_authority_label')
            ->where('postcode', $postcode)
            ->orderBy($sortCol, $dir);

        // Secondary tiebreaker to keep results stable
        if ($sortCol !== 'lodgement_date') {
            $query->orderBy('lodgement_date', 'desc');
        }

        $results = $query->paginate(50)->withQueryString();

        return view('epc.search', compact('results'));
    }

    // Scotland: Search EPC certificates by postcode (exact match)
    public function searchScotland(Request $request)
    {
        // If no postcode provided, just render the Scotland form
        $postcodeInput = (string) $request->query('postcode', '');
        if (trim($postcodeInput) === '') {
            return view('epc.search_scotland');
        }

        // Validate: require a plausible full UK postcode (same rule as E&W)
        $request->validate([
            'postcode' => ['required','string','max:16','regex:/^[A-Za-z]{1,2}\\d[A-Za-z\\d]?\\s*\\d[A-Za-z]{2}$/'],
        ], [
            'postcode.regex' => 'Please enter a full UK postcode (e.g. G12 8QQ).',
        ]);

        // Normalise to canonical form: uppercase and single space before last 3 chars
        $postcode = $this->normalisePostcode($postcodeInput);

        // Sorting (whitelist fields)
        $allowedSorts = [
            'lodgement_date'          => 'lodgement_date',
            'address'                 => 'address',
            'current_energy_rating'   => 'current_energy_rating',
            'potential_energy_rating' => 'potential_energy_rating',
            'property_type'           => 'property_type',
            'total_floor_area'        => 'total_floor_area',
        ];
        $sort   = $request->query('sort', 'lodgement_date');
        $dir    = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortCol = $allowedSorts[$sort] ?? 'lodgement_date';

        // Build address expression that works whether Scotland data has a single `address` column
        $addressExpr = DB::raw("NULLIF(TRIM(CONCAT_WS(', ',
            NULLIF(ADDRESS1, ''),
            NULLIF(ADDRESS2, ''),
            NULLIF(ADDRESS3, '')
        )), '') as address");

        $resultsQuery = DB::table('epc_certificates_scotland')
            ->select([
                DB::raw('REPORT_REFERENCE_NUMBER as report_reference_number'),
                DB::raw('BUILDING_REFERENCE_NUMBER as building_reference_number'),
                DB::raw('POSTCODE as postcode'),
                DB::raw('LODGEMENT_DATE as lodgement_date'),
                DB::raw('CURRENT_ENERGY_RATING as current_energy_rating'),
                DB::raw('POTENTIAL_ENERGY_RATING as potential_energy_rating'),
                DB::raw('PROPERTY_TYPE as property_type'),
                DB::raw('TOTAL_FLOOR_AREA as total_floor_area'),
                DB::raw('LOCAL_AUTHORITY_LABEL as local_authority_label'),
                $addressExpr,
            ])
            ->where('POSTCODE', $postcode)
            ->orderBy($sortCol, $dir);

        if ($sortCol !== 'lodgement_date') {
            $resultsQuery->orderBy('lodgement_date', 'desc');
        }

        $results = $resultsQuery->paginate(50)->withQueryString();

        return view('epc.search_scotland', compact('results'));
    }

    /**
     * Show a single EPC report by LMK/Building Reference.
     *
     * Scotland uses BUILDING_REFERENCE_NUMBER, while England & Wales use lmk_key.
     * We attempt Scotland first, then fall back to E&W. We pass the full row
     * (all columns) through to the view so we can decide later what to surface.
     */
    public function show(Request $request, string $lmk)
    {
        $encoded = $request->query('r');
        $incomingReturn = $request->query('return');

        $decoded = null;
        if ($encoded) {
            $decoded = base64_decode($encoded, true) ?: null;
        }

        // Prefer decoded `r`, then plain `return` param
        $backUrlParam = $decoded ?: $incomingReturn;
        $fallbackScot = route('epc.search_scotland');
        $fallbackEW   = route('epc.search');

        // --- Try Scotland first
        $scot = DB::table('epc_certificates_scotland')
            ->where('BUILDING_REFERENCE_NUMBER', $lmk)
            ->first();

        if ($scot) {
            // Build a readable address similar to searchScotland()
            $address = trim(implode(', ', array_filter([
                $scot->ADDRESS1 ?? null,
                $scot->ADDRESS2 ?? null,
                $scot->ADDRESS3 ?? null,
            ])));

            $record = (array) $scot;
            $record['address_display'] = $address;
            $record['nation'] = 'scotland';

            return view('epc.show', [
                'nation'  => 'scotland',
                'lmk'     => $lmk,
                'record'  => $record,   // full row as associative array
                'columns' => array_keys($record),
                'backUrl' => $backUrlParam ?: $fallbackScot,
            ]);
        }

        // --- Fall back to England & Wales
        $ew = DB::table('epc_certificates')
            ->where('lmk_key', $lmk)
            ->first();

        if ($ew) {
            $record = (array) $ew;
            // Keep a consistent extra field for display if needed
            if (!array_key_exists('address_display', $record)) {
                $record['address_display'] = $record['address'] ?? null;
            }
            $record['nation'] = 'ew';

            return view('epc.show', [
                'nation'  => 'ew',
                'lmk'     => $lmk,
                'record'  => $record,   // full row as associative array
                'columns' => array_keys($record),
                'backUrl' => $backUrlParam ?: $fallbackEW,
            ]);
        }

        // Not found in either dataset
        abort(404);
    }

    /**
     * Normalise a UK postcode to uppercase with a single space before the final 3 characters.
     */
    protected function normalisePostcode(string $pc): string
    {
        $pc = strtoupper(preg_replace('/\s+/', '', $pc));
        if (strlen($pc) >= 5) {
            return substr($pc, 0, -3) . ' ' . substr($pc, -3);
        }
        return $pc;
    }

    /**
     * Show a single Scotland EPC report by REPORT_REFERENCE_NUMBER.
     */
    public function showScotland(Request $request, string $rrn)
    {
        $encoded = $request->query('r');
        $decoded = $encoded ? base64_decode($encoded, true) : null;

        $backUrl = $decoded ?: route('epc.search_scotland');

        $scot = DB::table('epc_certificates_scotland')
            ->where('REPORT_REFERENCE_NUMBER', $rrn)
            ->first();

        abort_if(!$scot, 404);

        // Build a readable address
        $address = trim(implode(', ', array_filter([
            $scot->ADDRESS1 ?? null,
            $scot->ADDRESS2 ?? null,
            $scot->ADDRESS3 ?? null,
        ])));

        $record = (array) $scot;
        $record['address_display'] = $address;
        $record['nation'] = 'scotland';

        return view('epc.show', [
            'nation'  => 'scotland',
            'lmk'     => $rrn, // reuse slot; Scotland uses RRN
            'record'  => $record,
            'columns' => array_keys($record),
            'backUrl' => $backUrl,
        ]);
    }
}
