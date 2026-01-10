<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

//now includes all regions

class DeprivationController extends Controller
{
    public function index(Request $req)
    {
        // Filters
        $q        = trim((string)$req->input('q'));          // search in LSOA name or LAD
        $decile   = $req->input('decile');                   // 1..10
        $ruc      = $req->input('ruc');                      // RUC21CD or RUC21NM
        $lad      = $req->input('lad');                      // LAD (by name fragment)
        $perPage  = (int)($req->input('perPage') ?? 25);

        // Optional: direct postcode search (redirects to details page)
        $postcode = trim((string)$req->input('postcode'));
        if ($postcode !== '') {
            // Standardize postcode to PCDS format (e.g., "WR5 3EU")
            $std = function (string $s): string {
                $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', $s));
                if ($s === '' || strlen($s) <= 3) return $s;
                return substr($s, 0, -3) . ' ' . substr($s, -3);
            };

            $pcStd = $std($postcode);                                // e.g. "WR5 3EU"
            $pcKey = strtoupper(str_replace(' ', '', $postcode));    // e.g. "WR53EU"
            $cacheKey = 'onspd:pcmap:' . $pcKey;                     // strict, space-free key

            $row = Cache::get($cacheKey);
            if (!$row) {
                // 1) Try exact indexed match on PCDS for current records first
                $hit = null;
                if ($pcStd !== '') {
                    $hit = DB::table('onspd')
                        ->select(['lsoa21', 'lsoa11', 'ctry', 'pcds'])
                        ->where('pcds', $pcStd)
                        ->where(function ($q) {
                            $q->whereNull('doterm')->orWhere('doterm', '');
                        })
                        ->orderByDesc('dointr')
                        ->first();
                }

                if (!$hit) {
                    // 2) Fallback to normalized comparisons across pcds/pcd2/pcd
                    $hit = DB::table('onspd')
                        ->select(['lsoa21', 'lsoa11', 'ctry', 'pcds'])
                        ->whereRaw("REPLACE(UPPER(pcds),' ','') = ?", [$pcKey])
                        ->orWhereRaw("REPLACE(UPPER(pcd2),' ','') = ?", [$pcKey])
                        ->orWhereRaw("REPLACE(UPPER(pcd),' ','') = ?", [$pcKey])
                        ->orderByDesc('dointr')
                        ->first();
                }

                if ($hit) {
                    Cache::put($cacheKey, $hit, now()->addDays(30));
                    $row = $hit;
                }
            }

            if ($row) {
                $lsoa21 = $row->lsoa21 ?? null;

                // If only LSOA11 exists, bridge to 2021 code
                if (!$lsoa21 && !empty($row->lsoa11)) {
                    $map = DB::table('lsoa_2011_to_2021')
                        ->select('LSOA21CD')
                        ->where('LSOA11CD', $row->lsoa11)
                        ->first();
                    $lsoa21 = $map->LSOA21CD ?? null;
                }

                // If this is a Welsh postcode (WIMD coverage)
                if (isset($row->ctry) && $row->ctry === 'W92000004' && !empty($row->lsoa11)) {
                    return redirect()->route('deprivation.wales.show', [
                        'lsoa' => $row->lsoa11,
                        'pcd'  => $pcStd ?: $pcKey,
                    ]);
                }

                // If this is a Scottish postcode (Data Zone held in lsoa11 as S010…)
                if (!empty($row->lsoa11) && (function_exists('str_starts_with') ? str_starts_with($row->lsoa11, 'S010') : substr($row->lsoa11, 0, 4) === 'S010')) {
                    return redirect()->route('deprivation.scot.show', ['dz' => $row->lsoa11, 'pcd' => $pcStd ?: $pcKey]);
                }

                // Redirect to details if English LSOA (IMD coverage)
                if ($lsoa21 && (function_exists('str_starts_with') ? str_starts_with($lsoa21, 'E') : substr($lsoa21, 0, 1) === 'E')) {
                    return redirect()->route('deprivation.show', $lsoa21);
                }

                // Found but outside GB handling
                session()->flash('status', 'Postcode found but not currently supported for deprivation lookup (England IMD / Scotland SIMD / Wales WIMD only).');
            } else {
                session()->flash('status', 'Postcode not found in ONSPD.');
            }
        }

        // If we reach here, we're not redirecting by postcode. Show a concise dashboard rather than a huge table.

        // Cache helper
        $ttl = now()->addDays(7);

        // England — IMD 2025 base query (for top/bottom 10)
        $imd25Base = DB::table('imd2025')
            ->select([
                'LSOA_Code_2021 as lsoa_code',
                'LSOA_Name_2021 as lsoa_name',
                'Index_of_Multiple_Deprivation_Rank as rank',
                'Index_of_Multiple_Deprivation_Decile as decile',
            ]);

        $engTop10 = Cache::remember('imd25:top10', $ttl, function () use ($imd25Base) {
            $data = (clone $imd25Base)
                ->orderByDesc('rank') // highest rank = least deprived
                ->limit(10)
                ->get();
            Cache::put('imd25:last_warm', now()->toDateTimeString());
            return $data;
        });

        $engBottom10 = Cache::remember('imd25:bottom10', $ttl, function () use ($imd25Base) {
            $data = (clone $imd25Base)
                ->orderBy('rank') // lowest rank = most deprived
                ->limit(10)
                ->get();
            Cache::put('imd25:last_warm', now()->toDateTimeString());
            return $data;
        });

        // Scotland — SIMD Top/Bottom 10 (by overall rank). Use SIMD table directly for speed.
        $scoTop10 = Cache::remember('simd:top10', $ttl, function () {
            $data = DB::table('simd2020')
                ->select([
                    'Data_Zone as data_zone',
                    'Intermediate_Zone',
                    'Council_area',
                    DB::raw('CAST(SIMD2020v2_Decile AS UNSIGNED) as decile'),
                    DB::raw("CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED) as `rank`"),
                ])
                ->whereNotNull('SIMD2020v2_Rank')
                ->orderByRaw("CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED) DESC")
                ->limit(10)
                ->get();
            Cache::put('simd:last_warm', now()->toDateTimeString());
            return $data;
        });

        $scoBottom10 = Cache::remember('simd:bottom10', $ttl, function () {
            $data = DB::table('simd2020')
                ->select([
                    'Data_Zone as data_zone',
                    'Intermediate_Zone',
                    'Council_area',
                    DB::raw('CAST(SIMD2020v2_Decile AS UNSIGNED) as decile'),
                    DB::raw("CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED) as `rank`"),
                ])
                ->whereNotNull('SIMD2020v2_Rank')
                ->orderByRaw("CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED) ASC")
                ->limit(10)
                ->get();
            Cache::put('simd:last_warm', now()->toDateTimeString());
            return $data;
        });

        // Wales — WIMD Top/Bottom 10 (by overall rank)
        $wimdBase = DB::table('wimd2019')
            ->select([
                'LSOA_code as lsoa_code',
                'LSOA_name as lsoa_name',
                'WIMD_2019 as rank',
                DB::raw('CEIL(WIMD_2019 / 190.9) as decile'), // 1..10
            ]);

        $walTop10 = Cache::remember('wimd:top10', $ttl, function () use ($wimdBase) {
            $data = (clone $wimdBase)
                ->orderByDesc('rank') // highest rank = least deprived
                ->limit(10)
                ->get();
            Cache::put('wimd:last_warm', now()->toDateTimeString());
            return $data;
        });

        $walBottom10 = Cache::remember('wimd:bottom10', $ttl, function () use ($wimdBase) {
            $data = (clone $wimdBase)
                ->orderBy('rank') // lowest rank = most deprived
                ->limit(10)
                ->get();
            Cache::put('wimd:last_warm', now()->toDateTimeString());
            return $data;
        });

        // Northern Ireland — total areas so we can calculate deciles
        $totalNI = Cache::rememberForever('nimdm.total_rank', function () {
            $n = (int) (DB::table('ni_deprivation')->max('MDM_rank') ?? 0);
            // NIMDM2017 covers all NI Small Areas (~4,500). If query fails, fall back.
            return $n ?: 4537;
        });

        // Northern Ireland — NIMDM Top/Bottom 10 (by overall rank)
        // We'll calculate decile manually (1 = most deprived, 10 = least deprived)
        $niBase = DB::table('ni_deprivation')
            ->select([
                'SA2011 as sa_code',
                'SOA2001name as sa_name',
                'MDM_rank as rank',
            ]);

        $niTop10 = Cache::remember('nimdm:top10', $ttl, function () use ($niBase, $totalNI) {
            $data = (clone $niBase)
                ->orderByDesc('rank') // highest rank = least deprived
                ->limit(10)
                ->get();

            // Calculate decile (1 = most deprived, 10 = least deprived)
            foreach ($data as $row) {
                if (!is_null($row->rank)) {
                    $row->decile = max(
                        1,
                        min(
                            10,
                            (int) floor((($row->rank - 1) / $totalNI) * 10) + 1
                        )
                    );
                } else {
                    $row->decile = null;
                }
            }

            Cache::put('nimdm:last_warm', now()->toDateTimeString());
            return $data;
        });

        $niBottom10 = Cache::remember('nimdm:bottom10', $ttl, function () use ($niBase, $totalNI) {
            $data = (clone $niBase)
                ->orderBy('rank') // lowest rank = most deprived
                ->limit(10)
                ->get();

            // Calculate decile (1 = most deprived, 10 = least deprived)
            foreach ($data as $row) {
                if (!is_null($row->rank)) {
                    $row->decile = max(
                        1,
                        min(
                            10,
                            (int) floor((($row->rank - 1) / $totalNI) * 10) + 1
                        )
                    );
                } else {
                    $row->decile = null;
                }
            }

            Cache::put('nimdm:last_warm', now()->toDateTimeString());
            return $data;
        });

        // Total ranks for contextual percentages (IMD 2025)
        $totalIMD = Cache::rememberForever('imd25.total_rank', function () {
            $n = (int) (DB::table('imd2025')->max('Index_of_Multiple_Deprivation_Rank') ?? 0);
            return $n ?: 33755;
        });

        $totalSIMD = Cache::rememberForever('simd.total_rank', function () {
            $row = DB::table('simd2020')->selectRaw("MAX(CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED)) as max_rank")->first();
            $n = (int)($row->max_rank ?? 0);
            return $n ?: 6976;
        });

        $totalWIMD = Cache::rememberForever('wimd.total_rank', function () {
            $n = (int) (DB::table('wimd2019')->max('WIMD_2019') ?? 0);
            return $n ?: 1909;
        });

        return view('deprivation.index', [
            'engTop10'    => $engTop10,
            'engBottom10' => $engBottom10,
            'scoTop10'    => $scoTop10,
            'scoBottom10' => $scoBottom10,
            'walTop10'    => $walTop10,
            'walBottom10' => $walBottom10,
            'niTop10'     => $niTop10,
            'niBottom10'  => $niBottom10,
            'totalIMD'    => $totalIMD,
            'totalSIMD'   => $totalSIMD,
            'totalWIMD'   => $totalWIMD,
            'totalNI'     => $totalNI,
            // keep postcode input working on the page
            'q' => $q,
            'decile' => $decile,
            'ruc' => $ruc,
            'lad' => $lad,
        ]);
    }

    public function show(string $lsoaCode)
    {
        // If a Scottish Data Zone code is passed here, forward to the Scotland page
        if ((function_exists('str_starts_with') ? str_starts_with($lsoaCode, 'S010') : substr($lsoaCode, 0, 4) === 'S010')) {
            return redirect()->route('deprivation.scot.show', $lsoaCode);
        }

        // IMD 2025 (England)
        // 1. Fetch geography + deprivation row for this LSOA code
        $row = DB::table('imd2025 as i')
            ->leftJoin('lsoa21_ruc_geo as g', 'g.LSOA21CD', '=', 'i.LSOA_Code_2021')
            ->select([
                'i.LSOA_Code_2021',
                'i.LSOA_Name_2021',
                'i.Index_of_Multiple_Deprivation_Rank as overall_rank',
                'i.Index_of_Multiple_Deprivation_Decile as overall_decile',
                'i.Income_Rank',
                'i.Income_Decile',
                'i.Employment_Rank',
                'i.Employment_Decile',
                'i.Education_Skills_Training_Rank',
                'i.Education_Skills_Training_Decile',
                'i.Health_Deprivation_Disability_Rank',
                'i.Health_Deprivation_Disability_Decile',
                'i.Crime_Rank',
                'i.Crime_Decile',
                'i.Barriers_Housing_Services_Rank',
                'i.Barriers_Housing_Services_Decile',
                'i.Living_Environment_Rank',
                'i.Living_Environment_Decile',
                'g.RUC21CD',
                'g.RUC21NM',
                'g.Urban_rura',
                'g.LAT',
                'g.LONG',
            ])
            ->where('i.LSOA_Code_2021', $lsoaCode)
            ->first();

        abort_unless($row, 404);

        // 2. Total LSOAs for England (for % context)
        $total = Cache::rememberForever('imd25.total_rank', function () {
            $n = (int) (DB::table('imd2025')->max('Index_of_Multiple_Deprivation_Rank') ?? 0);
            return $n ?: 33755; // IoD25: 33,755 LSOAs in England
        });

        $rank = (int) ($row->overall_rank ?? 0); // 1 = most deprived
        $pct  = $rank
            ? max(0, min(100, (int) round((1 - (($rank - 1) / $total)) * 100)))
            : null;

        // 3. Build domain breakdown list for the blade (mirrors Scotland/Wales style)
        $domains = [
            [ 'label' => 'Income', 'rank' => $row->Income_Rank ?? null, 'decile' => $row->Income_Decile ?? null, 'weight' => '22.5%' ],
            [ 'label' => 'Employment', 'rank' => $row->Employment_Rank ?? null, 'decile' => $row->Employment_Decile ?? null, 'weight' => '22.5%' ],
            [ 'label' => 'Education, Skills & Training', 'rank' => $row->Education_Skills_Training_Rank ?? null, 'decile' => $row->Education_Skills_Training_Decile ?? null, 'weight' => '13.5%' ],
            [ 'label' => 'Health Deprivation & Disability', 'rank' => $row->Health_Deprivation_Disability_Rank ?? null, 'decile' => $row->Health_Deprivation_Disability_Decile ?? null, 'weight' => '13.5%' ],
            [ 'label' => 'Crime', 'rank' => $row->Crime_Rank ?? null, 'decile' => $row->Crime_Decile ?? null, 'weight' => '9.3%' ],
            [ 'label' => 'Barriers to Housing & Services', 'rank' => $row->Barriers_Housing_Services_Rank ?? null, 'decile' => $row->Barriers_Housing_Services_Decile ?? null, 'weight' => '9.3%' ],
            [ 'label' => 'Living Environment', 'rank' => $row->Living_Environment_Rank ?? null, 'decile' => $row->Living_Environment_Decile ?? null, 'weight' => '9.3%' ],
        ];

        return view('deprivation.show', [
            'row'     => $row,
            'total'   => $total,
            'pct'     => $pct,
            'domains' => $domains,
            'overall' => [
                'rank' => $row->overall_rank ?? null,
                'decile' => $row->overall_decile ?? null,
            ],
        ]);
    }

    public function showScotland(string $dz)
    {
        $pcd = request('pcd');

        // Prefer the exact postcode row if provided, otherwise fall back to any row in the DZ
        $row = null;
        if (!empty($pcd)) {
            $row = DB::table('v_postcode_deprivation_scotland')
                ->where('data_zone', $dz)
                ->where('postcode', $pcd)
                ->first();
        }

        if (!$row) {
            $row = DB::table('v_postcode_deprivation_scotland')
                ->where('data_zone', $dz)
                ->orderBy('postcode')
                ->first();
        }

        if (!$row) {
            return back()->with('status', 'No SIMD data found for that Scottish Data Zone.');
        }

        // Total Data Zones for percentile (max rank); cache forever, fallback ~6976
        $total = Cache::rememberForever('simd.total_rank', function () {
            $row = DB::table('simd2020')->selectRaw("MAX(CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED)) as max_rank")->first();
            $n = (int)($row->max_rank ?? 0);
            return $n ?: 6976;
        });

        $rank = (int) str_replace(',', '', (string) ($row->rank ?? '0'));
        $pct  = $rank ? max(0, min(100, (int) round((1 - (($rank - 1) / $total)) * 100))) : null;

        $domains = [
            ['label' => 'Income',     'rank' => $row->income_rank     !== null ? (int) str_replace(',', '', $row->income_rank)     : null],
            ['label' => 'Employment', 'rank' => $row->employment_rank !== null ? (int) str_replace(',', '', $row->employment_rank) : null],
            ['label' => 'Health',     'rank' => $row->health_rank     !== null ? (int) str_replace(',', '', $row->health_rank)     : null],
            ['label' => 'Education',  'rank' => $row->education_rank  !== null ? (int) str_replace(',', '', $row->education_rank)  : null],
            ['label' => 'Access',     'rank' => $row->access_rank     !== null ? (int) str_replace(',', '', $row->access_rank)     : null],
            ['label' => 'Crime',      'rank' => $row->crime_rank      !== null ? (int) str_replace(',', '', $row->crime_rank)      : null],
            ['label' => 'Housing',    'rank' => $row->housing_rank    !== null ? (int) str_replace(',', '', $row->housing_rank)    : null],
        ];

        return view('deprivation.scotland_show', [
            'dz'      => $dz,
            'row'     => $row,
            'total'   => $total,
            'pct'     => $pct,
            'domains' => $domains,
        ]);
    }

    public function showWales(string $lsoa)
    {
        $pcd = request('pcd');

        // Prefer the exact postcode row if provided, otherwise any row for the LSOA
        $row = null;
        if (!empty($pcd)) {
            $row = DB::table('v_postcode_deprivation_wales')
                ->where('lsoa_code', $lsoa)
                ->where('postcode', $pcd)
                ->first();
        }
        if (!$row) {
            $row = DB::table('v_postcode_deprivation_wales')
                ->where('lsoa_code', $lsoa)
                ->orderBy('postcode')
                ->first();
        }
        if (!$row) {
            return back()->with('status', 'No WIMD data found for that Welsh LSOA.');
        }

        // Total LSOAs in Wales for percentile context (1,909)
        $total = Cache::rememberForever('wimd.total_rank', function () {
            $max = DB::table('wimd2019')->max('WIMD_2019');
            $n = (int)($max ?? 0);
            return $n ?: 1909;
        });

        $rank = (int) ($row->rank ?? 0); // 1 = most deprived ... total = least
        $pct  = $rank ? max(0, min(100, (int) round((1 - (($rank - 1) / $total)) * 100))) : null;

        $domains = [
            ['label' => 'Income',                'rank' => $row->income_rank ?? null],
            ['label' => 'Employment',            'rank' => $row->employment_rank ?? null],
            ['label' => 'Health',                'rank' => $row->health_rank ?? null],
            ['label' => 'Education',             'rank' => $row->education_rank ?? null],
            ['label' => 'Access to Services',    'rank' => $row->access_rank ?? null],
            ['label' => 'Housing',               'rank' => $row->housing_rank ?? null],
            ['label' => 'Community Safety',      'rank' => $row->community_safety_rank ?? null],
            ['label' => 'Physical Environment',  'rank' => $row->physical_environment_rank ?? null],
        ];

        return view('deprivation.wales_show', [
            'lsoa'    => $lsoa,
            'row'     => $row,
            'total'   => $total,
            'pct'     => $pct,
            'domains' => $domains,
        ]);
    }
    public function showNorthernIreland(string $sa)
    {
        // Fetch deprivation row for this Northern Ireland Small Area (SA2011)
        $row = DB::table('ni_deprivation')
            ->where('SA2011', $sa)
            ->first();

        if (!$row) {
            abort(404, 'Area not found');
        }

        // Total number of areas for rank/percentile context
        $total = Cache::rememberForever('nimdm.total_rank', function () {
            $n = (int) (DB::table('ni_deprivation')->max('MDM_rank') ?? 0);
            return $n ?: 4537; // fallback to full NI Small Area count
        });

        // Build domain list for NI (NIMDM 2017)
        // Column mapping:
        // D1_Income_rank, D2_Empl_rank, D3_Health_rank,
        // P4_Education_rank, P5_Access_rank,
        // D6_LivEnv_rank, D7_CD_rank
        $domains = [
            [ 'label' => 'Income',              'rank' => $row->D1_Income_rank      ?? null ],
            [ 'label' => 'Employment',          'rank' => $row->D2_Empl_rank        ?? null ],
            [ 'label' => 'Health',              'rank' => $row->D3_Health_rank      ?? null ],
            [ 'label' => 'Education',           'rank' => $row->P4_Education_rank   ?? null ],
            [ 'label' => 'Access to Services',  'rank' => $row->P5_Access_rank      ?? null ],
            [ 'label' => 'Living Environment',  'rank' => $row->D6_LivEnv_rank      ?? null ],
            [ 'label' => 'Crime & Disorder',    'rank' => $row->D7_CD_rank          ?? null ],
        ];

        return view('deprivation.ni_show', [
            'sa'      => $sa,
            'row'     => $row,
            'total'   => $total,
            'domains' => $domains,
        ]);
    }
}