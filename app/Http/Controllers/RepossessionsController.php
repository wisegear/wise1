<?php
// app/Http/Controllers/RepossessionsController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\RepoLaQuarterly as Repo;
use Illuminate\Support\Str;

class RepossessionsController extends Controller
{
    /**
     * /repossessions index
     *
     * Shows grouped repossession counts with filters and a period toggle.
     * - period: 'quarterly' (default) or 'yearly'
     * - by:      'type' (default) or 'action'
     * - filters: year, quarter (for quarterly), year_from/year_to (for yearly),
     *            county (county_ua), region, type, action
     */
public function index(Request $request)
{
    // Charts + data view (no searchable dashboard)
    // Serve precomputed data via cache (warmed by your warmer)

    $cacheKey = 'repos:index:v1';
    $ttl = now()->addDays(45);

    $payload = Cache::remember($cacheKey, $ttl, function () {
        // Reduce memory usage in long runs
        DB::connection()->disableQueryLog();

        // Full available range
        $minYear = (int) (Repo::query()->min('year') ?? 0);
        $maxYear = (int) (Repo::query()->max('year') ?? 0);

        // 1) Total repossessions per year (full range)
        $yearlyTotals = Repo::query()
            ->selectRaw('year, SUM(value) AS total')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $yearLabels = $yearlyTotals->pluck('year')->values();
        $yearTotalValues = $yearlyTotals->pluck('total')->map(fn ($v) => (int) $v)->values();

        // 2) Possession type per year (fixed categories)
        $typeOrder = [
            'Accelerated_Landlord',
            'Mortgage',
            'Private_Landlord',
            'Social_Landlord',
        ];

        $typeRows = Repo::query()
            ->selectRaw('year, possession_type, SUM(value) AS total')
            ->whereIn('possession_type', $typeOrder)
            ->groupBy('year', 'possession_type')
            ->orderBy('year')
            ->get();

        // Build series arrays aligned to year labels
        $typeSeries = [];
        foreach ($typeOrder as $t) {
            $typeSeries[$t] = array_fill(0, $yearLabels->count(), 0);
        }

        $yearIndex = $yearLabels->flip(); // year => index
        foreach ($typeRows as $r) {
            $y = (int) $r->year;
            $t = (string) $r->possession_type;
            if (!$yearIndex->has($y) || !isset($typeSeries[$t])) {
                continue;
            }
            $typeSeries[$t][$yearIndex[$y]] = (int) $r->total;
        }

        // 3) Possession action per year (many categories)
        // Keep readable: top actions overall + "Other"
        $topN = 8;

        $actionTotals = Repo::query()
            ->selectRaw('possession_action, SUM(value) AS total')
            ->whereNotNull('possession_action')
            ->whereRaw("TRIM(possession_action) <> ''")
            ->groupBy('possession_action')
            ->orderByDesc('total')
            ->get();

        $topActions = $actionTotals->take($topN)->pluck('possession_action')
            ->map(fn ($s) => (string) $s)
            ->values()
            ->all();

        $actionRows = Repo::query()
            ->selectRaw('year, possession_action, SUM(value) AS total')
            ->whereNotNull('possession_action')
            ->whereRaw("TRIM(possession_action) <> ''")
            ->groupBy('year', 'possession_action')
            ->orderBy('year')
            ->get();

        $actionSeries = [];
        foreach ($topActions as $a) {
            $actionSeries[$a] = array_fill(0, $yearLabels->count(), 0);
        }
        $actionSeries['Other'] = array_fill(0, $yearLabels->count(), 0);

        foreach ($actionRows as $r) {
            $y = (int) $r->year;
            if (!$yearIndex->has($y)) {
                continue;
            }
            $idx = $yearIndex[$y];
            $a = (string) $r->possession_action;
            $val = (int) $r->total;

            if (in_array($a, $topActions, true)) {
                $actionSeries[$a][$idx] = $val;
            } else {
                $actionSeries['Other'][$idx] += $val;
            }
        }

        // 4) Top 20 Local Authorities by actions
        $topLocalAuthorities = Repo::query()
            ->selectRaw("TRIM(local_authority) AS local_authority, SUM(value) AS total")
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->groupByRaw('TRIM(local_authority)')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $topLaNames = $topLocalAuthorities->pluck('local_authority')->values()->all();

        // Breakdown by who raised the actions (possession_type)
        $laTypeRows = Repo::query()
            ->selectRaw("TRIM(local_authority) AS local_authority, possession_type, SUM(value) AS total")
            ->whereIn('possession_type', $typeOrder)
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->whereIn(DB::raw('TRIM(local_authority)'), $topLaNames)
            ->groupByRaw('TRIM(local_authority), possession_type')
            ->get();

        // Pivot into table-friendly rows
        $laBreakdown = [];
        foreach ($topLocalAuthorities as $row) {
            $name = (string) $row->local_authority;
            $laBreakdown[$name] = [
                'local_authority' => $name,
                'total' => (int) $row->total,
            ];
            foreach ($typeOrder as $t) {
                $laBreakdown[$name][$t] = 0;
            }
        }

        foreach ($laTypeRows as $r) {
            $name = (string) $r->local_authority;
            $t = (string) $r->possession_type;
            if (!isset($laBreakdown[$name]) || !array_key_exists($t, $laBreakdown[$name])) {
                continue;
            }
            $laBreakdown[$name][$t] = (int) $r->total;
        }

        // Preserve Top‑20 ordering
        $laBreakdownRows = collect($topLaNames)
            ->map(fn ($n) => $laBreakdown[$n] ?? null)
            ->filter()
            ->values();

        // 5) Bottom 20 Local Authorities by actions (least actions)
        $bottomLocalAuthorities = Repo::query()
            ->selectRaw("TRIM(local_authority) AS local_authority, SUM(value) AS total")
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->groupByRaw('TRIM(local_authority)')
            ->orderBy('total')
            ->limit(20)
            ->get();

        $bottomLaNames = $bottomLocalAuthorities->pluck('local_authority')->values()->all();

        $bottomLaTypeRows = Repo::query()
            ->selectRaw("TRIM(local_authority) AS local_authority, possession_type, SUM(value) AS total")
            ->whereIn('possession_type', $typeOrder)
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->whereIn(DB::raw('TRIM(local_authority)'), $bottomLaNames)
            ->groupByRaw('TRIM(local_authority), possession_type')
            ->get();

        // Pivot into table-friendly rows (least actions)
        $bottomLaBreakdown = [];
        foreach ($bottomLocalAuthorities as $row) {
            $name = (string) $row->local_authority;
            $bottomLaBreakdown[$name] = [
                'local_authority' => $name,
                'total' => (int) $row->total,
            ];
            foreach ($typeOrder as $t) {
                $bottomLaBreakdown[$name][$t] = 0;
            }
        }

        foreach ($bottomLaTypeRows as $r) {
            $name = (string) $r->local_authority;
            $t = (string) $r->possession_type;
            if (!isset($bottomLaBreakdown[$name]) || !array_key_exists($t, $bottomLaBreakdown[$name])) {
                continue;
            }
            $bottomLaBreakdown[$name][$t] = (int) $r->total;
        }

        $laBreakdownLeastRows = collect($bottomLaNames)
            ->map(fn ($n) => $bottomLaBreakdown[$n] ?? null)
            ->filter()
            ->values();

        // Latest / YoY for header chips
        $latest = $yearlyTotals->last();
        $previous = $yearlyTotals->count() > 1 ? $yearlyTotals[$yearlyTotals->count() - 2] : null;

        $yoy = null;
        if ($latest && $previous) {
            $yoy = (int) $latest->total - (int) $previous->total;
        }

        return [
            'meta' => [
                'min_year' => $minYear,
                'max_year' => $maxYear,
            ],

            'latest'   => $latest,
            'previous' => $previous,
            'yoy'      => $yoy,

            'year_labels'   => $yearLabels,
            'year_totals'   => $yearTotalValues,
            'type_series'   => $typeSeries,
            'action_series' => $actionSeries,

            'la_breakdown_rows'        => $laBreakdownRows,
            'la_breakdown_least_rows'  => $laBreakdownLeastRows,
        ];
    });

    return view('repossessions.index', $payload);
}

    public function localAuthority(string $slug)
    {
        // Resolve slug back to authority name
        $authority = DB::table('repo_la_quarterlies')
            ->selectRaw('DISTINCT TRIM(local_authority) AS local_authority')
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->get()
            ->first(fn ($r) => Str::slug($r->local_authority) === $slug);

        abort_if(! $authority, 404);

        $name = $authority->local_authority;

        // Yearly totals
        $yearly = DB::table('repo_la_quarterlies')
            ->selectRaw('year, SUM(value) AS total')
            ->whereRaw('TRIM(local_authority) = ?', [$name])
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        // By possession type
        $byType = DB::table('repo_la_quarterlies')
            ->selectRaw('year, possession_type, SUM(value) AS total')
            ->whereRaw('TRIM(local_authority) = ?', [$name])
            ->groupBy('year', 'possession_type')
            ->orderBy('year')
            ->get();

        // By possession action
        $byAction = DB::table('repo_la_quarterlies')
            ->selectRaw('year, possession_action, SUM(value) AS total')
            ->whereRaw('TRIM(local_authority) = ?', [$name])
            ->groupBy('year', 'possession_action')
            ->orderBy('year')
            ->get();

        return view('repossessions.local-authority', [
            'local_authority' => $name,
            'yearly'          => $yearly,
            'byType'          => $byType,
            'byAction'        => $byAction,
        ]);
    }

}
