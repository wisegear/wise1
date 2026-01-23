<?php

namespace App\Http\Controllers;

use App\Models\HpiMonthly;
use Illuminate\Support\Facades\DB;

class HpiDashboardController extends Controller
{
    public function index()
    {
        // Latest global date (useful for UK rollup)
        $latestGlobal = HpiMonthly::latestDate();

        // UK series (for charting)
        $ukSeries = HpiMonthly::ukSeries();

        // Nations (each at their own latest date)
        $nations = HpiMonthly::latestNations();

        // Property type split (average prices) for UK + nations (for 5 charts)
        $typePriceSeries = HpiMonthly::typePriceSeriesByArea();

        // Build time series for UK + each nation (12m%Change over time)
        $areas = [
            'United Kingdom'   => 'K02000001',
            'England'          => 'E92000001',
            'Scotland'         => 'S92000003',
            'Wales'            => 'W92000004',
            'Northern Ireland' => 'N92000002',
        ];

        $seriesByArea = [];
        foreach ($areas as $name => $code) {
            $rows = HpiMonthly::query()
                ->select([
                    'Date',
                    \Illuminate\Support\Facades\DB::raw('`12m%Change` as twelve_m_change')
                ])
                ->where('AreaCode', $code)
                ->orderBy('Date')
                ->get();

            $seriesByArea[] = [
                'name' => $name,
                'code' => $code,
                'dates' => $rows->pluck('Date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m'))->all(),
                'twelve_m_change' => $rows->pluck('twelve_m_change')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
            ];
        }

        // Movers – top 15 areas by 12m%Change at their latest date
        $latest = HpiMonthly::latestDate(); // global max date
        $movers = HpiMonthly::query()
            ->select('RegionName','AreaCode','AveragePrice','12m%Change')
            ->where('Date', $latest)
            ->whereNotIn('AreaCode', array_values(HpiMonthly::nationCodes()))
            ->orderByDesc(DB::raw('`12m%Change`'))
            ->limit(30)
            ->get();

        // Losers – bottom 15 areas by 12m%Change at the latest global date
        $losers = HpiMonthly::query()
            ->select('RegionName','AreaCode','AveragePrice','12m%Change')
            ->where('Date', $latest)
            ->whereNotIn('AreaCode', array_values(HpiMonthly::nationCodes()))
            ->orderBy(DB::raw('`12m%Change`'))
            ->limit(30)
            ->get();

        return view('hpi.dashboard', [
            'latestGlobal' => $latestGlobal,
            'ukSeries'     => $ukSeries,
            'nations'      => $nations,
            'movers'       => $movers,
            'seriesByArea' => $seriesByArea,
            'losers'       => $losers,
            'typePriceSeries' => $typePriceSeries,
        ]);
    }

    public function overview()
    {
        // Lightweight national HPI overview for Economic Indicators
        // Fetch UK monthly series (AreaCode K02000001)
        $ukRows = HpiMonthly::query()
            ->select([
                'Date',
                'AveragePrice',
                DB::raw('`12m%Change` as twelve_m_change'),
            ])
            ->where('AreaCode', 'K02000001') // United Kingdom
            ->orderBy('Date')
            ->get();

        if ($ukRows->isEmpty()) {
            return view('hpi.overview', [
                'latest' => null,
                'previous' => null,
                'labels' => [],
                'prices' => [],
                'changes' => [],
            ]);
        }

        // Latest and previous rows
        $latest = $ukRows->last();
        $previous = $ukRows->count() > 1 ? $ukRows[$ukRows->count() - 2] : null;

        // Build simple time series for charting
        $labels = $ukRows->map(function ($row) {
            try {
                return \Carbon\Carbon::parse($row->Date)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $row->Date;
            }
        })->values();

        $prices = $ukRows->map(function ($row) {
            return (float) ($row->AveragePrice ?? 0);
        })->values();

        $changes = $ukRows->map(function ($row) {
            return is_null($row->twelve_m_change) ? null : (float) $row->twelve_m_change;
        })->values();

        return view('hpi.overview', [
            'latest' => $latest,
            'previous' => $previous,
            'labels' => $labels,
            'prices' => $prices,
            'changes' => $changes,
        ]);
    }
}
