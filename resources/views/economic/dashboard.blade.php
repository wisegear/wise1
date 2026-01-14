@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    
    {{-- CALCULATE STRESS LEVELS --}}
    @php
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
                'green' => 'border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white',
                'amber' => 'border-amber-200 bg-gradient-to-br from-amber-50 via-white to-white',
                'red' => 'border-rose-200 bg-gradient-to-br from-rose-50 via-white to-white',
                'deep' => 'border-rose-400 bg-gradient-to-br from-rose-100 via-white to-white',
                default => 'border-gray-200 bg-white',
            };
        }

        function getLevelAccentClasses($level) {
            return match($level) {
                'green' => 'bg-emerald-200/60',
                'amber' => 'bg-amber-200/60',
                'red' => 'bg-rose-200/60',
                'deep' => 'bg-rose-300/70',
                default => 'bg-gray-200/60',
            };
        }

        function getLevelNeedleRotation($level) {
            return match($level) {
                'green' => -60,
                'amber' => -15,
                'red' => 20,
                'deep' => 55,
                default => -60,
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
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 md:p-8 shadow-sm mb-8">
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
                <p class="mt-1 text-xs text-zinc-700">
                    Each panel is ranked by the length of the current “bad” streak (how many consecutive periods the indicator
                    has moved in the direction that historically signals stress). The direction differs by indicator
                    (e.g. higher interest rates and unemployment are worse, while lower approvals and HPI are worse).
                </p>
                <ul class="list-disc pl-5 mt-1 text-xs space-y-1">
                    <li><span class="font-medium text-emerald-800">Green</span> — no current bad streak; conditions broadly supportive or normal.</li>
                    <li><span class="font-medium text-amber-800">Amber</span> — 1 consecutive bad period; early warning signs.</li>
                    <li><span class="font-medium text-rose-600">Red</span> — 2–3 consecutive bad periods; sustained stress signals.</li>
                    <li><span class="font-medium text-rose-900">Dark Red</span> — 4+ consecutive bad periods; elevated risk of downturn.</li>
                </ul>
                
                <p class="mt-2 text-xs">
                    The Property Stress Index combines all eight indicators into a 0–100 score.
                    Roughly: 70–100 = high stress, 40–69 = elevated risk, below 40 = low stress.
                </p>
            </div>
        </details>
    </section>

    {{-- OVERALL STRESS SCORE PANEL - Sticky --}}
    @include('partials.stress-score-panel', ['totalStress' => $totalStress ?? null, 'showDashboardLink' => false])

    {{-- INDICATOR GRID: All 8 economic indicators --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">

        {{-- INTEREST RATES --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['interest']) }}" 
             title="{{ $trendTexts['interest'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['interest']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Interest Rates</div>
                        <p class="text-[11px] text-gray-600">Lower is supportive; rising rates increase stress.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['interest']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- INFLATION --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['inflation']) }}" 
             title="{{ $trendTexts['inflation'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['inflation']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Inflation (CPIH)</div>
                        <p class="text-[11px] text-gray-600">Lower is supportive; persistent rises are negative.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['inflation']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- WAGE GROWTH --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['wages']) }}" 
             title="{{ $trendTexts['wages'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['wages']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Wage Growth</div>
                        <p class="text-[11px] text-gray-600">Higher real wage growth is positive; negative real wages are a drag.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['wages']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- UNEMPLOYMENT --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['unemployment']) }}" 
             title="{{ $trendTexts['unemployment'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['unemployment']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Unemployment</div>
                        <p class="text-[11px] text-gray-600">Lower is positive; rising unemployment is a warning sign.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['unemployment']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- MORTGAGE APPROVALS --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['approvals']) }}" 
             title="{{ $trendTexts['approvals'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['approvals']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Mortgage Approvals</div>
                        <p class="text-[11px] text-gray-600">Higher approvals are supportive; persistent declines signal tightening credit.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['approvals']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- REPOSSESSIONS --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['repossessions']) }}" 
             title="{{ $trendTexts['repossessions'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['repossessions']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Repossessions (MLAR)</div>
                        <p class="text-[11px] text-gray-600">Share of regulated mortgages in possession. Lower is positive.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['repossessions']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- MORTGAGE ARREARS --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['arrears']) }}" 
             title="Total arrears 2.5%+ of balance (excluding 1.5–2.5% band)">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['arrears']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Mortgage Arrears (MLAR)</div>
                        <p class="text-[11px] text-gray-600">Total arrears 2.5%+ of balance. Higher is worse.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['arrears']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
        </div>

        {{-- HOUSE PRICE INDEX --}}
        <div class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ getLevelClasses($levels['hpi']) }}" 
             title="{{ $trendTexts['hpi'] ?? '' }}">
            <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full blur-2xl {{ getLevelAccentClasses($levels['hpi']) }}"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">House Price Index (UK)</div>
                        <p class="text-[11px] text-gray-600">Modest growth or stability is normal; persistent falls signal stress.</p>
                    </div>
                    <div class="ml-3 flex h-11 w-11 items-center justify-center rounded-full">
                        <svg class="h-7 w-11" viewBox="0 0 120 70" aria-hidden="true">
                            <path d="M 12 60 A 48 48 0 0 1 36 18.43"
                                  fill="none"
                                  stroke="#6ee7b7"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 36 18.43 A 48 48 0 0 1 76.42 14.89"
                                  fill="none"
                                  stroke="#fcd34d"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 76.42 14.89 A 48 48 0 0 1 101.57 36"
                                  fill="none"
                                  stroke="#fca5a5"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <path d="M 101.57 36 A 48 48 0 0 1 108 60"
                                  fill="none"
                                  stroke="#fb7185"
                                  stroke-width="12"
                                  stroke-linecap="round" />
                            <g transform="rotate({{ getLevelNeedleRotation($levels['hpi']) }}, 60, 60)">
                                <line x1="60" y1="60" x2="60" y2="18" stroke="#1f2937" stroke-width="3" stroke-linecap="round" />
                                <circle cx="60" cy="60" r="4" fill="#1f2937" />
                            </g>
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
