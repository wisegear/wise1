<?php

namespace App\Http\Controllers;

use App\Models\MortgageApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MortgageApprovalController extends Controller
{
    public function home()
    {
        // Series we care about (include Other secured as a component)
        $series = ['LPMVTVX', 'LPMB4B3', 'LPMB4B4', 'LPMB3C8']; // House purchase, Remortgaging, Other secured, Total

        // Fetch all data for those series ordered by period ascending
        $all = MortgageApproval::query()
            ->whereIn('series_code', $series)
            ->orderBy('period')
            ->get()
            ->groupBy('series_code');

        // Build chart-ready arrays, latest points & deltas
        $seriesData = [];
        foreach ($series as $code) {
            /** @var \Illuminate\Support\Collection $c */
            $c = $all->get($code, collect());
            $labels = $c->pluck('period')->map(function ($d) {
                try {
                    return Carbon::parse($d)->format('Y-m');
                } catch (\Throwable $e) {
                    return (string) $d;
                }
            })->values();
            $values = $c->pluck('value')->map(fn ($v) => (int) $v)->values();

            $latest = $c->last();
            $prev   = $c->count() >= 2 ? $c[$c->count() - 2] : null;
            $delta  = ($latest && $prev) ? ((int) $latest->value - (int) $prev->value) : null;

            $seriesData[$code] = [
                'labels' => $labels,
                'values' => $values,
                'latest' => $latest,
                'prev'   => $prev,
                'delta'  => $delta,
            ];
        }

        // Latest period across all series
        $latestPeriod = MortgageApproval::max('period');

        // Last 24 months table (period rows x series columns)
        $periods = MortgageApproval::query()
            ->select('period')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(24)
            ->pluck('period')
            ->sort() // ascending for consistent table build; we'll display descending in Blade
            ->values();

        $table = [];
        if ($periods->count()) {
            // Group fetched rows by a STRING key (YYYY-MM-DD) to avoid object keys
            $rows = MortgageApproval::query()
                ->whereIn('period', $periods)
                ->whereIn('series_code', $series)
                ->get()
                ->groupBy(function ($row) {
                    try {
                        return \Illuminate\Support\Carbon::parse($row->period)->toDateString(); // 'YYYY-MM-DD'
                    } catch (\Throwable $e) {
                        return (string) $row->period;
                    }
                });

            foreach ($periods as $p) {
                // Build the same string key for lookup
                try {
                    $key = \Illuminate\Support\Carbon::parse($p)->toDateString();
                } catch (\Throwable $e) {
                    $key = (string) $p;
                }

                $bySeries = ($rows->get($key, collect()))->keyBy('series_code');

                $table[] = [
                    'period'   => $p,
                    'LPMVTVX'  => optional($bySeries->get('LPMVTVX'))->value,
                    'LPMB4B3'  => optional($bySeries->get('LPMB4B3'))->value,
                    'LPMB4B4'  => optional($bySeries->get('LPMB4B4'))->value,
                    'LPMB3C8'  => optional($bySeries->get('LPMB3C8'))->value,
                ];
            }
        }

        // Build yearly totals (all years) for the selected series
        $years = [];
        foreach ($series as $code) {
            /** @var \Illuminate\Support\Collection $c */
            $c = $all->get($code, collect());
            foreach ($c as $row) {
                try {
                    $yr = Carbon::parse($row->period)->format('Y');
                } catch (\Throwable $e) {
                    $yr = (string) $row->period; // fallback (should still group correctly)
                }
                if (!isset($years[$yr])) {
                    // Start the year row without forcing zeros so missing series display as '—'
                    $years[$yr] = [
                        'year' => (string) $yr,
                    ];
                }
                // Sum per year per series (only set when data exists)
                $years[$yr][$code] = (int) (($years[$yr][$code] ?? 0) + (int) ($row->value ?? 0));
            }
        }
        // If official Total (LPMB3C8) is missing for a year, compute it from available components
        foreach ($years as $yrKey => $row) {
            $hasOfficialTotal = array_key_exists('LPMB3C8', $row) && $row['LPMB3C8'] !== null;
            if (!$hasOfficialTotal) {
                $sum = 0; $hasComponent = false;
                foreach (['LPMVTVX', 'LPMB4B3', 'LPMB4B4'] as $codePart) {
                    if (array_key_exists($codePart, $row) && $row[$codePart] !== null) {
                        $sum += (int) $row[$codePart];
                        $hasComponent = true;
                    }
                }
                if ($hasComponent) {
                    $years[$yrKey]['LPMB3C8'] = $sum; // backfill Total approvals with computed sum
                }
            }
        }
        // Sort by year desc and reindex
        $yearTable = collect($years)
            ->sortByDesc(function ($r) { return (int) $r['year']; })
            ->values()
            ->all();

        return view('mortgages.home', compact('seriesData', 'latestPeriod', 'table', 'yearTable'));
    }
}
