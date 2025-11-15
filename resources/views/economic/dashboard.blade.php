@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    @php
        // Use hybrid stress score from controller
        $total = $totalStress ?? 0;
        // Convert to 0–100 scale (max possible is 28: 7 indicators * 4 max points)
        $scaled = max(0, min(100, round(($total / 28) * 100)));
        $stressScore = $scaled;

        if ($stressScore >= 80) {
            $stressLabel = 'High stress';
            $stressClass = 'bg-rose-50 text-rose-800 border-rose-200';
        } elseif ($stressScore >= 50) {
            $stressLabel = 'Elevated risk';
            $stressClass = 'bg-amber-50 text-amber-800 border-amber-200';
        } else {
            $stressLabel = 'Low stress';
            $stressClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }
    @endphp

    {{-- TOP-LEVEL STRESS INDEX SUMMARY --}}
    <section class="mb-10 rounded-lg border border-gray-200 bg-white/80 p-6 shadow-sm">
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900 mb-2">Property Stress Dashboard</h1>
        <p class="text-sm text-gray-700">
            Overview of key UK economic indicators that influence the housing market: borrowing costs, affordability,
            labour market strength, credit supply and distress. When several indicators move into the red at the same
            time, the property market historically weakens.
        </p>
        @if(!is_null($stressScore))
            <div class="mt-4 flex flex-wrap items-baseline gap-4">
                <div class="rounded-full border px-3 py-1 text-xs font-medium {{ $stressClass }}">
                    {{ $stressLabel }}
                </div>
                @php
                    // Headline trend arrow: compare last 2 points in sparkline for composite score
                    $trendArrow = '';
                    if (isset($sparklines['interest']['values'])) {
                        // Build a simple combined direction signal:
                        // Sum last direction of each indicator's sparkline
                        $dir = 0;
                        foreach ($sparklines as $series) {
                            $vals = $series['values'] ?? [];
                            if (is_array($vals) && count($vals) >= 2) {
                                $last = end($vals);
                                $prev = prev($vals);
                                if ($last > $prev) $dir++;
                                elseif ($last < $prev) $dir--;
                            }
                        }
                        if ($dir > 1) {
                            $trendArrow = '▲';
                        } elseif ($dir < -1) {
                            $trendArrow = '▼';
                        } else {
                            $trendArrow = '▶';
                        }
                    }
                @endphp
                <div class="text-3xl font-semibold text-gray-900 flex items-center gap-2">
                    {{ $stressScore }}
                    <span class="text-xl {{ $trendArrow === '▲' ? 'text-rose-600' : ($trendArrow === '▼' ? 'text-emerald-600' : 'text-gray-500') }}">
                        {{ $trendArrow }}
                    </span>
                    <span class="text-base font-normal text-gray-600">/ 100</span>
                </div>
                <p class="mt-1 text-[11px] text-gray-500">
                    Raw score: {{ $totalStress }} / 28
                </p>
            </div>
            <p class="mt-6 text-xs text-gray-500">
                Higher scores mean more stress and risk; lower scores mean a calmer backdrop for the property market.
                Roughly: below 50 = low stress, 50–79 = elevated risk, 80+ = high stress.
            </p>
        @endif
    </section>

    {{-- EXPLANATION PANEL --}}
    <section class="my-10">
        <details class="group rounded-lg border border-amber-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-amber-900 flex items-center justify-between">
                How these indicators signal market stress
                <span class="text-xs text-amber-700 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-600 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            <div class="px-5 pb-5 pt-3 text-sm text-zinc-800">
                <p>
                    When several indicators turn negative at the same time, the housing market typically weakens.
                    High interest rates, rising unemployment, falling real wages and declining mortgage approvals are
                    all historically linked to price stagnation or outright declines.
                </p>
                <p class="mt-2">This dashboard helps identify early warning signs:</p>
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li><span class="font-medium">High interest rates</span> tighten affordability and reduce demand.</li>
                    <li><span class="font-medium">High inflation</span> erodes real incomes, unless wage growth keeps up.</li>
                    <li><span class="font-medium">Weak wage growth</span> makes mortgages harder to service.</li>
                    <li><span class="font-medium">Rising unemployment</span> increases forced sales risk.</li>
                    <li><span class="font-medium">Falling mortgage approvals</span> signal tightening credit conditions.</li>
                    <li><span class="font-medium">Rising repossessions</span> indicate financial distress.</li>
                    <li><span class="font-medium">Weak HPI</span> often reflects reduced demand or affordability pressure.</li>
                </ul>
                <p class="mt-3 font-semibold">How the colours and score work:</p>
                <ul class="list-disc pl-5 mt-1 text-xs space-y-1 text-amber-900">
                    <li><span class="font-medium text-emerald-800">Green</span> – conditions are broadly supportive or normal for that indicator.</li>
                    <li><span class="font-medium text-amber-800">Amber</span> – starting to move into a more challenging zone.</li>
                    <li><span class="font-medium text-rose-800">Red</span> – historically associated with stress for the housing market.</li>
                </ul>
                <p class="mt-2 text-xs text-amber-900">
                    The Property Stress Index above combines all seven indicators into a 0–100 score.
                    Roughly: 80–100 = high stress, 50–79 = elevated risk, below 50 = low stress.
                    It is a guide only – the individual charts and numbers always matter more than any single number.
                </p>
            </div>
        </details>
    </section>

    {{-- INDICATOR GRID --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">

        {{-- INTEREST RATES --}}
        @php
            // Quarter-based early warning system for interest rates
            // Up is bad: consecutive quarters of rising averages increase stress level.
            $interestLevel = 'na';
            $intVals = $sparklines['interest']['values'] ?? [];

            if (is_array($intVals) && count($intVals) >= 6) {
                $n = count($intVals);

                $makeQuarterInt = function (int $endIndex) use ($intVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($intVals[$i])) {
                            return null;
                        }
                        $sum += (float)$intVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                // Latest three (or four) quarters: Q0 (oldest) ... Q3 (latest)
                $latestQ = $makeQuarterInt($n - 1);   // Q3
                $prevQ   = $makeQuarterInt($n - 4);   // Q2
                $prev2Q  = $makeQuarterInt($n - 7);   // Q1
                $prev3Q  = $makeQuarterInt($n - 10);  // Q0

                if ($latestQ !== null && $prevQ !== null) {
                    // Determine how many consecutive quarters have moved in the bad direction (up)
                    $badStreak = 0;

                    if ($latestQ > $prevQ) {
                        $badStreak = 1;

                        if ($prev2Q !== null && $prevQ > $prev2Q) {
                            $badStreak = 2;

                            if ($prev3Q !== null && $prev2Q > $prev3Q) {
                                $badStreak = 3;
                            }
                        }
                    }

                    if ($badStreak === 0) {
                        // Latest quarter is flat or better than previous: supportive/normal
                        $interestLevel = 'green';
                    } elseif ($badStreak === 1) {
                        // One bad quarter in a row
                        $interestLevel = 'amber';
                    } elseif ($badStreak === 2) {
                        // Two consecutive bad quarters
                        $interestLevel = 'red';
                    } else {
                        // Three or more consecutive bad quarters – very elevated stress
                        $interestLevel = 'deep';
                    }
                }
            }

            // Fallback if we couldn't compute a quarter signal but we still have a latest rate
            if ($interestLevel === 'na' && $interest) {
                $r = (float) $interest->rate;
                if ($r >= 7.0) {
                    $interestLevel = 'red';
                } elseif ($r >= 4.0) {
                    $interestLevel = 'amber';
                } else {
                    $interestLevel = 'green';
                }
            }

            $interestClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
                'na'    => 'border-gray-200 bg-white',
            ][$interestLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $interestClasses }}" title="{{ $trendTexts['interest'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Interest Rates</div>
                    <p class="text-[11px] text-gray-600">Lower is supportive; rising rates increase stress.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 15l4-4 3 3 5-7"></path>
                    </svg>
                </div>
            </div>
            @if($interest)
                <div class="text-2xl font-semibold">{{ number_format($interest->rate, 2) }}%</div>
                <div class="text-sm text-gray-600 mt-1">
                    As of {{ \Carbon\Carbon::parse($interest->effective_date)->format('d M Y') }}
                    @if(!empty($sparklines['interest']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-interest"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- INFLATION --}}
        @php
            $inflationLevel = 'na';
            $inflVals = $sparklines['inflation']['values'] ?? [];

            if (is_array($inflVals) && count($inflVals) >= 6) {
                $n = count($inflVals);

                $makeQuarter = function (int $endIndex) use ($inflVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($inflVals[$i])) {
                            return null;
                        }
                        $sum += (float)$inflVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                $latestQ = $makeQuarter($n - 1);
                $prevQ   = $makeQuarter($n - 4);
                $prev2Q  = $makeQuarter($n - 7);

                if ($latestQ !== null && $prevQ !== null) {
                    if ($latestQ <= $prevQ) {
                        $inflationLevel = 'green';
                    } else {
                        if ($prev2Q !== null && $prevQ > $prev2Q) {
                            $inflationLevel = 'red';
                        } else {
                            $inflationLevel = 'amber';
                        }
                    }
                }
            }

            $inflationClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$inflationLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $inflationClasses }}" title="{{ $trendTexts['inflation'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Inflation (CPIH)</div>
                    <p class="text-[11px] text-gray-600">Lower is supportive; persistent rises are negative unless wages keep up.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19h14"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l2-4 2 4 2-8"></path>
                        <circle cx="8" cy="6" r="1"></circle>
                    </svg>
                </div>
            </div>
            @if($inflation)
                @php
                    $cpihValue = null;
                    $vars = get_object_vars($inflation);
                    foreach ($vars as $key => $val) {
                        if (in_array($key, ['id','date','created_at','updated_at'])) {
                            continue;
                        }
                        $cpihValue = $val;
                        break;
                    }
                @endphp
                <div class="text-2xl font-semibold">
                    @if(!is_null($cpihValue))
                        {{ number_format((float) $cpihValue, 1) }}%
                    @else
                        <span class="text-gray-500 text-base">n/a</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($inflation->date)->format('M Y') }}
                    @if(!empty($sparklines['inflation']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-inflation"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- WAGE GROWTH --}}
        @php
            // QUARTER-based signal for REAL wage growth (wage minus inflation)
            $wageLevel = 'na';

            $wageVals = $sparklines['wages']['values'] ?? [];
            $inflVals = $sparklines['inflation']['values'] ?? [];

            // Build a real-wage series (wage - inflation)
            $realVals = [];
            if (is_array($wageVals) && is_array($inflVals) && count($wageVals) === count($inflVals)) {
                for ($i = 0; $i < count($wageVals); $i++) {
                    $realVals[] = (float)$wageVals[$i] - (float)$inflVals[$i];
                }
            }

            if (count($realVals) >= 6) {
                $n = count($realVals);

                $makeQuarterReal = function (int $endIndex) use ($realVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($realVals[$i])) {
                            return null;
                        }
                        $sum += (float)$realVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                $latestQ = $makeQuarterReal($n - 1);
                $prevQ   = $makeQuarterReal($n - 4);

                if ($latestQ !== null && $prevQ !== null) {
                    if ($latestQ >= 0) {
                        // Real wages positive or zero this quarter – supportive
                        $wageLevel = 'white';
                    } else {
                        // Real wages negative this quarter
                        if ($prevQ < 0) {
                            $wageLevel = 'red'; // two negative quarters
                        } else {
                            $wageLevel = 'amber'; // first negative quarter
                        }
                    }
                }
            }

            $wageClasses = [
                'white' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$wageLevel] ?? 'border-emerald-200 bg-emerald-50';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $wageClasses }}" title="{{ $trendTexts['wages'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Wage Growth</div>
                    <p class="text-[11px] text-gray-600">Higher real wage growth is positive; negative real wages are a drag.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19v-4a2 2 0 0 1 2-2h2"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19v-6a2 2 0 0 1 2-2h2"></path>
                        <circle cx="8" cy="7" r="2"></circle>
                        <circle cx="15" cy="6" r="2"></circle>
                    </svg>
                </div>
            </div>
            @if($wages)
                @php
                    $wageValue = null;
                    if (isset($wages->three_month_avg_yoy)) {
                        $wageValue = $wages->three_month_avg_yoy;
                    } elseif (isset($wages->single_month_yoy)) {
                        $wageValue = $wages->single_month_yoy;
                    }

                    $realWage = null;
                    if (!is_null($wageValue) && isset($cpihValue) && !is_null($cpihValue)) {
                        $realWage = (float)$wageValue - (float)$cpihValue;
                    }
                @endphp
                <div class="text-2xl font-semibold flex items-baseline gap-2">
                    @if(!is_null($wageValue))
                        {{ number_format((float) $wageValue, 2) }}%
                    @else
                        <span class="text-gray-500 text-base">n/a</span>
                    @endif

                    @if(!is_null($realWage))
                        <span class="text-sm font-normal {{ $realWage >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            (real: {{ number_format($realWage, 2) }}%)
                        </span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($wages->date)->format('M Y') }}
                    @if(!empty($sparklines['wages']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-wages"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- UNEMPLOYMENT --}}
        @php
            $unempLevel = 'na';
            $unempVals = $sparklines['unemployment']['values'] ?? [];

            if (is_array($unempVals) && count($unempVals) >= 6) {
                $n = count($unempVals);

                $makeQuarterUnemp = function (int $endIndex) use ($unempVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($unempVals[$i])) {
                            return null;
                        }
                        $sum += (float)$unempVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                $latestQ = $makeQuarterUnemp($n - 1);
                $prevQ   = $makeQuarterUnemp($n - 4);
                $prev2Q  = $makeQuarterUnemp($n - 7);

                if ($latestQ !== null && $prevQ !== null) {
                    if ($latestQ <= $prevQ) {
                        $unempLevel = 'green';
                    } else {
                        if ($prev2Q !== null && $prevQ > $prev2Q) {
                            $unempLevel = 'red';
                        } else {
                            $unempLevel = 'amber';
                        }
                    }
                }
            }

            $unempClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$unempLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $unempClasses }}" title="{{ $trendTexts['unemployment'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Unemployment</div>
                    <p class="text-[11px] text-gray-600">Lower is positive; rising unemployment is a warning sign.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 20v-3a3 3 0 0 1 3-3h8"></path>
                        <circle cx="9" cy="8" r="2.5"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9v6"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15h4"></path>
                    </svg>
                </div>
            </div>
            @if($unemp)
                @php
                    $unempValue = null;
                    $vars = get_object_vars($unemp);
                    foreach ($vars as $key => $val) {
                        if (in_array($key, ['id','date','created_at','updated_at'])) {
                            continue;
                        }
                        $unempValue = $val;
                        break;
                    }
                @endphp
                <div class="text-2xl font-semibold">
                    @if(!is_null($unempValue))
                        {{ number_format((float) $unempValue, 1) }}%
                    @else
                        <span class="text-gray-500 text-base">n/a</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($unemp->date)->format('M Y') }}
                    @if(!empty($sparklines['unemployment']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-unemployment"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- MORTGAGE APPROVALS --}}
        @php
            // Mortgage approvals: QUARTER-based direction
            // Green = latest quarter approvals >= previous quarter
            // Amber = latest quarter < previous quarter
            // Red   = two worsening quarters in a row
            $approvalsLevel = 'na';

            $appVals = $sparklines['approvals']['values'] ?? [];

            if (is_array($appVals) && count($appVals) >= 6) {
                $n = count($appVals);

                $makeQuarterApp = function (int $endIndex) use ($appVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($appVals[$i])) {
                            return null;
                        }
                        $sum += (float)$appVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                $latestQ = $makeQuarterApp($n - 1);
                $prevQ   = $makeQuarterApp($n - 4);
                $prev2Q  = $makeQuarterApp($n - 7);

                if ($latestQ !== null && $prevQ !== null) {
                    if ($latestQ >= $prevQ) {
                        $approvalsLevel = 'green';
                    } else {
                        if ($prev2Q !== null && $prevQ < $prev2Q) {
                            $approvalsLevel = 'red';
                        } else {
                            $approvalsLevel = 'amber';
                        }
                    }
                }
            }

            $approvalsClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$approvalsLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $approvalsClasses }}" title="{{ $trendTexts['approvals'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Mortgage Approvals</div>
                    <p class="text-[11px] text-gray-600">Higher approvals are supportive; persistent declines signal tightening credit.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 10v9h5v-5h2v5h5v-9"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16"></path>
                    </svg>
                </div>
            </div>
            @if($approvals)
                <div class="text-2xl font-semibold">
                    {{ number_format((float) $approvals->value) }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    @php
                        try {
                            echo \Carbon\Carbon::parse($approvals->period)->format('M Y');
                        } catch (\Throwable $e) {
                            echo e($approvals->period);
                        }
                    @endphp
                    @if(!empty($sparklines['approvals']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-approvals"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- REPOSSESSIONS --}}
        @php
            $repossLevel = 'na';
            if ($reposs && isset($reposs->total)) {
                $rv = (float) $reposs->total;
                if ($rv >= 10000) {
                    $repossLevel = 'red';
                } elseif ($rv >= 5000) {
                    $repossLevel = 'amber';
                } else {
                    $repossLevel = 'green';
                }
            }
            $repossClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$repossLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $repossClasses }}" title="{{ $trendTexts['repossessions'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Repossessions</div>
                    <p class="text-[11px] text-gray-600">Lower is positive; rising repossessions indicate distress.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21v-5"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 21v-3"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 15l10-6"></path>
                    </svg>
                </div>
            </div>
            @if($reposs)
                <div class="text-2xl font-semibold">
                    {{ number_format((float) $reposs->total) }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $reposs->year }} {{ $reposs->quarter }}
                    @if(!empty($sparklines['repossessions']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-repossessions"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- HPI --}}
        @php
            $hpiLevel = 'na';
            $hpiVals = $sparklines['hpi']['values'] ?? [];

            if (is_array($hpiVals) && count($hpiVals) >= 6) {
                $n = count($hpiVals);

                $makeQuarterHpi = function (int $endIndex) use ($hpiVals) {
                    $start = $endIndex - 2;
                    if ($start < 0) {
                        return null;
                    }
                    $sum = 0.0;
                    $count = 0;
                    for ($i = $start; $i <= $endIndex; $i++) {
                        if (!isset($hpiVals[$i])) {
                            return null;
                        }
                        $sum += (float)$hpiVals[$i];
                        $count++;
                    }
                    return $count > 0 ? $sum / $count : null;
                };

                $latestQ = $makeQuarterHpi($n - 1);
                $prevQ   = $makeQuarterHpi($n - 4);
                $prev2Q  = $makeQuarterHpi($n - 7);

                if ($latestQ !== null && $prevQ !== null) {
                    if ($latestQ >= $prevQ) {
                        $hpiLevel = 'green';
                    } else {
                        if ($prev2Q !== null && $prevQ < $prev2Q) {
                            $hpiLevel = 'red';
                        } else {
                            $hpiLevel = 'amber';
                        }
                    }
                }
            }

            $hpiClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'na'    => 'border-gray-200 bg-white',
            ][$hpiLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $hpiClasses }}" title="{{ $trendTexts['hpi'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">House Price Index (UK)</div>
                    <p class="text-[11px] text-gray-600">Modest growth or stability is normal; persistent falls can signal stress.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l7-7 7 7"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11v8h10v-8"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 16h3"></path>
                    </svg>
                </div>
            </div>
            @if($hpi)
                <div class="text-2xl font-semibold">£{{ number_format($hpi->AveragePrice, 0) }}</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($hpi->Date)->format('M Y') }}
                    @if(!empty($sparklines['hpi']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-hpi"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">How the Index Interprets Movement</div>
            <p class="text-xs text-gray-600 leading-5">
                The index looks at how each indicator has behaved over recent quarters rather than a single month.
                Interest rates, inflation and unemployment add stress when they keep rising; wage growth adds stress
                when it fails to keep up with inflation; mortgage approvals and house prices add stress when they fall.
                Repossessions add stress when they rise. One difficult quarter nudges the score higher, while longer
                runs of adverse quarters and extreme levels push it further into the amber and red zones.
            </p>
        </div>

    </section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const data = @json($sparklines ?? []);

    // badWhen: 'up'   -> higher values are worse (e.g. inflation, unemployment, interest, repos)
    //          'down' -> lower values are worse (e.g. approvals, HPI, real wages proxy)
    function makeSpark(id, key, badWhen = 'up') {
        const el = document.getElementById(id);
        if (!el) return;
        const values = data[key]?.values || [];
        if (!values.length) return;

        const ctx = el.getContext('2d');

        const labels = data[key]?.labels || values.map((_, i) => i + 1);

        const pointBackgroundColor = [];
        const pointRadius = [];
        const neutralColor = 'rgba(148, 163, 184, 1)'; // zinc-400-ish
        const badColor = 'rgba(220, 38, 38, 1)';       // red-600-ish

        values.forEach((v, i) => {
            if (i === 0) {
                pointBackgroundColor.push(neutralColor);
                pointRadius.push(0);
                return;
            }
            const prev = values[i - 1];
            let isBad = false;
            if (badWhen === 'up') {
                isBad = v > prev;
            } else if (badWhen === 'down') {
                isBad = v < prev;
            }
            pointBackgroundColor.push(isBad ? badColor : neutralColor);
            pointRadius.push(isBad ? 2 : 0);
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderWidth: 1.5,
                    pointRadius: pointRadius,
                    pointBackgroundColor: pointBackgroundColor,
                    pointHoverRadius: 3,
                    tension: 0.3,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        displayColors: false,
                        callbacks: {
                            title: function(items) {
                                if (!items || !items.length) return '';
                                const idx = items[0].dataIndex;
                                return labels[idx] ? String(labels[idx]) : '';
                            },
                            label: function(ctx) {
                                const v = ctx.parsed.y;
                                if (v === null || v === undefined) return '';
                                return (typeof v === 'number' ? v.toFixed(2) : v);
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    }

    // Higher is worse
    makeSpark('spark-interest', 'interest', 'up');
    makeSpark('spark-inflation', 'inflation', 'up');
    makeSpark('spark-unemployment', 'unemployment', 'up');
    makeSpark('spark-repossessions', 'repossessions', 'up');

    // Lower is worse
    makeSpark('spark-wages', 'wages', 'down');
    makeSpark('spark-approvals', 'approvals', 'down');
    makeSpark('spark-hpi', 'hpi', 'down');
})();
</script>
</div>
@endsection
