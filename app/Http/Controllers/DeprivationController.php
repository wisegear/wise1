<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

        // England — IMD base query (for top/bottom 10)
        $imdBase = DB::table('lsoa21_ruc_geo as g')
            ->leftJoin('lsoa_2011_to_2021 as map', 'map.LSOA21CD', '=', 'g.LSOA21CD')
            ->leftJoin('imd2019 as imd_dec', function ($j) {
                $j->on('imd_dec.FeatureCode', '=', 'map.LSOA11CD')
                  ->where('imd_dec.measurement_norm', '=', 'decile')
                  ->where('imd_dec.iod_norm', 'like', 'a. index of multiple deprivation%');
            })
            ->leftJoin('imd2019 as imd_rank', function ($j) {
                $j->on('imd_rank.FeatureCode', '=', 'map.LSOA11CD')
                  ->where('imd_rank.measurement_norm', '=', 'rank')
                  ->where('imd_rank.iod_norm', 'like', 'a. index of multiple deprivation%');
            })
            ->where('g.LSOA21CD', 'like', 'E%')
            ->whereNotNull('imd_rank.Value')
            ->select([
                'g.LSOA21CD as lsoa21cd',
                'g.LSOA21NM as lsoa_name',
                'imd_dec.Value as decile',
                'imd_rank.Value as rank',
            ]);

        $engTop10 = Cache::remember('imd:top10', $ttl, function () use ($imdBase) {
            $data = (clone $imdBase)
                ->orderByDesc('rank') // highest rank = least deprived
                ->limit(10)
                ->get();
            Cache::put('imd:last_warm', now()->toDateTimeString());
            return $data;
        });

        $engBottom10 = Cache::remember('imd:bottom10', $ttl, function () use ($imdBase) {
            $data = (clone $imdBase)
                ->orderBy('rank') // lowest rank = most deprived
                ->limit(10)
                ->get();
            Cache::put('imd:last_warm', now()->toDateTimeString());
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

        // Total ranks for contextual percentages
        $totalIMD = Cache::rememberForever('imd.total_rank', function () {
            $n = (int) (DB::table('imd2019')
                ->where('measurement_norm', 'rank')
                ->where('iod_norm', 'like', 'a. index of multiple deprivation%')
                ->max('Value') ?? 0);
            return $n ?: 32844;
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
            'walTop10'     => $walTop10,
            'walBottom10'  => $walBottom10,
            'totalIMD'    => $totalIMD,
            'totalSIMD'   => $totalSIMD,
            'totalWIMD'   => $totalWIMD,
            // keep postcode input working on the page
            'q' => $q,
            'decile' => $decile,
            'ruc' => $ruc,
            'lad' => $lad,
        ]);
    }

    public function show(string $lsoa21cd)
    {
        // If a Scottish Data Zone code is passed here, forward to the Scotland page
        if ((function_exists('str_starts_with') ? str_starts_with($lsoa21cd, 'S010') : substr($lsoa21cd, 0, 4) === 'S010')) {
            return redirect()->route('deprivation.scot.show', $lsoa21cd);
        }

        // Resolve LSOA21 → LSOA11 (for IMD 2019 joins)
        $base = DB::table('lsoa21_ruc_geo as g')
            ->leftJoin('lsoa_2011_to_2021 as map', 'map.LSOA21CD', '=', 'g.LSOA21CD')
            ->select([
                'g.LSOA21CD','g.LSOA21NM','g.RUC21CD','g.RUC21NM','g.Urban_rura','g.LAT','g.LONG',
                'map.LSOA11CD',
            ])
            ->where('g.LSOA21CD', $lsoa21cd)
            ->first();

        abort_unless($base, 404);

        // Pull all Rank/Decile pairs for this LSOA11 across all domains
        $rows = DB::table('imd2019')
            ->select(['iod_norm as domain_label', 'measurement_norm as meas', 'Value'])
            ->where('FeatureCode', $base->LSOA11CD)
            ->whereIn('measurement_norm', ['rank','decile'])
            ->get();

        // Map rows to canonical IMD domains using tolerant matching
        $groups = [
            'overall'   => ['name' => 'Overall IMD',                          'weight' => 100],
            'income'    => ['name' => 'Income',                               'weight' => 22.5],
            'employment'=> ['name' => 'Employment',                           'weight' => 22.5],
            'education' => ['name' => 'Education, Skills & Training',         'weight' => 13.5],
            'health'    => ['name' => 'Health',                               'weight' => 13.5],
            'crime'     => ['name' => 'Crime',                                'weight' => 9.3],
            'barriers'  => ['name' => 'Barriers to Housing & Services',       'weight' => 9.3],
            'living'    => ['name' => 'Living Environment',                   'weight' => 9.3],
        ];

        $agg = [];
        foreach (array_keys($groups) as $k) {
            $agg[$k] = ['rank' => null, 'decile' => null];
        }

        foreach ($rows as $r) {
            $label = $r->domain_label;  // already lower+trim
            $meas  = $r->meas;          // 'rank' or 'decile'
            $val   = $r->Value;

            $key = null;
            if (str_starts_with($label, 'a. index of multiple deprivation')) {
                $key = 'overall';
            } elseif (str_contains($label, 'income deprivation') && str_contains($label, 'domain')) {
                $key = 'income';
            } elseif (str_contains($label, 'employment deprivation') && str_contains($label, 'domain')) {
                $key = 'employment';
            } elseif (str_contains($label, 'education, skills') && str_contains($label, 'domain')) {
                $key = 'education';
            } elseif (str_contains($label, 'health deprivation') && str_contains($label, 'domain')) {
                $key = 'health';
            } elseif (str_contains($label, 'crime') && str_contains($label, 'domain')) {
                $key = 'crime';
            } elseif (str_contains($label, 'barriers to housing') && str_contains($label, 'domain')) {
                $key = 'barriers';
            } elseif (str_contains($label, 'living environment') && str_contains($label, 'domain')) {
                $key = 'living';
            }

            if ($key !== null) {
                $agg[$key][$meas] = $val;
            }
        }

        // Build ordered list for the view
        $ordered = [];
        foreach (['overall','income','employment','education','health','crime','barriers','living'] as $k) {
            $ordered[] = [
                'key'    => $k,
                'label'  => $groups[$k]['name'],
                'weight' => $groups[$k]['weight'],
                'rank'   => $agg[$k]['rank'],
                'decile' => $agg[$k]['decile'],
            ];
        }

        return view('deprivation.show', [
            'g'       => $base,
            'ordered' => $ordered,
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
            ['label' => 'Income',     'rank' => $row->income_rank ?? null],
            ['label' => 'Employment', 'rank' => $row->employment_rank ?? null],
            ['label' => 'Health',     'rank' => $row->health_rank ?? null],
            ['label' => 'Education',  'rank' => $row->education_rank ?? null],
            ['label' => 'Access',     'rank' => $row->access_rank ?? null],
            ['label' => 'Crime',      'rank' => $row->crime_rank ?? null],
            ['label' => 'Housing',    'rank' => $row->housing_rank ?? null],
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
}