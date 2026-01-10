@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- HERO SECTION: Displays latest wage growth statistics and trends --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">UK Wage Growth</h1>

            {{-- Latest single-month year-over-year wage growth --}}
            @if($latest)
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest (single‑month YoY):
                    <span class="font-semibold">{{ number_format($latest->single_month_yoy, 1) }}%</span>
                    <span class="text-gray-600">for</span>
                    <span class="font-medium">{{ $latest->date->format('M Y') }}</span>.
                </p>
            @endif

            {{-- Latest 3-month average year-over-year wage growth --}}
            @if($latest && $latest->three_month_avg_yoy !== null)
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Latest (3‑month average YoY):
                    <span class="font-semibold">{{ number_format($latest->three_month_avg_yoy, 1) }}%</span>
                </p>
            @endif

            {{-- Month-on-month change calculations and display --}}
            @if($previous)
                @php
                    // Calculate deltas between latest and previous month
                    $delta_single = $latest->single_month_yoy - $previous->single_month_yoy;
                    $delta_three = $latest->three_month_avg_yoy - $previous->three_month_avg_yoy;
                @endphp

                {{-- Single-month change with color coding (green=up, red=down) --}}
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Month‑on‑month (single‑month):
                    <span class="font-semibold {{ $delta_single > 0 ? 'text-emerald-700' : ($delta_single < 0 ? 'text-red-700' : 'text-zinc-900') }}">
                        @if($delta_single > 0)
                            +{{ number_format(abs($delta_single), 1) }}%
                        @elseif($delta_single < 0)
                            -{{ number_format(abs($delta_single), 1) }}%
                        @else
                            No change
                        @endif
                    </span>
                </p>

                {{-- 3-month average change with color coding --}}
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Month‑on‑month (3‑month average):
                    <span class="font-semibold {{ $delta_three > 0 ? 'text-emerald-700' : ($delta_three < 0 ? 'text-red-700' : 'text-zinc-900') }}">
                        @if($delta_three > 0)
                            +{{ number_format(abs($delta_three), 1) }}%
                        @elseif($delta_three < 0)
                            -{{ number_format(abs($delta_three), 1) }}%
                        @else
                            No change
                        @endif
                    </span>
                </p>
            @endif
        </div>

        {{-- Hero image --}}
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/wages.svg') }}" 
                 alt="Wage Growth" 
                 class="w-64 h-auto">
        </div>
    </section>

    {{-- MAIN CHART: Visual comparison of single-month vs 3-month average wage growth --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">
                Wage Growth — Single Month vs 3‑Month Average
            </div>
            <div class="h-96">
                <canvas id="wageChart"></canvas>
            </div>
        </div>
    </section>

    {{-- BONUS EFFECT EXPLANATION: Collapsible panel explaining how to interpret the data --}}
    <section class="mb-6">
        <details class="group rounded-lg border border-amber-200 bg-amber-50 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-amber-900 flex items-center justify-between">
                How to read the "Bonus Effect"
                <span class="text-xs text-amber-700 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-600 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            
            <div class="px-5 pb-5 pt-3 text-sm text-zinc-800">
                <p>
                    The Bonus Effect compares the latest <span class="font-semibold">single-month wage growth</span> with the
                    <span class="font-semibold">3-month average</span>. A <span class="font-semibold">positive</span> value (shown in green)
                    means the most recent month is running hotter than the recent trend — often when bonuses are stronger.
                    A <span class="font-semibold">negative</span> value (shown in red) means the latest month is weaker than the trend,
                    typically when bonuses are being cut or frozen.
                </p>
                
                <p class="mt-2">
                    Large negative dips in the Bonus Effect and in overall wage growth tend to line up with major economic shocks
                    where employers pull back on variable pay:
                </p>
                
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    <li>
                        <span class="font-medium">2008‑2009 — Global financial crisis:</span>
                        bank failures, credit tightening and a deep recession saw significant cuts to banking and financial-sector bonuses.
                    </li>
                    <li>
                        <span class="font-medium">2020‑2021 — COVID‑19 shock:</span>
                        lockdowns, furlough schemes and widespread uncertainty led many employers to freeze or reduce bonuses even where
                        base pay held up.
                    </li>
                    <li>
                        <span class="font-medium">Other dips:</span>
                        smaller negative moves can reflect sector-specific slowdowns, hiring freezes or one-off shocks that reduce
                        variable pay before they show up more broadly in unemployment.
                    </li>
                </ul>
                
                <p class="mt-2 text-xs text-amber-900">
                    In short: sustained negatives in the Bonus Effect panel are an early sign that employers are turning cautious,
                    which can feed through to housing demand and mortgage affordability with a lag.
                </p>
            </div>
        </details>
    </section>

    {{-- SUMMARY CARDS: Key statistics at a glance --}}
    @php
        // Find maximum and minimum values across all data
        $max_single = $all->max('single_month_yoy');
        $min_single = $all->min('single_month_yoy');
        $max_three  = $all->max('three_month_avg_yoy');
        $min_three  = $all->min('three_month_avg_yoy');
        
        // Get the rows containing these values for date display
        $max_single_row = $all->firstWhere('single_month_yoy', $max_single);
        $min_single_row = $all->firstWhere('single_month_yoy', $min_single);
        $max_three_row  = $all->firstWhere('three_month_avg_yoy', $max_three);
        $min_three_row  = $all->firstWhere('three_month_avg_yoy', $min_three);
    @endphp

    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Highest single-month growth card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Highest Single‑Month YoY</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($max_single, 1) }}%</div>
            <div class="text-sm text-gray-600">in {{ $max_single_row->date->format('M Y') }}</div>
        </div>

        {{-- Highest 3-month average growth card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Highest 3‑Month Avg YoY</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($max_three, 1) }}%</div>
            <div class="text-sm text-gray-600">in {{ $max_three_row->date->format('M Y') }}</div>
        </div>

        {{-- Bonus Effect card: difference between single-month and 3-month average --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Bonus Effect (Latest)</div>
            @php 
                $bonus_gap = $latest->single_month_yoy - $latest->three_month_avg_yoy; 
            @endphp
            <div class="mt-1 text-2xl font-semibold {{ $bonus_gap > 0 ? 'text-emerald-700' : ($bonus_gap < 0 ? 'text-red-700' : 'text-zinc-900') }}">
                {{ number_format($bonus_gap, 1) }}%
            </div>
            <div class="text-sm text-gray-600">single-month minus 3-month avg</div>
        </div>
    </section>

    {{-- DATA TABLE: Complete historical wage growth data --}}
    <div class="overflow-hidden border-gray-200 bg-white shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Month</th>
                        <th class="border-b border-gray-200 px-4 py-2">Single‑Month YoY (%)</th>
                        <th class="border-b border-gray-200 px-4 py-2">3‑Month Avg YoY (%)</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($all->sortByDesc('date') as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ $row->date->format('M Y') }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                {{ number_format($row->single_month_yoy, 1) }}%
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                {{ number_format($row->three_month_avg_yoy, 1) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                                No data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart.js library for rendering the wage growth chart --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function() {
    // Data from Laravel controller passed as JSON
    const labels = @json($labels);
    const single = @json($values_single);
    const three  = @json($values_three);

    const canvas = document.getElementById('wageChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Destroy existing chart instance if it exists (prevents memory leaks)
    if (window._wageChart) {
        window._wageChart.destroy();
    }
    
    // Set canvas height to match parent container
    if (canvas.parentElement) {
        canvas.height = canvas.parentElement.clientHeight;
    }

    // Create new Chart.js instance
    window._wageChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Single-month YoY %',
                    data: single,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.15)',
                    tension: 0.15,
                    pointRadius: 0,
                    borderWidth: 2,
                    spanGaps: true
                },
                {
                    label: '3-month avg YoY %',
                    data: three,
                    borderColor: 'rgba(30, 30, 30, 1)',
                    backgroundColor: 'rgba(30, 30, 30, 0.20)',
                    tension: 0.15,
                    pointRadius: 0,
                    borderWidth: 2,
                    spanGaps: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { 
                    display: true 
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        // Format tooltip values to show percentage with 1 decimal place
                        label: function(ctx) {
                            const v = ctx.parsed.y;
                            if (v === null || v === undefined) {
                                return ' n/a';
                            }
                            return ' ' + v.toFixed(1) + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        // Display only year (first 4 characters) to reduce clutter
                        callback: function(value, index) {
                            const raw = this.getLabelForValue(value);
                            return raw.slice(0, 4);
                        },
                        maxTicksLimit: 14
                    }
                },
                y: {
                    beginAtZero: false,
                    grace: '5%' // Add 5% padding above/below data range
                }
            }
        }
    });
})();
</script>
@endsection