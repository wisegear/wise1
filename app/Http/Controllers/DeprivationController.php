<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $pc = strtoupper(preg_replace('/\s+/', '', $postcode));

            // Look up postcode across pcds/pcd2/pcd; prefer latest record
            $row = DB::table('onspd')
                ->select(['lsoa21', 'lsoa11'])
                ->whereRaw("REPLACE(UPPER(pcds),' ','') = ?", [$pc])
                ->orWhereRaw("REPLACE(UPPER(pcd2),' ','') = ?", [$pc])
                ->orWhereRaw("REPLACE(UPPER(pcd),' ','') = ?", [$pc])
                ->orderByDesc('dointr')
                ->first();

            if ($row) {
                $lsoa21 = $row->lsoa21;

                // If only LSOA11 exists, bridge to 2021 code
                if (!$lsoa21 && $row->lsoa11) {
                    $map = DB::table('lsoa_2011_to_2021')
                        ->select('LSOA21CD')
                        ->where('LSOA11CD', $row->lsoa11)
                        ->first();
                    $lsoa21 = $map->LSOA21CD ?? null;
                }

                // Redirect to details if English LSOA (IMD coverage)
                if ($lsoa21 && (function_exists('str_starts_with') ? str_starts_with($lsoa21, 'E') : substr($lsoa21, 0, 1) === 'E')) {
                    return redirect()->route('deprivation.show', $lsoa21);
                }

                // Found but not England (Wales/Scotland/NI): show message
                session()->flash('status', 'Postcode found but not an English LSOA (IMD is England-only).');
            } else {
                session()->flash('status', 'Postcode not found in ONSPD.');
            }
        }

        // Total LSOA count for overall IMD rank (used to show percentile alongside rank)
        $totalRank = (int) (DB::table('imd2019')
            ->where('measurement_norm', 'rank')
            ->where('iod_norm', 'like', 'a. index of multiple deprivation%')
            ->max('Value') ?? 0);
        if ($totalRank === 0) {
            // Sensible fallback for England IMD if dataset shape changes
            $totalRank = 32844;
        }

        // Base: start from LSOA21 names (so UI is readable)
        // Join bridge to get LSOA11, then IMD decile/rank (overall IMD domain)
        $rows = DB::table('lsoa21_ruc_geo as g')
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
            ->select([
                'g.LSOA21CD as lsoa21cd',
                'g.LSOA21NM as lsoa_name',
                'g.RUC21CD', 'g.RUC21NM', 'g.Urban_rura',
                'g.LAT', 'g.LONG',
                'imd_dec.Value as imd_decile',
                'imd_rank.Value as imd_rank',
            ]);

        // Apply filters
        if ($q !== '') {
            $rows->where(function ($w) use ($q) {
                $w->where('g.LSOA21NM', 'like', "%$q%")
                  ->orWhere('g.RUC21NM', 'like', "%$q%");
            });
        }
        if ($lad !== '') {
            // If you also stored LAD names/codes in lsoa21 table, filter here; if not, skip
            $rows->where('g.LSOA21NM', 'like', "%$lad%"); // placeholder: often LSOA name contains LA name
        }
        if ($decile !== null && $decile !== '') {
            $rows->where('imd_dec.Value', '=', (int)$decile);
        }
        if ($ruc !== null && $ruc !== '') {
            $rows->where(function ($w) use ($ruc) {
                $w->where('g.RUC21CD', '=', $ruc)
                  ->orWhere('g.RUC21NM', 'like', "%$ruc%");
            });
        }

        // Sorting (default: most deprived first)
        $sort = $req->input('sort', 'imd_decile');    // imd_decile|imd_rank|lsoa_name
        $dir  = $req->input('dir',  'asc');           // asc|desc

        $allowed = ['imd_decile','imd_rank','lsoa_name'];
        if (!in_array($sort, $allowed, true)) $sort = 'imd_decile';
        if (!in_array($dir,  ['asc','desc'], true)) $dir = 'asc';

        $rows->orderBy($sort, $dir)->orderBy('lsoa_name');

        $data = $rows->simplePaginate($perPage)->appends($req->query());

        return view('deprivation.index', [
            'data'   => $data,
            'q'      => $q,
            'decile' => $decile,
            'ruc'    => $ruc,
            'lad'    => $lad,
            'sort'   => $sort,
            'dir'    => $dir,
            'perPage'=> $perPage,
            'totalRank' => $totalRank,
        ]);
    }

    public function show(string $lsoa21cd)
    {
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
}
