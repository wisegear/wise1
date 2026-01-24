@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- PREPARE DATA: Extract series data --}}
    @php
        $hp = $seriesData['LPMVTVX'] ?? null;   // House purchase
        $re = $seriesData['LPMB4B3'] ?? null;   // Remortgaging
        $tt = $seriesData['LPMB3C8'] ?? null;   // Total approvals
        $os = $seriesData['LPMB4B4'] ?? null;   // Other secured

        // Helper function to format dates consistently
        $formatMonth = function($d) {
            try {
                return \Illuminate\Support\Carbon::parse($d)->isoFormat('MMM YYYY');
            } catch (\Throwable $e) {
                return (string) $d;
            }
        };
    @endphp

    {{-- HERO SECTION: Latest mortgage approvals statistics --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">
                Mortgage Approvals
            </h1>
            
            {{-- Display latest month --}}
            @if(!empty($latestPeriod))
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest month: <span class="font-medium">{{ $formatMonth($latestPeriod) }}</span>
                </p>
            @endif

            {{-- Grid showing all approval categories with month-on-month changes --}}
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                {{-- House purchase approvals --}}
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">House purchase</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($hp && $hp['latest'])
                            {{ number_format((int) $hp['latest']->value) }}
                            {{-- Show month-on-month change with color coding --}}
                            @if(!is_null($hp['delta']))
                                <span class="{{ $hp['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $hp['delta'] >= 0 ? '+' : '' }}{{ number_format($hp['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>

                {{-- Remortgaging approvals --}}
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Remortgaging</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($re && $re['latest'])
                            {{ number_format((int) $re['latest']->value) }}
                            @if(!is_null($re['delta']))
                                <span class="{{ $re['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $re['delta'] >= 0 ? '+' : '' }}{{ number_format($re['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>

                {{-- Other secured lending --}}
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Other secured</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($os && $os['latest'])
                            {{ number_format((int) $os['latest']->value) }}
                            @if(!is_null($os['delta']))
                                <span class="{{ $os['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $os['delta'] >= 0 ? '+' : '' }}{{ number_format($os['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>

                {{-- Total approvals --}}
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total approvals</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($tt && $tt['latest'])
                            {{ number_format((int) $tt['latest']->value) }}
                            @if(!is_null($tt['delta']))
                                <span class="{{ $tt['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $tt['delta'] >= 0 ? '+' : '' }}{{ number_format($tt['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            {{-- Explanation of "Other secured" category --}}
            <p class="mt-4 text-sm text-zinc-500">
                Other secured - This refers to anything not a purchase or remortgage. These could be further advances from the same lender, 
                a 2nd charge loan, debt consolidation, internal refinancing with the same lender or something else.
            </p>
        </div>

        {{-- Hero image --}}
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/approvals.svg') }}" 
                 alt="Mortgage approvals" 
                 class="w-64 h-auto">
        </div>
    </section>

    {{-- PREPARE CHART DATA --}}
    @php
        // Main chart data (all time periods)
        $labels     = $tt['labels'] ?? collect();
        $dataTotal  = $tt['values'] ?? collect();
        $dataHP     = $hp['values'] ?? collect();
        $dataRe     = $re['values'] ?? collect();
        $dataOther  = $os['values'] ?? collect();

        // Labels per series (used for 24-month charts)
        $labelsHP = $hp['labels'] ?? collect();
        $labelsRe = $re['labels'] ?? collect();

        // Extract last 24 months for detailed charts
        $hpLabels24 = collect($labelsHP)->take(-24)->values();
        $hpData24   = collect($dataHP)->take(-24)->values();
        $reLabels24 = collect($labelsRe)->take(-24)->values();
        $reData24   = collect($dataRe)->take(-24)->values();
    @endphp

    {{-- MAIN CHART: Combined approvals over time --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">
                Approvals over time
            </div>
            
            @if(($labels instanceof \Illuminate\Support\Collection ? $labels->isEmpty() : empty($labels)))
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="approvalsChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- 24-MONTH DETAILED CHARTS: House purchase & Remortgaging side-by-side --}}
    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        {{-- House purchase 24-month chart --}}
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">
                Mortgage Approvals over the last 24 months
            </div>
            
            @if(($hpLabels24 instanceof \Illuminate\Support\Collection ? $hpLabels24->isEmpty() : empty($hpLabels24)))
                <p class="text-sm text-gray-500">No recent data available.</p>
            @else
                <div class="h-72">
                    <canvas id="hpChart"></canvas>
                </div>
            @endif
        </div>

        {{-- Remortgaging 24-month chart --}}
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">
                Remortgaging over the last 24 months
            </div>
            
            @if(($reLabels24 instanceof \Illuminate\Support\Collection ? $reLabels24->isEmpty() : empty($reLabels24)))
                <p class="text-sm text-gray-500">No recent data available.</p>
            @else
                <div class="h-72">
                    <canvas id="reChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- YEARLY TOTALS TABLE: Historical data by year --}}
    <div class="overflow-hidden bg-white shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Year</th>
                        <th class="border-b border-gray-200 px-4 py-2">House purchase</th>
                        <th class="border-b border-gray-200 px-4 py-2">Remortgaging</th>
                        <th class="border-b border-gray-200 px-4 py-2">Other secured</th>
                        <th class="border-b border-gray-200 px-4 py-2">Total approvals</th>
                    </tr>
                </thead>
                
                <tbody>
                    @forelse(($yearTable ?? []) as $y)
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                {{ $y['year'] }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMVTVX']) ? number_format((int)$y['LPMVTVX']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB4B3']) ? number_format((int)$y['LPMB4B3']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB4B4']) ? number_format((int)$y['LPMB4B4']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB3C8']) ? number_format((int)$y['LPMB3C8']) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                No yearly data to display.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart.js library for rendering mortgage approval charts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function() {
    // Data from Laravel controller passed as JSON
    const labels = @json($labels ?? []);
    const total  = @json($dataTotal ?? []);
    const hp     = @json($dataHP ?? []);
    const re     = @json($dataRe ?? []);
    const other  = @json($dataOther ?? []);

    // Last 24 months data (per series)
    const hpLabels24 = @json($hpLabels24 ?? []);
    const hp24       = @json($hpData24 ?? []);
    const reLabels24 = @json($reLabels24 ?? []);
    const re24       = @json($reData24 ?? []);

    // === MAIN COMBINED CHART (All time periods) ===
    const el = document.getElementById('approvalsChart');
    if (!el) return;
    
    const ctx = el.getContext('2d');
    
    // Destroy existing chart instance if it exists (prevents memory leaks)
    if (window._approvalsChart) {
        window._approvalsChart.destroy();
    }
    
    // Set canvas height to match parent container
    if (el.parentElement) {
        el.height = el.parentElement.clientHeight;
    }

    // Create main combined chart
    window._approvalsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total',
                    data: total,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.15)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 0,      // No points on main chart
                    fill: false
                },
                {
                    label: 'House purchase',
                    data: hp,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,      // No points on main chart
                    fill: false
                },
                {
                    label: 'Remortgaging',
                    data: re,
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,      // No points on main chart
                    fill: false
                },
                {
                    label: 'Other secured',
                    data: other,
                    borderColor: 'rgb(234, 179, 8)',
                    backgroundColor: 'rgba(234, 179, 8, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,      // No points on main chart
                    fill: false
                },
            ]
        },
        options: {
            responsive: true,
            resizeDelay: 150,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                decimation: {
                    enabled: true,
                    algorithm: 'min-max'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: (items) => items && items.length ? items[0].label : '',
                        label: (ctx) => {
                            const v = ctx.parsed.y ?? 0;
                            return ` ${ctx.dataset.label}: ${Number(v).toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    offset: false,
                    ticks: {
                        autoSkip: true,
                        includeBounds: true,
                        maxTicksLimit: 14,
                        // Show only year on x-axis (labels are "YYYY-MM")
                        callback: function(value, index) {
                            const scale = this;
                            const raw = (scale.getLabelForValue ? scale.getLabelForValue(value) : (labels[index] ?? value));
                            return String(raw).slice(0, 4);
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '5%',
                    ticks: {
                        callback: (v) => {
                            const n = Number(v);
                            return isFinite(n) ? n.toLocaleString() : v;
                        }
                    }
                }
            }
        }
    });

    // === HOUSE PURCHASE 24-MONTH CHART ===
    const hpEl = document.getElementById('hpChart');
    if (hpEl && hpLabels24.length && hp24.length) {
        const hpCtx = hpEl.getContext('2d');
        
        if (window._hpChart) {
            window._hpChart.destroy();
        }
        
        if (hpEl.parentElement) {
            hpEl.height = hpEl.parentElement.clientHeight;
        }

        // Format labels for display: "Jan 24", "Feb 24", etc.
        const hpFormattedLabels = hpLabels24.map(label => {
            const [year, month] = String(label).split('-');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthIndex = parseInt(month, 10) - 1;
            const shortYear = year ? year.slice(-2) : '';
            return monthNames[monthIndex] ? `${monthNames[monthIndex]} ${shortYear}` : label;
        });

        window._hpChart = new Chart(hpCtx, {
            type: 'line',
            data: {
                labels: hpFormattedLabels,
                datasets: [{
                    label: 'House purchase',
                    data: hp24,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.10)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 3,              // Show points on 24-month chart
                    pointHoverRadius: 5,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    decimation: { enabled: true, algorithm: 'min-max' }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,     // Angle labels for better fit
                            minRotation: 45,
                            maxTicksLimit: 12    // Show max 12 labels
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '5%',
                        ticks: {
                            callback: (v) => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    }

    // === REMORTGAGING 24-MONTH CHART ===
    const reEl = document.getElementById('reChart');
    if (reEl && reLabels24.length && re24.length) {
        const reCtx = reEl.getContext('2d');
        
        if (window._reChart) {
            window._reChart.destroy();
        }
        
        if (reEl.parentElement) {
            reEl.height = reEl.parentElement.clientHeight;
        }

        // Format labels for display: "Jan 24", "Feb 24", etc.
        const reFormattedLabels = reLabels24.map(label => {
            const [year, month] = String(label).split('-');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthIndex = parseInt(month, 10) - 1;
            const shortYear = year ? year.slice(-2) : '';
            return monthNames[monthIndex] ? `${monthNames[monthIndex]} ${shortYear}` : label;
        });

        window._reChart = new Chart(reCtx, {
            type: 'line',
            data: {
                labels: reFormattedLabels,
                datasets: [{
                    label: 'Remortgaging',
                    data: re24,
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.10)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 3,              // Show points on 24-month chart
                    pointHoverRadius: 5,
                    pointBackgroundColor: 'rgb(139, 92, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    decimation: { enabled: true, algorithm: 'min-max' }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,     // Angle labels for better fit
                            minRotation: 45,
                            maxTicksLimit: 12    // Show max 12 labels
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '5%',
                        ticks: {
                            callback: (v) => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endsection
