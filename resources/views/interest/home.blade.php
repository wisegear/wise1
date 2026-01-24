@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- HERO SECTION: Summary of latest Bank Rate --}}
    @php
        // Sort rates chronologically and get first/last entries
        $sorted = $rates->sortBy('effective_date')->values();
        $latest = $sorted->last();
        $first  = $sorted->first();
    @endphp

    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">
                UK Bank Rate (BoE)
            </h1>
            
            <p class="mt-2 text-sm leading-6 text-gray-700">
                @if($latest)
                    Latest: <span class="font-semibold">{{ number_format((float)$latest->rate, 2) }}%</span>
                    <span class="text-gray-600">as of</span>
                    <span class="font-medium">{{ \Illuminate\Support\Carbon::parse($latest->effective_date)->format('d-m-Y') }}</span>.
                @else
                    No data available yet.
                @endif
            </p>
            
            {{-- Display data coverage range if we have multiple entries --}}
            @if($first && $latest && $first !== $latest)
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Series coverage: {{ \Illuminate\Support\Carbon::parse($first->effective_date)->format('Y') }}
                    to {{ \Illuminate\Support\Carbon::parse($latest->effective_date)->format('Y') }}. 
                    Only actual movements are recorded, periods where there was no change are ignored.
                </p>
            @endif
        </div>
        
        {{-- Hero image --}}
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/interest.svg') }}" 
                 alt="Bank Rate" 
                 class="w-64 h-auto">
        </div>
    </section>

    {{-- PREPARE CHART DATA --}}
    @php
        // Prepare labels and values for Chart.js (oldest to newest)
        $labels = $sorted->map(function($r) {
            try {
                return \Illuminate\Support\Carbon::parse($r->effective_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $r->effective_date;
            }
        });

        $values = $sorted->map(function($r) {
            return (float) ($r->rate ?? 0);
        });

        // Calculate movement direction for each entry: -1 = down, 0 = same, 1 = up, null = first row
        $movementByDate = [];
        $prevRate = null;
        
        foreach ($sorted as $row) {
            try {
                $dateKey = \Illuminate\Support\Carbon::parse($row->effective_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dateKey = (string) $row->effective_date;
            }
            
            $cur = (float) ($row->rate ?? 0);
            
            if ($prevRate === null) {
                // First entry has no previous rate to compare
                $movementByDate[$dateKey] = null;
            } else {
                // Spaceship operator: returns 1 if up, 0 if same, -1 if down
                $movementByDate[$dateKey] = $cur <=> $prevRate;
            }
            
            $prevRate = $cur;
        }
    @endphp

    {{-- MAIN CHART: Line chart showing Bank Rate over time --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">
                Bank Rate over time (hover over line for more detail)
            </div>
            
            @if($sorted->isEmpty())
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="ratesChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- RATE SPIKE HIGHLIGHTS: Collapsible panel explaining major rate movements --}}
    <section class="mb-6">
        <details class="group rounded-lg border border-zinc-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-orange-500 flex items-center justify-between">
                Rate spike highlights
                <span class="text-xs text-lime-600 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-500 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            
            <div class="px-5 pb-5 pt-3 text-xs leading-6 text-zinc-700 space-y-2">
                <ul class="list-disc pl-5 space-y-1">
                    <li>
                        <span class="font-medium">1976</span> — Sterling crisis and an <span class="font-medium">IMF bailout</span>. 
                        Inflation running in the high teens/20s and a weak pound prompted aggressive monetary tightening to defend the currency.
                    </li>
                    <li>
                        <span class="font-medium">1977</span> — Continued anti‑inflation policy and currency support kept rates elevated 
                        while fiscal consolidation from the IMF programme worked through the economy.
                    </li>
                    <li>
                        <span class="font-medium">1988</span> — The <span class="font-medium">"Lawson Boom"</span>: rapid credit growth, 
                        strong demand and a house‑price surge pushed inflation risks higher, leading to successive rate hikes.
                    </li>
                    <li>
                        <span class="font-medium">1994</span> — Post‑ERM recovery saw policy tighten modestly to head off inflation as growth firmed; 
                        globally, bond yields jumped during the 1994 "bond massacre," adding to upward pressures.
                    </li>
                    <li>
                        <span class="font-medium">2022</span> — Post‑pandemic inflation shock: energy prices, supply chain disruptions and 
                        the Russia‑Ukraine war drove CPI sharply higher, triggering the fastest BoE hiking cycle in decades.
                    </li>
                </ul>
                
                <p class="mt-2 text-xs text-orange-500">
                    Notes are brief context only; they summarise widely reported drivers behind each episode.
                </p>
            </div>
        </details>
    </section>

    {{-- STATISTICS CALCULATIONS --}}
    @if($sorted->isNotEmpty())
    @php
        // Find highest and lowest rates
        $maxRate = $sorted->max('rate');
        $minRate = $sorted->min('rate');
        $maxRow = $sorted->filter(fn($r) => (float)$r->rate === (float)$maxRate)->last();
        $minRow = $sorted->filter(fn($r) => (float)$r->rate === (float)$minRate)->last();
        
        // Count total up and down movements
        $upCount = collect($movementByDate)->filter(fn($m) => $m === 1)->count();
        $downCount = collect($movementByDate)->filter(fn($m) => $m === -1)->count();

        // === LAST CHANGE CALCULATION ===
        $lastChangeDir = null;   // 'up' | 'down' | 'same' | null
        $lastChangeDelta = null; // float
        $lastChangeDate = null;  // Carbon|null

        if ($sorted->count() >= 2) {
            $prevRow = $sorted[$sorted->count() - 2];
            $latestRow = $sorted[$sorted->count() - 1];

            $prevRateF = (float) ($prevRow->rate ?? 0);
            $latestRateF = (float) ($latestRow->rate ?? 0);
            $delta = $latestRateF - $prevRateF;

            $lastChangeDelta = $delta;
            $lastChangeDir = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'same');
            
            try {
                $lastChangeDate = \Illuminate\Support\Carbon::parse($latestRow->effective_date);
            } catch (\Throwable $e) {
                $lastChangeDate = null;
            }
        }

        // === LONGEST STREAK CALCULATIONS ===
        // Find longest consecutive UP streak
        $longestUpLen = 0;
        $longestUpStartIdx = null;
        $longestUpEndIdx = null;
        
        // Find longest consecutive DOWN streak
        $longestDownLen = 0;
        $longestDownStartIdx = null;
        $longestDownEndIdx = null;

        $curUpLen = 0;
        $curUpStartIdx = null;
        $curDownLen = 0;
        $curDownStartIdx = null;

        // Loop through all rates comparing each to the previous
        for ($i = 1; $i < $sorted->count(); $i++) {
            $prev = (float) ($sorted[$i - 1]->rate ?? 0);
            $cur  = (float) ($sorted[$i]->rate ?? 0);
            $dir = $cur <=> $prev; // 1 = up, 0 = same, -1 = down

            if ($dir === 1) {
                // Rate went up
                if ($curUpLen === 0) {
                    $curUpStartIdx = $i - 1;
                }
                $curUpLen++;
                
                // Reset down streak
                $curDownLen = 0;
                $curDownStartIdx = null;
                
                // Check if this is the longest up streak
                if ($curUpLen > $longestUpLen) {
                    $longestUpLen = $curUpLen;
                    $longestUpStartIdx = $curUpStartIdx;
                    $longestUpEndIdx = $i;
                }
            } elseif ($dir === -1) {
                // Rate went down
                if ($curDownLen === 0) {
                    $curDownStartIdx = $i - 1;
                }
                $curDownLen++;
                
                // Reset up streak
                $curUpLen = 0;
                $curUpStartIdx = null;
                
                // Check if this is the longest down streak
                if ($curDownLen > $longestDownLen) {
                    $longestDownLen = $curDownLen;
                    $longestDownStartIdx = $curDownStartIdx;
                    $longestDownEndIdx = $i;
                }
            } else {
                // Rate stayed the same - reset both streaks
                $curUpLen = 0;
                $curUpStartIdx = null;
                $curDownLen = 0;
                $curDownStartIdx = null;
            }
        }

        // Format longest UP streak dates for display
        $longestUpStartLabel = $longestUpEndLabel = null;
        if ($longestUpLen > 0 && $longestUpStartIdx !== null && $longestUpEndIdx !== null) {
            try {
                $longestUpStartLabel = \Illuminate\Support\Carbon::parse($sorted[$longestUpStartIdx]->effective_date)->format('d-m-Y');
                $longestUpEndLabel   = \Illuminate\Support\Carbon::parse($sorted[$longestUpEndIdx]->effective_date)->format('d-m-Y');
            } catch (\Throwable $e) {
                $longestUpStartLabel = (string) $sorted[$longestUpStartIdx]->effective_date;
                $longestUpEndLabel   = (string) $sorted[$longestUpEndIdx]->effective_date;
            }
        }

        // Format longest DOWN streak dates for display
        $longestDownStartLabel = $longestDownEndLabel = null;
        if ($longestDownLen > 0 && $longestDownStartIdx !== null && $longestDownEndIdx !== null) {
            try {
                $longestDownStartLabel = \Illuminate\Support\Carbon::parse($sorted[$longestDownStartIdx]->effective_date)->format('d-m-Y');
                $longestDownEndLabel   = \Illuminate\Support\Carbon::parse($sorted[$longestDownEndIdx]->effective_date)->format('d-m-Y');
            } catch (\Throwable $e) {
                $longestDownStartLabel = (string) $sorted[$longestDownStartIdx]->effective_date;
                $longestDownEndLabel   = (string) $sorted[$longestDownEndIdx]->effective_date;
            }
        }

        // Total movements (excluding first entry and any flats)
        $totalMoves = collect($movementByDate)->filter(fn($m) => $m !== null && $m !== 0)->count();
    @endphp

    {{-- SUMMARY CARDS: Key statistics at a glance --}}
    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Highest rate card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Highest recorded</div>
            <div class="mt-1 text-2xl font-semibold">
                {{ number_format((float) $maxRate, 2) }}%
            </div>
            @if($maxRow)
                <div class="text-sm text-gray-600">
                    on {{ \Illuminate\Support\Carbon::parse($maxRow->effective_date)->format('d-m-Y') }}
                </div>
            @endif
        </div>

        {{-- Lowest rate card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Lowest recorded</div>
            <div class="mt-1 text-2xl font-semibold">
                {{ number_format((float) $minRate, 2) }}%
            </div>
            @if($minRow)
                <div class="text-sm text-gray-600">
                    on {{ \Illuminate\Support\Carbon::parse($minRow->effective_date)->format('d-m-Y') }}
                </div>
            @endif
        </div>

        {{-- Last change card with color coding (red=up, green=down) --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Last change</div>
            @if(!is_null($lastChangeDir) && !is_null($lastChangeDelta))
                <div class="mt-1 text-2xl font-semibold {{ $lastChangeDir === 'up' ? 'text-red-500' : ($lastChangeDir === 'down' ? 'text-lime-600' : 'text-zinc-900') }}">
                    @if($lastChangeDir === 'up')
                        +{{ number_format(abs($lastChangeDelta), 2) }}%
                    @elseif($lastChangeDir === 'down')
                        -{{ number_format(abs($lastChangeDelta), 2) }}%
                    @else
                        {{ number_format(0, 2) }}%
                    @endif
                </div>
                
                @if($lastChangeDate)
                    <div class="text-sm text-gray-600">
                        on {{ $lastChangeDate->format('d-m-Y') }}
                    </div>
                @endif
            @else
                <div class="mt-1 text-sm text-gray-600">—</div>
            @endif
        </div>
    </section>

    {{-- MOVEMENT STATISTICS: Summary of all rate changes and streaks --}}
    <section class="mb-6">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-gray-800">
                <p>
                    Since records began there have been <span class="font-semibold">{{ number_format($totalMoves) }}</span> rate movements —
                    <span class="text-rose-700 font-medium">{{ number_format($upCount) }} up</span>
                    and <span class="text-emerald-700 font-medium">{{ number_format($downCount) }} down</span>.
                </p>
                
                {{-- Longest upward streak --}}
                <p class="mt-1">
                    @if($longestUpLen > 0 && $longestUpStartLabel && $longestUpEndLabel)
                        The longest upward streak was between <span class="font-medium">{{ $longestUpStartLabel }}</span> 
                        and <span class="font-medium">{{ $longestUpEndLabel }}</span>
                        when it increased <span class="font-medium">{{ $longestUpLen }}</span> times.
                    @else
                        No upward streaks found.
                    @endif
                </p>
                
                {{-- Longest downward streak --}}
                <p class="mt-1">
                    @if($longestDownLen > 0 && $longestDownStartLabel && $longestDownEndLabel)
                        The longest downward streak was between <span class="font-medium">{{ $longestDownStartLabel }}</span> 
                        and <span class="font-medium">{{ $longestDownEndLabel }}</span>
                        when it went down <span class="font-medium">{{ $longestDownLen }}</span> times.
                    @else
                        No downward streaks found.
                    @endif
                </p>
            </div>
        </div>
    </section>
    @endif

    {{-- DATA TABLE: Complete historical Bank Rate data --}}
    <div class="overflow-hidden border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Effective date</th>
                        <th class="border-b border-gray-200 px-4 py-2">Rate (%)</th>
                        <th class="border-b border-gray-200 px-4 py-2">Movement</th>
                    </tr>
                </thead>
                
                <tbody>
                    @forelse($rates->sortByDesc('effective_date') as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ \Illuminate\Support\Carbon::parse($r->effective_date)->format('d-m-Y') }}
                            </td>
                            
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                {{ number_format((float)$r->rate, 2) }}
                            </td>
                            
                            {{-- Movement badge: Up (red), Down (green), or No change --}}
                            @php
                                $dateKey = \Illuminate\Support\Carbon::parse($r->effective_date)->format('Y-m-d');
                                $mv = $movementByDate[$dateKey] ?? null; // 1 up, 0 same, -1 down, null first
                            @endphp
                            
                            <td class="border-b border-gray-100 px-4 py-2">
                                @if($mv === 1)
                                    <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
                                        Up
                                    </span>
                                @elseif($mv === -1)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                        Down
                                    </span>
                                @elseif($mv === 0)
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700">
                                        No change
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                                No data to display.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart.js library for rendering the Bank Rate chart --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function() {
    // Data from Laravel controller passed as JSON
    const labels = @json($labels);
    const data   = @json($values);

    const el = document.getElementById('ratesChart');
    if (!el) return;

    const ctx = el.getContext('2d');
    
    // Destroy existing chart instance if it exists (prevents memory leaks)
    if (window._ratesChart) {
        window._ratesChart.destroy();
    }
    
    // Set canvas height to match parent container
    if (el.parentElement) {
        el.height = el.parentElement.clientHeight;
    }

    // Create new Chart.js instance
    window._ratesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Bank Rate (%)',
                data: data,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: false,
                tension: 0.1,          // Slight curve to the line
                pointRadius: 0,        // Hide points by default
                pointHoverRadius: 4,   // Show point on hover
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            resizeDelay: 150,
            maintainAspectRatio: false, // Fill the fixed-height parent container
            animation: false,
            plugins: {
                legend: { 
                    display: false     // Hide legend since we only have one dataset
                },
                decimation: {
                    enabled: true,     // Optimize performance for large datasets
                    algorithm: 'min-max'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        // Show full date in tooltip title
                        title: function(items) {
                            return items && items.length ? items[0].label : '';
                        },
                        // Format rate value with 2 decimal places and % symbol
                        label: function(ctx) {
                            const v = (ctx.parsed.y ?? 0).toFixed(2);
                            return ` ${v}%`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 14,
                        // Display only year (first 4 characters) to reduce clutter
                        callback: function(value, index, ticks) {
                            const scale = this;
                            const raw = (scale.getLabelForValue ? scale.getLabelForValue(value) : (labels[index] ?? value));
                            return String(raw).slice(0, 4); // YYYY
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    min: 0,
                    grace: '5%',       // Add 5% padding above/below data range
                    ticks: {
                        // Format y-axis labels with 2 decimal places
                        callback: (v) => (v && v.toFixed) ? v.toFixed(2) : v
                    }
                }
            }
        }
    });
})();
</script>
@endsection
