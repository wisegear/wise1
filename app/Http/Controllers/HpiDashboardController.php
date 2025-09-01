<?php

namespace App\Http\Controllers;

use App\Models\HpiMonthly;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HpiDashboardController extends Controller
{
    public function index()
    {
        // Cache for 45 days (data is monthly)
        $ttl = now()->addDays(45);

        // Latest global date (useful for UK rollup)
        $latestGlobal = Cache::remember('hpi:latest_date', $ttl, function () {
            return HpiMonthly::latestDate();
        });

        // UK series (for charting)
        $ukSeries = Cache::remember('hpi:uk:series', $ttl, function () {
            return HpiMonthly::ukSeries();
        });

        // Nations (each at their own latest date)
        $nations = Cache::remember('hpi:nations', $ttl, function () {
            return HpiMonthly::latestNations();
        });

        // Property type split (average prices) for UK + nations (for 5 charts)
        $typePriceSeries = Cache::remember('hpi:type_price_series', $ttl, function () {
            return HpiMonthly::typePriceSeriesByArea();
        });

        // Build time series for UK + each nation (12m%Change over time)
        $areas = [
            'United Kingdom'   => 'K02000001',
            'England'          => 'E92000001',
            'Scotland'         => 'S92000003',
            'Wales'            => 'W92000004',
            'Northern Ireland' => 'N92000002',
        ];

        $seriesByArea = Cache::remember('hpi:series_by_area', $ttl, function () use ($areas) {
            $out = [];
            foreach ($areas as $name => $code) {
                $rows = HpiMonthly::query()
                    ->select([
                        'Date',
                        \Illuminate\Support\Facades\DB::raw('`12m%Change` as twelve_m_change')
                    ])
                    ->where('AreaCode', $code)
                    ->orderBy('Date')
                    ->get();

                $out[] = [
                    'name' => $name,
                    'code' => $code,
                    'dates' => $rows->pluck('Date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m'))->all(),
                    'twelve_m_change' => $rows->pluck('twelve_m_change')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
                ];
            }
            return $out;
        });

        // Movers – top 15 areas by 12m%Change at their latest date
        $movers = Cache::remember('hpi:movers', $ttl, function () {
            $latest = HpiMonthly::latestDate(); // global max date
            return HpiMonthly::query()
                ->select('RegionName','AreaCode','AveragePrice','12m%Change')
                ->where('Date', $latest)
                ->whereNotIn('AreaCode', array_values(HpiMonthly::nationCodes()))
                ->orderByDesc(DB::raw('`12m%Change`'))
                ->limit(20)
                ->get();
        });

        // Losers – bottom 15 areas by 12m%Change at the latest global date
        $losers = Cache::remember('hpi:losers', $ttl, function () {
            $latest = HpiMonthly::latestDate(); // global max date
            return HpiMonthly::query()
                ->select('RegionName','AreaCode','AveragePrice','12m%Change')
                ->where('Date', $latest)
                ->whereNotIn('AreaCode', array_values(HpiMonthly::nationCodes()))
                ->orderBy(DB::raw('`12m%Change`'))
                ->limit(15)
                ->get();
        });

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
}
