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

    // Available years (for dropdown)
    $availableYears = DB::table('new_old_prices')
        ->selectRaw('DISTINCT YEAR(date) as year')
        ->orderBy('year', 'desc')
        ->limit(20)
        ->pluck('year')
        ->map(fn($y) => (string) $y)
        ->toArray();

    // Choose snapshot year (latest if none provided)
    $latestYear = $availableYears[0] ?? null;
    $snapshotYear = $yearParam ?: $latestYear;

    // Base query for the snapshot YEAR
    $base = DB::table('new_old_prices')
        ->whereRaw('YEAR(date) = ?', [$snapshotYear]);

    if (!$includeAggregates) {
        $base->where('is_aggregate', 0);
    }

    // Country-level aggregates for the snapshot YEAR
    $countryRows = (clone $base)
        ->select(
            'country_name',
            DB::raw('SUM(new_build_sales_volume) as new_vol'),
            DB::raw('SUM(existing_property_sales_volume) as old_vol')
        )
        ->groupBy('country_name')
        ->orderBy('country_name')
        ->get();

    $totNew = (int) $countryRows->sum('new_vol');
    $totOld = (int) $countryRows->sum('old_vol');

    $countries = $countryRows->map(function ($r) use ($totNew, $totOld) {
        return [
            'country' => $r->country_name,
            'new_vol' => (int) $r->new_vol,
            'old_vol' => (int) $r->old_vol,
            'new_share_pct' => $totNew ? round(100 * $r->new_vol / $totNew, 2) : null,
            'old_share_pct' => $totOld ? round(100 * $r->old_vol / $totOld, 2) : null,
        ];
    })->values()->all();

    // Top 20 areas by **annual** total volume for the snapshot YEAR
    $regionRows = (clone $base)
        ->select(
            'area_code',
            'region_name',
            DB::raw('COALESCE(SUM(new_build_sales_volume),0) as new_vol'),
            DB::raw('COALESCE(SUM(existing_property_sales_volume),0) as old_vol'),
            DB::raw('(COALESCE(SUM(new_build_sales_volume),0) + COALESCE(SUM(existing_property_sales_volume),0)) as total_vol')
        )
        ->groupBy('area_code', 'region_name')
        ->orderByDesc('total_vol')
        ->limit(20)
        ->get();

    $regions = $regionRows->map(function ($r) {
        $total = (int) $r->total_vol;
        $new = (int) $r->new_vol;
        return [
            'area_code'   => $r->area_code,
            'region_name' => $r->region_name,
            'new_vol'     => $new,
            'old_vol'     => (int) $r->old_vol,
            'total_vol'   => $total,
            'new_share_pct' => $total ? round(100 * $new / $total, 1) : null,
        ];
    })->values()->all();

    // Trend over last 15 years: UK-level, grouped YEARLY
    $trendRows = DB::table('new_old_prices')
        ->when(!$includeAggregates, fn($q) => $q->where('is_aggregate', 0))
        ->select(
            DB::raw('YEAR(date) as year'),
            DB::raw('SUM(new_build_sales_volume) as new_vol'),
            DB::raw('SUM(existing_property_sales_volume) as old_vol')
        )
        ->groupBy(DB::raw('YEAR(date)'))
        ->orderBy(DB::raw('YEAR(date)'), 'desc')
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
