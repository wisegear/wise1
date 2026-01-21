<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EconomicDashboardController extends Controller
{
    public function index()
    {
        $ttl = now()->addHours(6);

        // Helper to extract a numeric metric from a DB row when column names vary
        $getMetric = function ($row, array $extraExclude = []) {
            if (! $row) {
                return null;
            }
            $vars = get_object_vars($row);
            $exclude = array_merge(
                ['id', 'date', 'period', 'year', 'quarter', 'created_at', 'updated_at'],
                $extraExclude
            );
            foreach ($vars as $key => $val) {
                if (in_array($key, $exclude, true)) {
                    continue;
                }
                if (is_numeric($val)) {
                    return (float) $val;
                }
            }
            return null;
        };

        // 1. Interest Rates (latest)
        $interest = Cache::remember('eco:last_interest', $ttl, function () {
            return DB::table('interest_rates')
                ->orderBy('effective_date', 'desc')
                ->first();
        });

        // 2. Inflation (latest CPIH)
        $inflation = Cache::remember('eco:last_inflation', $ttl, function () {
            return DB::table('inflation_cpih_monthly')
                ->orderBy('date', 'desc')
                ->first();
        });

        // 3. Wage Growth (latest)
        $wages = Cache::remember('eco:last_wages', $ttl, function () {
            return DB::table('wage_growth_monthly')
                ->orderBy('date', 'desc')
                ->first();
        });

        // 4. Unemployment (latest)
        $unemp = Cache::remember('eco:last_unemployment', $ttl, function () {
            return DB::table('unemployment_monthly')
                ->orderBy('date', 'desc')
                ->first();
        });

        // 5. Mortgage Approvals (latest)
        $approvals = Cache::remember('eco:last_approvals', $ttl, function () {
            return DB::table('mortgage_approvals')
                ->orderBy('period', 'desc')
                ->first();
        });

        // 6. Repossessions (latest) – use MLAR possessions series directly
        $reposs = Cache::remember('eco:last_reposs_v2', $ttl, function () {
            $latest = DB::table('mlar_arrears')
                ->where('description', 'In possession')
                ->orderBy('year', 'desc')
                ->orderByRaw("FIELD(quarter, 'Q4','Q3','Q2','Q1')")
                ->first();

            if (! $latest) {
                return null;
            }

            return (object) [
                'year'    => $latest->year,
                'quarter' => $latest->quarter,
                // MLAR value is already "% of loans" in the possessions series
                'total'   => (float) $latest->value,
            ];
        });

        // 7. House Price Index (UK only)
        $hpi = Cache::remember('eco:last_hpi', $ttl, function () {
            return DB::table('hpi_monthly')
                ->where('AreaCode', 'K02000001')
                ->orderBy('Date', 'desc')
                ->first();
        });

        // Build small sparkline series for each indicator (no caching for now)
        $sparklines = [];

        // Helper: build monthly sparkline (values + labels) from a dated collection
        $makeMonthlySeries = function ($collection, string $dateField, callable $valueCallback) {
            $values = [];
            $labels = [];

            foreach ($collection as $row) {
                if (empty($row->{$dateField})) {
                    continue;
                }
                try {
                    $date = \Carbon\Carbon::parse($row->{$dateField});
                } catch (\Throwable $e) {
                    continue;
                }
                $values[] = (float) $valueCallback($row);
                $labels[] = $date->format('M Y');
            }

            return [
                'values' => $values,
                'labels' => $labels,
            ];
        };

        // Interest Rates: last 24 changes
        $interestSeries = DB::table('interest_rates')
            ->orderBy('effective_date', 'desc')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();
        $sparklines['interest'] = $makeMonthlySeries(
            $interestSeries,
            'effective_date',
            fn ($r) => $r->rate
        );

        // Inflation: last 24 months
        $inflationSeries = DB::table('inflation_cpih_monthly')
            ->orderBy('date', 'desc')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();
        $sparklines['inflation'] = $makeMonthlySeries(
            $inflationSeries,
            'date',
            function ($row) use ($getMetric) {
                return $getMetric($row) ?? 0;
            }
        );

        // Wage Growth: last 24 months
        $wageSeries = DB::table('wage_growth_monthly')
            ->orderBy('date', 'desc')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();
        $sparklines['wages'] = $makeMonthlySeries(
            $wageSeries,
            'date',
            function ($row) {
                if (isset($row->three_month_avg_yoy)) {
                    return $row->three_month_avg_yoy;
                }
                if (isset($row->single_month_yoy)) {
                    return $row->single_month_yoy;
                }
                return 0;
            }
        );

        // Unemployment: last 24 months
        $unempSeries = DB::table('unemployment_monthly')
            ->orderBy('date', 'desc')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();
        $sparklines['unemployment'] = $makeMonthlySeries(
            $unempSeries,
            'date',
            function ($row) use ($getMetric) {
                return $getMetric($row) ?? 0;
            }
        );

        // Mortgage Approvals: monthly series
        $approvalsRaw = DB::table('mortgage_approvals')
            ->orderBy('period', 'desc')
            ->limit(36)
            ->get()
            ->reverse()
            ->values();
        $sparklines['approvals'] = $makeMonthlySeries(
            $approvalsRaw,
            'period',
            fn ($row) => $row->value
        );

        // Repossessions: last 16 quarters of MLAR possessions series
        $repossSeries = DB::table('mlar_arrears')
            ->where('description', 'In possession')
            ->orderBy('year', 'desc')
            ->orderByRaw("FIELD(quarter, 'Q4','Q3','Q2','Q1')")
            ->limit(16)
            ->get()
            ->reverse()
            ->values();
        $sparklines['repossessions'] = [
            'values' => $repossSeries->map(fn ($r) => (float)$r->value)->values()->all(),
            'labels' => $repossSeries->map(fn ($r) => $r->year.' '.$r->quarter)->values()->all(),
        ];

        // Mortgage arrears (MLAR)
        // Sparkline: total arrears across all bands except the lowest (1.5–2.5%),
        // which mainly captures minor/one-off missed payments.
        $arrearsSeries = DB::table('mlar_arrears')
            ->select('year', 'quarter', DB::raw('SUM(value) as total'))
            ->where('band', '!=', '1.5_2.5')
            ->groupBy('year', 'quarter')
            ->orderBy('year')
            ->orderByRaw("FIELD(quarter, 'Q1','Q2','Q3','Q4')")
            ->get();

        $sparklines['arrears'] = [
            'values' => $arrearsSeries->map(fn ($r) => (float)$r->total)->values()->all(),
            'labels' => $arrearsSeries->map(fn ($r) => $r->year.' '.$r->quarter)->values()->all(),
        ];


        // HPI: last 60 months, UK only
        $hpiSeries = DB::table('hpi_monthly')
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date', 'desc')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();
        $sparklines['hpi'] = $makeMonthlySeries(
            $hpiSeries,
            'Date',
            fn ($r) => $r->AveragePrice
        );

        $arrearsPanel = null;
        $repossDirection = null;

        // ------------------------------------------------------------
        // HYBRID PROPERTY STRESS INDEX (Level + Direction)
        // ------------------------------------------------------------

        $stress = [
            'interest'  => 0,
            'inflation' => 0,
            'wages'     => 0,
            'unemp'     => 0,
            'approvals' => 0,
            'reposs'    => 0,
            'hpi'       => 0,
            'arrears'   => 0,
        ];

        // Helper for direction scoring on a per-period basis (monthly or quarterly)
        $directionScore = function (array $vals, bool $inverse = false) {
            $n = count($vals);
            if ($n < 2) {
                return 0;
            }

            $latest = (float) $vals[$n - 1];
            $prev1  = (float) $vals[$n - 2];
            $prev2  = $n >= 3 ? (float) $vals[$n - 3] : null;

            if (! $inverse) {
                // Higher is worse
                if ($latest <= $prev1) {
                    return 0;
                }
                if (!is_null($prev2) && $prev1 > $prev2) {
                    return 2;
                }
                return 1;
            }

            // Lower is worse
            if ($latest >= $prev1) {
                return 0;
            }
            if (!is_null($prev2) && $prev1 < $prev2) {
                return 2;
            }
            return 1;
        };

        // Arrears direction score (quarterly series, higher = worse)
        // 0 = green (not rising vs previous quarter)
        // 1 = amber (one worsening quarter)
        // 2 = red (two consecutive worsening quarters)
        // 3 = dark red (three consecutive worsening quarters)
        $arrearsDirectionScore = function (array $vals) {
            $n = count($vals);
            if ($n < 2) {
                return 0;
            }

            $latest = (float) $vals[$n - 1];
            $prev1  = (float) $vals[$n - 2];
            $prev2  = $n >= 3 ? (float) $vals[$n - 3] : null;
            $prev3  = $n >= 4 ? (float) $vals[$n - 4] : null;

            // If latest is not higher than previous, treat as green
            if ($latest <= $prev1) {
                return 0;
            }

            $hasTwoWorsening = $prev2 !== null && $prev1 > $prev2;
            $hasThreeWorsening = $prev3 !== null && $prev2 !== null && $prev2 > $prev3;

            if ($hasTwoWorsening && $hasThreeWorsening) {
                return 3; // dark red: three consecutive worsening quarters
            }

            if ($hasTwoWorsening) {
                return 2; // red: two consecutive worsening quarters
            }

            return 1; // amber: one worsening quarter
        };

        // 1. Interest rate level
        if ($interest && $interest->rate >= 4.5) {
            $stress['interest'] += 2;
        } elseif ($interest && $interest->rate >= 3.0) {
            $stress['interest'] += 1;
        }
        $stress['interest'] += $directionScore($sparklines['interest']['values'] ?? [], false);

        // 2. Inflation level
        $inflVal = $inflation ? (float)$getMetric($inflation) : null;
        if (!is_null($inflVal)) {
            if ($inflVal >= 4.0) $stress['inflation'] += 2;
            elseif ($inflVal >= 2.0) $stress['inflation'] += 1;
        }
        $stress['inflation'] += $directionScore($sparklines['inflation']['values'] ?? [], false);

        // 3. Real wage level + direction
        $wSeries = $sparklines['wages']['values'] ?? [];
        $iSeries = $sparklines['inflation']['values'] ?? [];
        $realSeries = [];
        if (count($wSeries) === count($iSeries)) {
            for ($x = 0; $x < count($wSeries); $x++) {
                $realSeries[] = (float)$wSeries[$x] - (float)$iSeries[$x];
            }
        }
        if (count($realSeries) > 0) {
            $latestReal = end($realSeries);
            if ($latestReal <= -1.0) $stress['wages'] += 2;
            elseif ($latestReal < 0) $stress['wages'] += 1;
        }
        $stress['wages'] += $directionScore($realSeries, true);

        // 4. Unemployment level + direction
        $uVal = $unemp ? (float)$getMetric($unemp) : null;
        if (!is_null($uVal)) {
            if ($uVal >= 2000000) $stress['unemp'] += 2;
            elseif ($uVal >= 1500000) $stress['unemp'] += 1;
        }
        $stress['unemp'] += $directionScore($sparklines['unemployment']['values'] ?? [], false);

        // 5. Mortgage approvals level + direction
        $aVal = $approvals ? (float)$approvals->value : null;
        if (!is_null($aVal)) {
            if ($aVal <= 40000) $stress['approvals'] += 2;
            elseif ($aVal <= 60000) $stress['approvals'] += 1;
        }
        $stress['approvals'] += $directionScore($sparklines['approvals']['values'] ?? [], true);

        // 6. Repossessions level + direction (MLAR possessions % of loans)
        $rVal = $reposs ? (float)$reposs->total : null;
        if (!is_null($rVal)) {
            // Thresholds now in percentage points of loans in 10%+ arrears
            if ($rVal >= 1.0) {
                $stress['reposs'] += 2; // red: 1% or more of loans in severe arrears
            } elseif ($rVal >= 0.5) {
                $stress['reposs'] += 1; // amber: 0.5%–0.99%
            }
        }
        $stress['reposs'] += $directionScore($sparklines['repossessions']['values'] ?? [], false);

        // 7. HPI level + direction
        $hVal = $hpi ? (float)$hpi->AveragePrice : null;
        if (!is_null($hVal)) {
            $series = $sparklines['hpi']['values'];
            $n = count($series);
            if ($n >= 2) {
                $change = $series[$n-1] - $series[$n-2];
                if ($change <= -5000) $stress['hpi'] += 2;
                elseif ($change < 0) $stress['hpi'] += 1;
            }
        }
        $stress['hpi'] += $directionScore($sparklines['hpi']['values'] ?? [], true);

        // 8. Arrears direction only (total arrears 2.5%+ of balance)
        // We treat higher arrears as worse; scoring uses the custom arrearsDirectionScore
        // with 0 (green) to 3 (dark red) based on consecutive worsening quarters.
        if (!empty($sparklines['arrears']['values'] ?? [])) {
            $stress['arrears'] += $arrearsDirectionScore($sparklines['arrears']['values']);
        }

        // Repossessions direction score (reuse arrearsDirectionScore: 0–3)
        if (!empty($sparklines['repossessions']['values'] ?? [])) {
            $repossDirection = $arrearsDirectionScore($sparklines['repossessions']['values']);
        }

        // Build arrears panel
        // Both direction and headline value use the total arrears across all bands
        // excluding the lowest (1.5–2.5%) band, taken directly from the sparkline
        // so the panel value always matches the last point on the chart.
        if (!empty($sparklines['arrears']['values'] ?? [])) {
            $arrearsValues = $sparklines['arrears']['values'];
            $arrearsLabels = $sparklines['arrears']['labels'] ?? [];

            $arrearsDir = $arrearsDirectionScore($arrearsValues);

            $lastValue = (float) end($arrearsValues);
            $lastLabel = end($arrearsLabels) ?: null;

            $year = null;
            $quarter = null;
            if ($lastLabel && is_string($lastLabel)) {
                $parts = explode(' ', $lastLabel);
                if (count($parts) === 2) {
                    $year = (int) $parts[0];
                    $quarter = $parts[1];
                }
            }

            if (!is_null($year) && !is_null($quarter)) {
                $arrearsPanel = [
                    'year'      => $year,
                    'quarter'   => $quarter,
                    'value'     => $lastValue,
                    'direction' => $arrearsDir,
                ];
            }
        }

        // Total score
        $totalStress = array_sum($stress);
        Cache::put('eco:total_stress', $totalStress, $ttl);
        Cache::forever('eco:total_stress_persist', $totalStress);

        return view('economic.dashboard', [
            'interest'        => $interest,
            'inflation'       => $inflation,
            'wages'           => $wages,
            'unemp'           => $unemp,
            'approvals'       => $approvals,
            'reposs'          => $reposs,
            'repossDirection' => $repossDirection,
            'hpi'             => $hpi,
            'sparklines'      => $sparklines,
            'stress'          => $stress,
            'totalStress'     => $totalStress,
            'arrearsPanel'    => $arrearsPanel,
        ]);
    }
}
