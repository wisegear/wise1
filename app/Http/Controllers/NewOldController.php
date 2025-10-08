<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NewOldController extends Controller
{
public function index(Request $request)
{
    // Toggles / params
    $includeAggregates = (bool) $request->boolean('include_aggregates', false);
    $yearParam = $request->input('year'); // optional YYYY

    // Available years (for dropdown) from HPI table
    $availableYears = DB::table('hpi_monthly')
        ->selectRaw('DISTINCT YEAR(`Date`) as year')
        ->orderBy('year', 'desc')
        ->limit(20)
        ->pluck('year')
        ->map(fn($y) => (string) $y)
        ->toArray();

    // Choose snapshot year (latest if none provided)
    $latestYear = $availableYears[0] ?? null;
    $snapshotYear = $yearParam ?: $latestYear;

    // Base query for the snapshot YEAR (HPI)
    $base = DB::table('hpi_monthly')
        ->whereRaw('YEAR(`Date`) = ?', [$snapshotYear]);

    if (!$includeAggregates) {
        // Exclude K* aggregate rows (UK/GB/England roll-ups)
        $base->whereRaw("LEFT(`AreaCode`, 1) <> 'K'");
    }

    // Country-level aggregates for the snapshot YEAR (derive from AreaCode prefix)
    $countryRows = (clone $base)
        ->selectRaw(
            "CASE LEFT(`AreaCode`, 1)
                WHEN 'E' THEN 'England'
                WHEN 'W' THEN 'Wales'
                WHEN 'S' THEN 'Scotland'
                WHEN 'N' THEN 'Northern Ireland'
                WHEN 'K' THEN 'Aggregate'
                ELSE 'Other'
            END as country_name",
            )
        ->selectRaw('COALESCE(SUM(`NewSalesVolume`),0) as new_vol')
        ->selectRaw('COALESCE(SUM(`OldSalesVolume`),0) as old_vol')
        ->groupBy('country_name')
        ->orderBy('country_name')
        ->get();

    $totNew = (int) $countryRows->sum('new_vol');
    $totOld = (int) $countryRows->sum('old_vol');

    $countries = $countryRows->map(function ($r) {
        $new = (int) $r->new_vol;
        $old = (int) $r->old_vol;
        $total = $new + $old;
        return [
            'country' => $r->country_name,
            'new_vol' => $new,
            'old_vol' => $old,
            'new_share_pct' => $total > 0 ? round(100 * $new / $total, 2) : 0,
            'old_share_pct' => $total > 0 ? round(100 * $old / $total, 2) : 0,
        ];
    })->values()->all();

    $sort = $request->input('sort', 'new_share_pct');
    $direction = $request->input('direction', 'desc');

    $allowedSorts = ['region_name','new_vol','old_vol','total_vol','new_share_pct'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'new_share_pct';
    }
    $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

    // Top 20 areas by **annual** total volume for the snapshot YEAR
    $regions = (clone $base)
        ->whereNotIn('AreaCode', ['E92000001','S92000003','W92000004','N92000002'])
        ->select('AreaCode as area_code', 'RegionName as region_name')
        ->selectRaw('COALESCE(SUM(`NewSalesVolume`),0) as new_vol')
        ->selectRaw('COALESCE(SUM(`OldSalesVolume`),0) as old_vol')
        ->selectRaw('(COALESCE(SUM(`NewSalesVolume`),0) + COALESCE(SUM(`OldSalesVolume`),0)) as total_vol')
        ->selectRaw('CASE WHEN (COALESCE(SUM(`NewSalesVolume`),0) + COALESCE(SUM(`OldSalesVolume`),0)) = 0 THEN 0 ELSE ROUND(100 * COALESCE(SUM(`NewSalesVolume`),0) / (COALESCE(SUM(`NewSalesVolume`),0) + COALESCE(SUM(`OldSalesVolume`),0)), 1) END as new_share_pct')
        ->groupBy('AreaCode', 'RegionName')
        ->orderBy($sort, $direction)
        ->paginate(20)
        ->withQueryString();

    // Trend over last 15 years: UK-level from HPI, grouped YEARLY
    $trendRows = DB::table('hpi_monthly')
        ->when(!$includeAggregates, fn($q) => $q->whereRaw("LEFT(`AreaCode`, 1) <> 'K'"))
        ->selectRaw('YEAR(`Date`) as year')
        ->selectRaw('SUM(`NewSalesVolume`) as new_vol')
        ->selectRaw('SUM(`OldSalesVolume`) as old_vol')
        ->groupBy(DB::raw('YEAR(`Date`)'))
        ->orderBy(DB::raw('YEAR(`Date`)'), 'desc')
        ->limit(15)
        ->get()
        ->reverse()
        ->values();

    $trend = $trendRows->map(fn($r) => [
        'date'    => (string) $r->year, // x-axis label (year)
        'new_vol' => (int) $r->new_vol,
        'old_vol' => (int) $r->old_vol,
    ])->all();

    // Pass everything to the view (yearly)
    return view('new_old.index', [
        'snapshot_year'      => $snapshotYear,
        'available_years'    => $availableYears,
        'include_aggregates' => $includeAggregates,
        'countries'          => $countries,
        'regions'            => $regions,
        'trend'              => $trend,
    ]);
}
}
