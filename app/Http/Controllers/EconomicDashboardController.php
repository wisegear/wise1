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

        // 6. Repossessions (latest national total by year+quarter)
        $reposs = Cache::remember('eco:last_reposs_v2', $ttl, function () {
            // Get the most recent year + quarter present in the table
            $latest = DB::table('repo_la_quarterlies')
                ->select('year', 'quarter')
                ->orderBy('year', 'desc')
                ->orderBy('quarter', 'desc')
                ->first();

            if (! $latest) {
                return null;
            }

            // Sum all local-authority values for that year + quarter
            $total = DB::table('repo_la_quarterlies')
                ->where('year', $latest->year)
                ->where('quarter', $latest->quarter)
                ->sum('value');

            return (object) [
                'year'    => $latest->year,
                'quarter' => $latest->quarter,
                'total'   => $total,
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

        // Helper: build quarterly sparkline (values + labels) from a monthly/dated collection
        $makeQuarterSeries = function ($collection, string $dateField, callable $valueCallback) {
            $grouped = [];

            foreach ($collection as $row) {
                $date = \Carbon\Carbon::parse($row->{$dateField});
                $year = $date->year;
                $quarter = $date->quarter;
                $key = $year . '-Q' . $quarter;

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'sum'     => 0.0,
                        'count'   => 0,
                        'year'    => $year,
                        'quarter' => $quarter,
                    ];
                }

                $grouped[$key]['sum']   += (float) $valueCallback($row);
                $grouped[$key]['count']++;
            }

            // Sort by year then quarter
            usort($grouped, function ($a, $b) {
                if ($a['year'] === $b['year']) {
                    return $a['quarter'] <=> $b['quarter'];
                }
                return $a['year'] <=> $b['year'];
            });

            $values = [];
            $labels = [];

            foreach ($grouped as $bucket) {
                if ($bucket['count'] > 0) {
                    $values[] = $bucket['sum'] / $bucket['count'];
                    $labels[] = $bucket['year'] . ' Q' . $bucket['quarter'];
                }
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
        $sparklines['interest'] = $makeQuarterSeries(
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
        $sparklines['inflation'] = $makeQuarterSeries(
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
        $sparklines['wages'] = $makeQuarterSeries(
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
        $sparklines['unemployment'] = $makeQuarterSeries(
            $unempSeries,
            'date',
            function ($row) use ($getMetric) {
                return $getMetric($row) ?? 0;
            }
        );

        // Mortgage Approvals: last 24 periods (keep monthly for sparkline)
        $approvalsSeries = DB::table('mortgage_approvals')
            ->orderBy('period', 'desc')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();
        $sparklines['approvals'] = [
            'values' => $approvalsSeries->map(fn ($r) => (float) $r->value)->values()->all(),
            'labels' => $approvalsSeries->map(fn ($r) => \Carbon\Carbon::parse($r->period)->format('Y-m'))->values()->all(),
        ];

        // Repossessions: last 16 quarters, summed nationally
        $repossSeries = DB::table('repo_la_quarterlies')
            ->select('year', 'quarter', DB::raw('SUM(value) as total'))
            ->groupBy('year', 'quarter')
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->limit(16)
            ->get()
            ->reverse()
            ->values();
        $sparklines['repossessions'] = [
            'values' => $repossSeries->map(fn ($r) => (float)$r->total)->values()->all(),
            'labels' => $repossSeries->map(fn ($r) => $r->year.' Q'.$r->quarter)->values()->all(),
        ];

        // HPI: last 60 months, UK only
        $hpiSeries = DB::table('hpi_monthly')
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date', 'desc')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();
        $sparklines['hpi'] = $makeQuarterSeries(
            $hpiSeries,
            'Date',
            fn ($r) => $r->AveragePrice
        );

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
        ];

        // Helper for direction scoring on a QUARTERLY basis
        // For monthly series, we build simple 3-month blocks:
        //  - latestQ = average of last 3 values
        //  - prevQ   = average of previous 3
        //  - prev2Q  = average of the 3 before that (for "two bad quarters" tests)
        $quarterDirectionScore = function (array $vals, bool $inverse = false, bool $alreadyQuarterly = false) {
            // If series is already quarterly (e.g. repossessions), treat each value as one quarter
            if ($alreadyQuarterly) {
                $n = count($vals);
                if ($n < 2) {
                    return 0;
                }
                $latestQ = (float)$vals[$n - 1];
                $prevQ   = (float)$vals[$n - 2];
                $prev2Q  = $n >= 3 ? (float)$vals[$n - 3] : null;

                if (! $inverse) {
                    // Higher is worse
                    if ($latestQ <= $prevQ) {
                        return 0; // green
                    }
                    if (!is_null($prev2Q) && $prevQ > $prev2Q) {
                        return 2; // red: two worsening quarters
                    }
                    return 1; // amber: one worsening quarter
                } else {
                    // Lower is worse
                    if ($latestQ >= $prevQ) {
                        return 0; // green
                    }
                    if (!is_null($prev2Q) && $prevQ < $prev2Q) {
                        return 2; // red: two worsening quarters
                    }
                    return 1; // amber
                }
            }

            // Monthly series: need at least 6 points for two quarters
            $n = count($vals);
            if ($n < 6) {
                return 0;
            }

            // Build last 3 quarters (each quarter = average of 3 months, working from the end)
            $makeQuarter = function (int $endIndex) use ($vals) {
                // endIndex is inclusive (last index of the block)
                $start = $endIndex - 2;
                if ($start < 0) {
                    return null;
                }
                $sum = 0.0;
                $count = 0;
                for ($i = $start; $i <= $endIndex; $i++) {
                    if (!isset($vals[$i])) {
                        return null;
                    }
                    $sum += (float)$vals[$i];
                    $count++;
                }
                return $count > 0 ? $sum / $count : null;
            };

            $latestQ = $makeQuarter($n - 1);       // last 3 months
            $prevQ   = $makeQuarter($n - 4);       // 3 months before that
            $prev2Q  = $makeQuarter($n - 7);       // 3 months before that

            if ($latestQ === null || $prevQ === null) {
                return 0;
            }

            if (! $inverse) {
                // Higher is worse (inflation, unemployment, interest, etc.)
                if ($latestQ <= $prevQ) {
                    return 0; // green
                }
                if ($prev2Q !== null && $prevQ > $prev2Q) {
                    return 2; // red: two worsening quarters back-to-back
                }
                return 1; // amber: one worsening quarter
            } else {
                // Lower is worse (approvals, HPI, real wages)
                if ($latestQ >= $prevQ) {
                    return 0; // green
                }
                if ($prev2Q !== null && $prevQ < $prev2Q) {
                    return 2; // red: two worsening quarters back-to-back
                }
                return 1; // amber
            }
        };

        // 1. Interest rate level
        if ($interest && $interest->rate >= 4.5) {
            $stress['interest'] += 2;
        } elseif ($interest && $interest->rate >= 3.0) {
            $stress['interest'] += 1;
        }
        $stress['interest'] += $quarterDirectionScore($sparklines['interest']['values'] ?? [], false, true);

        // 2. Inflation level
        $inflVal = $inflation ? (float)$getMetric($inflation) : null;
        if (!is_null($inflVal)) {
            if ($inflVal >= 4.0) $stress['inflation'] += 2;
            elseif ($inflVal >= 2.0) $stress['inflation'] += 1;
        }
        $stress['inflation'] += $quarterDirectionScore($sparklines['inflation']['values'] ?? [], false, true);

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
        $stress['wages'] += $quarterDirectionScore($realSeries, true, true);

        // 4. Unemployment level + direction
        $uVal = $unemp ? (float)$getMetric($unemp) : null;
        if (!is_null($uVal)) {
            if ($uVal >= 2000000) $stress['unemp'] += 2;
            elseif ($uVal >= 1500000) $stress['unemp'] += 1;
        }
        $stress['unemp'] += $quarterDirectionScore($sparklines['unemployment']['values'] ?? [], false, true);

        // 5. Mortgage approvals level + direction
        $aVal = $approvals ? (float)$approvals->value : null;
        if (!is_null($aVal)) {
            if ($aVal <= 40000) $stress['approvals'] += 2;
            elseif ($aVal <= 60000) $stress['approvals'] += 1;
        }
        $stress['approvals'] += $quarterDirectionScore($sparklines['approvals']['values'] ?? [], true, false);

        // 6. Repossessions level + direction
        $rVal = $reposs ? (float)$reposs->total : null;
        if (!is_null($rVal)) {
            if ($rVal >= 10000) $stress['reposs'] += 2;
            elseif ($rVal >= 6000) $stress['reposs'] += 1;
        }
        $stress['reposs'] += $quarterDirectionScore($sparklines['repossessions']['values'] ?? [], false, true);

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
        $stress['hpi'] += $quarterDirectionScore($sparklines['hpi']['values'] ?? [], true, true);

        // Total score
        $totalStress = array_sum($stress);

        return view('economic.dashboard', [
            'interest'   => $interest,
            'inflation'  => $inflation,
            'wages'      => $wages,
            'unemp'      => $unemp,
            'approvals'  => $approvals,
            'reposs'     => $reposs,
            'hpi'        => $hpi,
            'sparklines' => $sparklines,
            'stress'     => $stress,
            'totalStress'=> $totalStress,
        ]);
    }
}
