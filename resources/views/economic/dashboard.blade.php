@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    @php
        // Use hybrid stress score from controller
        $total = $totalStress ?? 0;
        // Convert to 0–100 scale (max possible is 31: seven 4-point indicators plus arrears (0–3))
        $scaled = max(0, min(100, round(($total / 31) * 100)));
        $stressScore = $scaled;

        if ($stressScore >= 70) {
            $stressLabel = 'High stress';
            $stressClass = 'bg-rose-50 text-rose-800 border-rose-200';
        } elseif ($stressScore >= 40) {
            $stressLabel = 'Elevated risk';
            $stressClass = 'bg-amber-50 text-amber-800 border-amber-200';
        } else {
            $stressLabel = 'Low stress';
            $stressClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }
    @endphp

    {{-- HERO SECTION --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Market Stress Dashboard</h1>
        <p class="text-sm text-gray-700 mt-2">
            Overview of key UK economic indicators that influence the housing market: borrowing costs, affordability,
            labour market strength, credit supply and distress. When several indicators move into the red at the same
            time, the property market historically weakens.
        </p>

        </div>

        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/stress.svg') }}" alt="Inflation" class="w-64 h-auto">
        </div>
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
                    <li><span class="font-medium">Increasing mortgage arrears</span> show more borrowers falling behind on payments and often precede possessions.</li>
                    <li><span class="font-medium">Weak HPI</span> often reflects reduced demand or affordability pressure.</li>
                </ul>
                <p class="mt-3 font-semibold">How the colours and score work:</p>
                <ul class="list-disc pl-5 mt-1 text-xs space-y-1">
                    <li><span class="font-medium text-emerald-800">Green</span> – conditions are broadly supportive or normal for that indicator.</li>
                    <li><span class="font-medium text-amber-800">Amber</span> – starting to move into a more challenging zone.</li>
                    <li><span class="font-medium text-rose-600">Red</span> – historically associated with stress for the housing market.</li>
                    <li><span class="font-medium text-rose-900">Dark Red</span> – Possible suggestion that the market has turned and may be heading for a level of turmoil.</li>
                </ul>
                <p class="mt-2 text-xs">
                    The Property Stress Index above combines all eight indicators into a 0–100 score.
                    Roughly: 70–100 = high stress, 40–69 = elevated risk, below 40 = low stress.
                    It is a guide only – the individual charts and numbers always matter more than any single number.
                </p>
            </div>
        </details>
    </section>

    @if(!is_null($stressScore))
        @php
            $stressBarClass = $stressScore >= 70
                ? 'bg-rose-200'
                : ($stressScore >= 40 ? 'bg-amber-200' : 'bg-emerald-200');
        @endphp
        <section class="mb-8 rounded-xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold tracking-wide text-gray-700 uppercase">Overall Property Stress Index</h2>
                    <p class="mt-1 text-xs text-gray-700 max-w-xl">
                        A single 0–100 score that combines all eight indicators. Higher scores mean more stress and risk;
                        lower scores mean a calmer backdrop for the property market.
                    </p>
                </div>
                <div class="flex items-baseline gap-3">
                    <div class="flex items-baseline gap-1">
                        <span class="text-4xl md:text-5xl font-semibold text-gray-900">{{ $stressScore }}</span>
                        <span class="text-sm text-gray-600">/ 100</span>
                    </div>
                    <span class="ml-2 rounded-full border px-3 py-1 text-[11px] font-medium {{ $stressClass }} whitespace-nowrap">
                        {{ $stressLabel }}
                    </span>
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
                <div class="relative h-2 w-full flex-1 rounded-full bg-white/70 border border-zinc-200 overflow-hidden">
                    <div class="h-full {{ $stressBarClass }} rounded-full" style="width: {{ max(0, min(100, $stressScore)) }}%;"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-600 md:w-auto md:justify-start md:gap-4 mt-1 md:mt-0">
                    <span>Raw score: {{ $totalStress }} / 31</span>
                    <span class="hidden md:inline-block text-gray-400">•</span>
                    <span>Below 40 = low, 40–69 = elevated, 70+ = high stress</span>
                </div>
            </div>
        </section>
    @endif

    {{-- INDICATOR GRID --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">

        {{-- INTEREST RATES --}}
        @php
            // Quarter-based early warning system for interest rates
            // We treat the interest sparkline as already-quarterly values and
            // count consecutive rising quarters from the latest backwards.
            // Up is bad here:
            // 0  -> green (no worsening this quarter)
            // 1  -> amber (first worsening quarter)
            // 2-3 -> red (sustained rise over several quarters)
            // 4+ -> deep (prolonged period of rising rates)
            $interestLevel = 'na';
            $intVals = $sparklines['interest']['values'] ?? [];

            if (is_array($intVals) && count($intVals) >= 2) {
                $n = count($intVals);
                $badStreak = 0;

                for ($i = $n - 1; $i >= 1; $i--) {
                    $cur = (float) $intVals[$i];
                    $prev = (float) $intVals[$i - 1];
                    if ($cur > $prev) {
                        $badStreak++;
                    } else {
                        break; // streak broken
                    }
                }

                if ($badStreak === 0) {
                    $interestLevel = 'green';
                } elseif ($badStreak === 1) {
                    $interestLevel = 'amber';
                } elseif ($badStreak === 2 || $badStreak === 3) {
                    $interestLevel = 'red';
                } else {
                    // 4 or more consecutive rising quarters
                    $interestLevel = 'deep';
                }
            }

            // Fallback if we couldn't compute a streak signal but we still have a latest rate
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

            // Treat inflation sparkline as already-quarterly values.
            // We count how many consecutive quarters (from the latest backwards)
            // have risen. This is our early-warning streak:
            // 0  -> green (no worsening this quarter)
            // 1  -> amber (first worsening quarter)
            // 2-3 -> red (three quarters of rising inflation)
            // 4+ -> deep red (four or more rising quarters in a row)
            if (is_array($inflVals) && count($inflVals) >= 2) {
                $n = count($inflVals);
                $badStreak = 0;

                for ($i = $n - 1; $i >= 1; $i--) {
                    $cur = (float) $inflVals[$i];
                    $prev = (float) $inflVals[$i - 1];
                    if ($cur > $prev) {
                        $badStreak++;
                    } else {
                        break; // streak broken
                    }
                }

                if ($badStreak === 0) {
                    $inflationLevel = 'green';
                } elseif ($badStreak === 1) {
                    $inflationLevel = 'amber';
                } elseif ($badStreak === 2 || $badStreak === 3) {
                    $inflationLevel = 'red';
                } else {
                    // 4 or more consecutive rising quarters
                    $inflationLevel = 'deep';
                }
            }

            $inflationClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
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
            // Signal for REAL wage growth (wage minus inflation)
            // Green  = latest real wage growth >= 0
            // Amber  = latest < 0 (one negative period)
            // Red    = latest < 0 and 2 consecutive negative periods
            // Deep   = latest < 0 and 3+ consecutive negative periods
            $wageLevel = 'na';

            $wageVals = $sparklines['wages']['values'] ?? [];
            $inflVals = $sparklines['inflation']['values'] ?? [];

            // Build a real-wage series (wage - inflation) aligned to the sparkline
            $realVals = [];
            if (is_array($wageVals) && is_array($inflVals) && count($wageVals) === count($inflVals)) {
                for ($i = 0; $i < count($wageVals); $i++) {
                    $realVals[] = (float)$wageVals[$i] - (float)$inflVals[$i];
                }
            }

            if (!empty($realVals)) {
                $n = count($realVals);
                $latestReal = (float) $realVals[$n - 1];

                if ($latestReal >= 0) {
                    // Latest period real wages are positive or flat – supportive
                    $wageLevel = 'green';
                } else {
                    // Latest period real wages are negative – measure how persistent
                    $badStreak = 1; // we know the latest is negative
                    for ($i = $n - 2; $i >= 0; $i--) {
                        if ((float)$realVals[$i] < 0) {
                            $badStreak++;
                        } else {
                            break;
                        }
                    }

                    if ($badStreak === 1) {
                        $wageLevel = 'amber';
                    } elseif ($badStreak === 2) {
                        $wageLevel = 'red';
                    } else {
                        // 3 or more consecutive negative periods
                        $wageLevel = 'deep';
                    }
                }
            }

            $wageClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
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
            // Quarter-based early warning for unemployment
            // Treat the unemployment sparkline as already-quarterly values.
            // We count how many consecutive quarters (from the latest backwards)
            // have risen. Up is bad here:
            // 0   -> green (no worsening this quarter)
            // 1   -> amber (first worsening quarter)
            // 2-3 -> red (sustained rise over several quarters)
            // 4+  -> deep (prolonged period of rising unemployment)
            $unempLevel = 'na';
            $unempVals = $sparklines['unemployment']['values'] ?? [];

            if (is_array($unempVals) && count($unempVals) >= 2) {
                $n = count($unempVals);
                $badStreak = 0;

                for ($i = $n - 1; $i >= 1; $i--) {
                    $cur = (float) $unempVals[$i];
                    $prev = (float) $unempVals[$i - 1];
                    if ($cur > $prev) {
                        $badStreak++;
                    } else {
                        break; // streak broken
                    }
                }

                if ($badStreak === 0) {
                    $unempLevel = 'green';
                } elseif ($badStreak === 1) {
                    $unempLevel = 'amber';
                } elseif ($badStreak === 2 || $badStreak === 3) {
                    $unempLevel = 'red';
                } else {
                    // 4 or more consecutive rising quarters
                    $unempLevel = 'deep';
                }
            }

            $unempClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
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
            // Mortgage approvals: quarter-based early warning using already-quarterly sparkline.
            // Down is bad, up or flat is supportive.
            // 0 bad quarters -> green
            // 1 -> amber
            // 2-3 -> red
            // 4+ -> deep red
            $approvalsLevel = 'na';
            $appVals = $sparklines['approvals']['values'] ?? [];

            if (is_array($appVals) && count($appVals) >= 2) {
                $n = count($appVals);
                $badStreak = 0;

                for ($i = $n - 1; $i >= 1; $i--) {
                    $cur  = (float) $appVals[$i];
                    $prev = (float) $appVals[$i - 1];

                    if ($cur < $prev) {
                        // approvals fell vs previous quarter
                        $badStreak++;
                    } else {
                        // flat or rising breaks the bad streak
                        break;
                    }
                }

                if ($badStreak === 0) {
                    $approvalsLevel = 'green';
                } elseif ($badStreak === 1) {
                    $approvalsLevel = 'amber';
                } elseif ($badStreak === 2 || $badStreak === 3) {
                    $approvalsLevel = 'red';
                } else {
                    // 4+ consecutive falling quarters
                    $approvalsLevel = 'deep';
                }
            }

            // If we have data but didn't classify (e.g. only one quarter), default to green
            if ($approvalsLevel === 'na' && is_array($appVals) && count($appVals) > 0) {
                $approvalsLevel = 'green';
            }

            $approvalsClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
                'na'    => 'border-gray-200 bg-white',
            ][$approvalsLevel] ?? 'border-emerald-200 bg-emerald-50';
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

            if (isset($repossDirection)) {
                if ($repossDirection === 0) {
                    $repossLevel = 'green';
                } elseif ($repossDirection === 1) {
                    $repossLevel = 'amber';
                } elseif ($repossDirection === 2) {
                    $repossLevel = 'red';
                } else {
                    // 3 or more worsening quarters
                    $repossLevel = 'deep';
                }
            }

            $repossClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
                'na'    => 'border-gray-200 bg-white',
            ][$repossLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $repossClasses }}" title="{{ $trendTexts['repossessions'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Repossessions (MLAR)</div>
                    <p class="text-[11px] text-gray-600">Share of regulated mortgages in possession. Lower is positive; rising possessions indicate distress.</p>
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
                    {{ number_format((float) $reposs->total, 3) }}%
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

        {{-- MORTGAGE ARREARS (MLAR) --}}
        @php
            $arrearsLevel = 'na';

            if (!empty($arrearsPanel)) {
                $dir = $arrearsPanel['direction'] ?? 0; // 0–3 from controller
                if ($dir === 0) {
                    $arrearsLevel = 'green';
                } elseif ($dir === 1) {
                    $arrearsLevel = 'amber';
                } elseif ($dir === 2) {
                    $arrearsLevel = 'red';
                } else {
                    // 3 or more worsening quarters
                    $arrearsLevel = 'deep';
                }
            }

            $arrearsClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
                'na'    => 'border-gray-200 bg-white',
            ][$arrearsLevel] ?? 'border-gray-200 bg-white';
        @endphp
        <div class="rounded-lg border p-5 shadow-sm {{ $arrearsClasses }}" title="Headline and sparkline show total arrears across all bands from 2.5%+ of balance (excluding the 1.5–2.5% band).">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Mortgage Arrears (MLAR)</div>
                    <p class="text-[11px] text-gray-600">Headline and sparkline show total arrears 2.5%+ of balance (excluding the 1.5–2.5% band). Higher is worse.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 15l4-4 3 3 5-7"></path>
                    </svg>
                </div>
            </div>
            @if(!empty($arrearsPanel))
                <div class="text-2xl font-semibold">
                    {{ number_format((float) $arrearsPanel['value'], 3) }}%
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $arrearsPanel['year'] }} {{ $arrearsPanel['quarter'] }}
                    @if(!empty($sparklines['arrears']['values'] ?? []))
                        <div class="h-28 pt-8">
                            <canvas id="spark-arrears"></canvas>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- HPI --}}
        @php
            // House Price Index: quarter-based early warning using already-quarterly sparkline.
            // Down is bad (falling prices), up or flat is supportive/normal.
            // 0 bad quarters -> green
            // 1 -> amber
            // 2-3 -> red
            // 4+ -> deep red
            $hpiLevel = 'na';
            $hpiVals = $sparklines['hpi']['values'] ?? [];

            if (is_array($hpiVals) && count($hpiVals) >= 2) {
                $n = count($hpiVals);
                $badStreak = 0;

                for ($i = $n - 1; $i >= 1; $i--) {
                    $cur  = (float) $hpiVals[$i];
                    $prev = (float) $hpiVals[$i - 1];

                    if ($cur < $prev) {
                        // HPI fell vs previous quarter
                        $badStreak++;
                    } else {
                        // flat or rising breaks the bad streak
                        break;
                    }
                }

                if ($badStreak === 0) {
                    $hpiLevel = 'green';
                } elseif ($badStreak === 1) {
                    $hpiLevel = 'amber';
                } elseif ($badStreak === 2 || $badStreak === 3) {
                    $hpiLevel = 'red';
                } else {
                    // 4+ consecutive falling quarters
                    $hpiLevel = 'deep';
                }
            }

            // If we have data but didn't classify (e.g. only one quarter), default to green
            if ($hpiLevel === 'na' && is_array($hpiVals) && count($hpiVals) > 0) {
                $hpiLevel = 'green';
            }

            $hpiClasses = [
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red'   => 'border-rose-200 bg-rose-50',
                'deep'  => 'border-rose-400 bg-rose-100',
                'na'    => 'border-gray-200 bg-white',
            ][$hpiLevel] ?? 'border-emerald-200 bg-emerald-50';
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
    makeSpark('spark-arrears', 'arrears', 'up');

    // Lower is worse
    makeSpark('spark-wages', 'wages', 'down');
    makeSpark('spark-approvals', 'approvals', 'down');
    makeSpark('spark-hpi', 'hpi', 'down');
})();
</script>
</div>
@endsection
