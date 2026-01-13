@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    
    {{-- CALCULATE STRESS SCORE AND LEVEL --}}
    @php
        // Use hybrid stress score from controller
        $total = $totalStress ?? 0;
        // Convert to 0–100 scale (max possible is 31: seven 4-point indicators plus arrears (0–3))
        $scaled = max(0, min(100, round(($total / 31) * 100)));
        $stressScore = $scaled;

        // Determine stress level and styling
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

        // Helper function to calculate indicator level based on consecutive bad quarters
        // This consolidates the repeated streak calculation logic used throughout the file
        function calculateStreakLevel($values, $direction = 'up') {
            if (!is_array($values) || count($values) < 2) {
                return 'na';
            }

            $n = count($values);
            $badStreak = 0;

            // Count consecutive bad quarters from latest backwards
            for ($i = $n - 1; $i >= 1; $i--) {
                $cur = (float) $values[$i];
                $prev = (float) $values[$i - 1];
                
                $isBad = ($direction === 'up') ? ($cur > $prev) : ($cur < $prev);
                
                if ($isBad) {
                    $badStreak++;
                } else {
                    break;
                }
            }

            // Convert streak to level
            if ($badStreak === 0) return 'green';
            if ($badStreak === 1) return 'amber';
            if ($badStreak === 2 || $badStreak === 3) return 'red';
            return 'deep'; // 4+ consecutive bad quarters
        }

        // Helper function to get CSS classes for level
        function getLevelClasses($level) {
            return match($level) {
                'green' => 'border-emerald-200 bg-emerald-50',
                'amber' => 'border-amber-200 bg-amber-50',
                'red' => 'border-rose-200 bg-rose-50',
                'deep' => 'border-rose-400 bg-rose-100',
                default => 'border-gray-200 bg-white',
            };
        }

        // Calculate levels for all indicators
        $levels = [
            'interest' => calculateStreakLevel($sparklines['interest']['values'] ?? [], 'up'),
            'inflation' => calculateStreakLevel($sparklines['inflation']['values'] ?? [], 'up'),
            'unemployment' => calculateStreakLevel($sparklines['unemployment']['values'] ?? [], 'up'),
            'approvals' => calculateStreakLevel($sparklines['approvals']['values'] ?? [], 'down'),
            'hpi' => calculateStreakLevel($sparklines['hpi']['values'] ?? [], 'down'),
        ];

        // Special handling for wages (real wage growth)
        $wageVals = $sparklines['wages']['values'] ?? [];
        $inflVals = $sparklines['inflation']['values'] ?? [];
        $realVals = [];
        if (count($wageVals) === count($inflVals)) {
            for ($i = 0; $i < count($wageVals); $i++) {
                $realVals[] = (float)$wageVals[$i] - (float)$inflVals[$i];
            }
        }
        if (!empty($realVals)) {
            $latest = end($realVals);
            if ($latest >= 0) {
                $levels['wages'] = 'green';
            } else {
                $streak = 1;
                for ($i = count($realVals) - 2; $i >= 0; $i--) {
                    if ($realVals[$i] < 0) $streak++; else break;
                }
                $levels['wages'] = match(true) {
                    $streak === 1 => 'amber',
                    $streak === 2 => 'red',
                    default => 'deep'
                };
            }
        } else {
            $levels['wages'] = 'na';
        }

        // Repossessions level from controller
        $levels['repossessions'] = match($repossDirection ?? null) {
            0 => 'green',
            1 => 'amber',
            2 => 'red',
            3 => 'deep',
            default => 'na',
        };

        // Arrears level from controller
        $levels['arrears'] = match($arrearsPanel['direction'] ?? null) {
            0 => 'green',
            1 => 'amber',
            2 => 'red',
            3 => 'deep',
            default => 'na',
        };
    @endphp

    {{-- HERO SECTION: Dashboard overview --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 border-l-4 border-l-lime-500 bg-gradient-to-br from-white to-gray-50 p-6 md:p-8 shadow-sm mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="max-w-4xl">
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-zinc-900">
                    Market Stress Dashboard
                </h1>
                <p class="text-sm text-zinc-700 mt-2">
                    Overview of key UK economic indicators that influence the housing market: borrowing costs, affordability,
                    labour market strength, credit supply and distress. When several indicators move into the red at the same
                    time, the property market historically weakens.
                </p>
                <p class="text-sm mt-2 text-zinc-700">
                    More information: 
                    <a class="text-lime-700 text-sm hover:text-lime-500 underline" 
                       href="/blog/introducing-the-property-market-economic-indicators-dashboard">
                        Blog Post
                    </a>
                </p>
                <p class="text-xs mt-2 text-zinc-600">
                    Note: Updates can be sporadic (monthly/quarterly), may be one period behind, and data can take time to become available.
                </p>
                <p class="text-xs mt-2 text-zinc-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Last updated: {{ now()->format('j M Y') }}
                </p>
            </div>

            {{-- Hero image --}}
            <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
                <img src="{{ asset('assets/images/site/stress.svg') }}" 
                     alt="Market Stress" 
                     class="w-64 h-auto">
            </div>
        </div>
    </section>

    {{-- EXPLANATION PANEL: How indicators signal stress --}}
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
                    <li><span class="font-medium">Increasing mortgage arrears</span> show more borrowers falling behind on payments.</li>
                    <li><span class="font-medium">Weak HPI</span> often reflects reduced demand or affordability pressure.</li>
                </ul>
                
                <p class="mt-3 font-semibold">How the colours and score work:</p>
                <ul class="list-disc pl-5 mt-1 text-xs space-y-1">
                    <li><span class="font-medium text-emerald-800">Green</span> — conditions are broadly supportive or normal.</li>
                    <li><span class="font-medium text-amber-800">Amber</span> — starting to move into a more challenging zone.</li>
                    <li><span class="font-medium text-rose-600">Red</span> — historically associated with stress for the housing market.</li>
                    <li><span class="font-medium text-rose-900">Dark Red</span> — Suggests the market may be heading for turmoil.</li>
                </ul>
                
                <p class="mt-2 text-xs">
                    The Property Stress Index combines all eight indicators into a 0–100 score.
                    Roughly: 70–100 = high stress, 40–69 = elevated risk, below 40 = low stress.
                </p>
            </div>
        </details>
    </section>

    {{-- OVERALL STRESS SCORE PANEL - Sticky --}}
    @if(!is_null($stressScore))
        @php
            $stressBarClass = $stressScore >= 70 ? 'bg-rose-500' :
                             ($stressScore >= 40 ? 'bg-amber-500' : 'bg-emerald-500');
            $gaugeRotation = $stressScore <= 40
                ? -90 + pow(($stressScore / 40), 1.6) * 71.79
                : ($stressScore <= 69
                    ? -18.21 + (($stressScore - 40) / 29) * 52.10
                    : 33.89 + (($stressScore - 70) / 30) * 56.11);
        @endphp

        <section class="mb-8 rounded-xl border border-gray-200 border-l-4 border-l-lime-500 bg-gradient-to-br from-white to-gray-50 p-5 md:p-6 shadow-lg sticky top-0 z-40 backdrop-blur-sm bg-white/95">
            <div class="flex flex-col gap-4 md:grid md:grid-cols-3 md:items-center">
                {{-- Left: Title and description --}}
                <div class="md:col-span-1">
                    <h2 class="text-sm font-semibold tracking-wide text-gray-700 uppercase">
                        Overall Property Stress Index
                    </h2>
                    <p class="mt-1 text-xs text-gray-700 hidden md:block">
                        A single 0–100 score combining all eight indicators. Higher scores mean more stress and risk.
                    </p>
                </div>

                {{-- Center: Semi-circular gauge --}}
                <div class="flex flex-col items-center md:col-span-1">
                    <div class="relative w-44 h-24">
                        <svg class="w-44 h-24" viewBox="0 0 200 120" aria-hidden="true">
                            <!-- Green zone (0–40) -->
                            <path d="M 20 100 A 80 80 0 0 1 75 24"
                                  fill="none"
                                  stroke="#d1fae5"
                                  stroke-width="12"
                                  stroke-linecap="round" />

                            <!-- Amber zone (40–69) -->
                            <path d="M 75 24 A 80 80 0 0 1 145 33"
                                  fill="none"
                                  stroke="#fef3c7"
                                  stroke-width="12"
                                  stroke-linecap="round" />

                            <!-- Red zone (70–100) -->
                            <path d="M 145 33 A 80 80 0 0 1 180 100"
                                  fill="none"
                                  stroke="#fecaca"
                                  stroke-width="12"
                                  stroke-linecap="round" />

                            <!-- Needle -->
                            <g transform="rotate({{ $gaugeRotation }}, 100, 100)">
                                <line x1="100" y1="100" x2="100" y2="32"
                                      stroke="#1f2937"
                                      stroke-width="3"
                                      stroke-linecap="round" />
                                <circle cx="100" cy="100" r="5" fill="#1f2937" />
                            </g>

                            <!-- Scale labels -->
                            <text x="16" y="116" class="text-[10px] fill-gray-500">0</text>
                            <text x="96" y="18" class="text-[10px] fill-gray-500">50</text>
                            <text x="176" y="116" class="text-[10px] fill-gray-500">100</text>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-1 -mt-2">
                        <span class="text-3xl md:text-4xl font-bold text-gray-900">{{ $stressScore }}</span>
                        <span class="text-xs text-gray-500">/ 100</span>
                    </div>
                    <span class="mt-1 rounded-full border px-3 py-1 text-[11px] font-medium {{ $stressClass }} whitespace-nowrap">
                        {{ $stressLabel }}
                    </span>
                </div>

                {{-- Right: Score explanation --}}
                <div class="flex flex-col items-end text-right md:col-span-1">
                    <div class="text-sm uppercase tracking-wide text-gray-700 font-semibold mb-2">Score guide</div>
                    <p class="text-xs text-gray-600">
                        The score rolls up eight indicators into a 0–100 index. Under 40 is low stress,
                        40–69 signals elevated risk, and 70+ points to high stress. Use it to compare
                        momentum over time rather than a single-month snapshot.
                    </p>
                    <div class="text-[10px] text-gray-500 mt-2">Raw: {{ $totalStress }}/31</div>
                </div>
            </div>
        </section>
    @endif

    {{-- INDICATOR GRID: All 8 economic indicators --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">

        {{-- INTEREST RATES --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['interest']) }}" 
             title="{{ $trendTexts['interest'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Interest Rates</div>
                    <p class="text-[11px] text-gray-600">Lower is supportive; rising rates increase stress.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M6 15l4-4 3 3 5-7"/>
                    </svg>
                </div>
            </div>
            
            @if($interest)
                <div class="text-2xl font-semibold">{{ number_format($interest->rate, 2) }}%</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($interest->effective_date)->format('M Y') }}
                    @if(!empty($sparklines['interest']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-interest"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- INFLATION --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['inflation']) }}" 
             title="{{ $trendTexts['inflation'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Inflation (CPIH)</div>
                    <p class="text-[11px] text-gray-600">Lower is supportive; persistent rises are negative.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19h14M9 15l2-4 2 4 2-8"/>
                        <circle cx="8" cy="6" r="1"/>
                    </svg>
                </div>
            </div>
            
            @if($inflation)
                @php
                    $cpihValue = collect(get_object_vars($inflation))
                        ->except(['id', 'date', 'created_at', 'updated_at'])
                        ->first();
                @endphp
                <div class="text-2xl font-semibold">
                    {{ !is_null($cpihValue) ? number_format((float) $cpihValue, 1) . '%' : 'n/a' }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($inflation->date)->format('M Y') }}
                    @if(!empty($sparklines['inflation']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-inflation"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- WAGE GROWTH --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['wages']) }}" 
             title="{{ $trendTexts['wages'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Wage Growth</div>
                    <p class="text-[11px] text-gray-600">Higher real wage growth is positive; negative real wages are a drag.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19v-4a2 2 0 0 1 2-2h2M11 19v-6a2 2 0 0 1 2-2h2"/>
                        <circle cx="8" cy="7" r="2"/><circle cx="15" cy="6" r="2"/>
                    </svg>
                </div>
            </div>
            
            @if($wages)
                @php
                    $wageValue = $wages->three_month_avg_yoy ?? $wages->single_month_yoy ?? null;
                    $realWage = (!is_null($wageValue) && isset($cpihValue)) 
                        ? (float)$wageValue - (float)$cpihValue 
                        : null;
                @endphp
                <div class="text-2xl font-semibold flex items-baseline gap-2">
                    {{ !is_null($wageValue) ? number_format((float) $wageValue, 2) . '%' : 'n/a' }}
                    @if(!is_null($realWage))
                        <span class="text-sm font-normal {{ $realWage >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            (real: {{ number_format($realWage, 2) }}%)
                        </span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($wages->date)->format('M Y') }}
                    @if(!empty($sparklines['wages']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-wages"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- UNEMPLOYMENT --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['unemployment']) }}" 
             title="{{ $trendTexts['unemployment'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Unemployment</div>
                    <p class="text-[11px] text-gray-600">Lower is positive; rising unemployment is a warning sign.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 20v-3a3 3 0 0 1 3-3h8"/>
                        <circle cx="9" cy="8" r="2.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9v6M15 15h4"/>
                    </svg>
                </div>
            </div>
            
            @if($unemp)
                @php
                    $unempValue = collect(get_object_vars($unemp))
                        ->except(['id', 'date', 'created_at', 'updated_at'])
                        ->first();
                @endphp
                <div class="text-2xl font-semibold">
                    {{ !is_null($unempValue) ? number_format((float) $unempValue, 1) . '%' : 'n/a' }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($unemp->date)->format('M Y') }}
                    @if(!empty($sparklines['unemployment']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-unemployment"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- MORTGAGE APPROVALS --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['approvals']) }}" 
             title="{{ $trendTexts['approvals'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Mortgage Approvals</div>
                    <p class="text-[11px] text-gray-600">Higher approvals are supportive; persistent declines signal tightening credit.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M6 10v9h5v-5h2v5h5v-9M4 19h16"/>
                    </svg>
                </div>
            </div>
            
            @if($approvals)
                <div class="text-2xl font-semibold">{{ number_format((float) $approvals->value) }}</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($approvals->period)->format('M Y') }}
                    @if(!empty($sparklines['approvals']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-approvals"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- REPOSSESSIONS --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['repossessions']) }}" 
             title="{{ $trendTexts['repossessions'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Repossessions (MLAR)</div>
                    <p class="text-[11px] text-gray-600">Share of regulated mortgages in possession. Lower is positive.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M9 21v-5M15 21v-3M4 19h16M7 15l10-6"/>
                    </svg>
                </div>
            </div>
            
            @if($reposs)
                <div class="text-2xl font-semibold">{{ number_format((float) $reposs->total, 3) }}%</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $reposs->year }} {{ $reposs->quarter }}
                    @if(!empty($sparklines['repossessions']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-repossessions"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- MORTGAGE ARREARS --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['arrears']) }}" 
             title="Total arrears 2.5%+ of balance (excluding 1.5–2.5% band)">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Mortgage Arrears (MLAR)</div>
                    <p class="text-[11px] text-gray-600">Total arrears 2.5%+ of balance. Higher is worse.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M6 15l4-4 3 3 5-7"/>
                    </svg>
                </div>
            </div>
            
            @if(!empty($arrearsPanel))
                <div class="text-2xl font-semibold">{{ number_format((float) $arrearsPanel['value'], 3) }}%</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $arrearsPanel['year'] }} {{ $arrearsPanel['quarter'] }}
                    @if(!empty($sparklines['arrears']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-arrears"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

        {{-- HOUSE PRICE INDEX --}}
        <div class="rounded-lg border p-5 shadow-sm transition-all hover:shadow-md {{ getLevelClasses($levels['hpi']) }}" 
             title="{{ $trendTexts['hpi'] ?? '' }}">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">House Price Index (UK)</div>
                    <p class="text-[11px] text-gray-600">Modest growth or stability is normal; persistent falls signal stress.</p>
                </div>
                <div class="ml-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l7-7 7 7M7 11v8h10v-8M9 16h3"/>
                    </svg>
                </div>
            </div>
            
            @if($hpi)
                <div class="text-2xl font-semibold">£{{ number_format($hpi->AveragePrice, 0) }}</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ \Carbon\Carbon::parse($hpi->Date)->format('M Y') }}
                    @if(!empty($sparklines['hpi']['values'] ?? []))
                        <div class="h-28 pt-8"><canvas id="spark-hpi"></canvas></div>
                    @endif
                </div>
            @else
                <div class="text-gray-500 text-sm">No data</div>
            @endif
        </div>

    </section>
</div>

{{-- Chart.js library and sparkline initialization --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const data = @json($sparklines ?? []);

    // Create sparkline chart for an indicator
    // badWhen: 'up' = higher values are worse, 'down' = lower values are worse
    function makeSpark(id, key, badWhen = 'up') {
        const el = document.getElementById(id);
        if (!el) return;
        
        const values = data[key]?.values || [];
        if (!values.length) return;

        const ctx = el.getContext('2d');
        const labels = data[key]?.labels || values.map((_, i) => i + 1);

        // Highlight bad quarters with red dots
        const pointBackgroundColor = [];
        const pointRadius = [];
        const neutralColor = 'rgba(148, 163, 184, 1)';
        const badColor = 'rgba(220, 38, 38, 1)';

        // Determine which quarters are "bad" based on direction
        values.forEach((v, i) => {
            if (i === 0) {
                pointBackgroundColor.push(neutralColor);
                pointRadius.push(0);
                return;
            }
            const prev = values[i - 1];
            const isBad = (badWhen === 'up') ? (v > prev) : (v < prev);
            pointBackgroundColor.push(isBad ? badColor : neutralColor);
            pointRadius.push(isBad ? 2 : 0);
        });

        // Create Chart.js sparkline
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: 'rgba(100, 116, 139, 0.8)',
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
                            title: (items) => items?.[0] ? String(labels[items[0].dataIndex]) : '',
                            label: (ctx) => {
                                const v = ctx.parsed.y;
                                return (v !== null && v !== undefined) ? v.toFixed(2) : '';
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

    // Initialize all sparklines
    // Higher values are worse for these indicators
    makeSpark('spark-interest', 'interest', 'up');
    makeSpark('spark-inflation', 'inflation', 'up');
    makeSpark('spark-unemployment', 'unemployment', 'up');
    makeSpark('spark-repossessions', 'repossessions', 'up');
    makeSpark('spark-arrears', 'arrears', 'up');

    // Lower values are worse for these indicators
    makeSpark('spark-wages', 'wages', 'down');
    makeSpark('spark-approvals', 'approvals', 'down');
    makeSpark('spark-hpi', 'hpi', 'down');
})();
</script>
@endsection
