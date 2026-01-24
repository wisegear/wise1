@extends('layouts.app')

@section('title', 'Mortgage arrears — MLAR')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    {{-- HERO SECTION: Overview of mortgage arrears data --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">
                Mortgage arrears (MLAR)
            </h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                Number of mortgage accounts in arrears as a percentage of all regulated mortgage accounts, split by arrears band.
            </p>
            
            {{-- Display latest data period --}}
            @if($latest)
                <p class="mt-3 text-sm text-gray-600">
                    <span class="font-medium">Latest data:</span> {{ $latest->year }} {{ $latest->quarter }}
                </p>
            @endif
        </div>

        {{-- Hero image --}}
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/arrears.svg') }}" 
                 alt="Mortgage Arrears" 
                 class="w-64 h-auto">
        </div>
    </section>

    {{-- MAIN CONTENT: Explainer, latest snapshot, and chart --}}
    <div class="grid md:grid-cols-[2fr,3fr] gap-6">
        
        {{-- COLLAPSIBLE EXPLAINER: How to read this indicator --}}
        <div class="border border-zinc-200 rounded-lg bg-white">
            <button type="button" 
                    class="w-full flex items-center justify-between px-4 py-3 text-left" 
                    onclick="document.getElementById('arrearsExplainer').classList.toggle('hidden'); this.querySelector('[data-chevron]').classList.toggle('rotate-180');">
                <span class="text-sm font-semibold text-zinc-800">How to read this indicator</span>
                <svg data-chevron 
                     xmlns="http://www.w3.org/2000/svg" 
                     viewBox="0 0 20 20" 
                     fill="currentColor" 
                     class="h-4 w-4 text-zinc-500 transition-transform duration-150">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.186l3.71-3.955a.75.75 0 111.08 1.04l-4.25 4.53a.75.75 0 01-1.08 0l-4.25-4.53a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <div id="arrearsExplainer" class="px-4 pb-4 pt-1 space-y-2 border-t border-zinc-100">
                <p class="text-sm text-zinc-600">
                    Each arrears band shows the share of outstanding mortgage accounts where the borrower is behind on
                    payments by that amount, as a percentage of all regulated mortgage accounts.
                </p>
                <ul class="mt-2 space-y-1 text-sm text-zinc-600 list-disc list-inside">
                    <li>Values are in <span class="font-medium">per cent of all loans</span>, not number of accounts.</li>
                    <li>Higher values in the <span class="font-medium">10%+ arrears</span> band point to more severe stress.</li>
                    <li>Changes over time matter more than any one quarter in isolation.</li>
                </ul>
            </div>
        </div>

        {{-- LATEST QUARTER SNAPSHOT & CHART --}}
        <div class="space-y-4">
            {{-- Latest quarter snapshot cards --}}
            @if($latestValues && $latestValues->count())
                <div class="border border-zinc-200 rounded-lg bg-white p-4">
                    <div class="flex items-baseline justify-between gap-4 mb-3">
                        <h2 class="text-sm font-semibold text-zinc-800">
                            Latest quarter snapshot
                        </h2>
                        <p class="text-xs text-zinc-500">
                            {{ $latest->year }} {{ $latest->quarter }}
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($latestValues as $row)
                            <div class="border border-zinc-200 rounded-md px-3 py-2 bg-zinc-50">
                                <p class="text-xs font-medium text-zinc-600">
                                    {{ $row->description }}
                                </p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">
                                    {{ number_format($row->value, 3) }}%
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Arrears over time chart --}}
            <div class="border border-zinc-200 rounded-lg bg-white p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-zinc-800">Arrears over time - <span class="text-zinc-400 text-sm font-normal">Hover over lines to see more data.</span></h2>
                    @if($periods && $periods->count())
                        <p class="text-xs text-zinc-500">
                            {{ $periods->first() }} — {{ $periods->last() }}
                        </p>
                    @endif
                </div>
                
                <div class="overflow-x-auto">
                    <div class="min-w-[640px] h-72 sm:h-80">
                        <canvas id="arrearsChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FULL HISTORY: Collapsible tables showing complete time series --}}
    <div class="border border-zinc-200 rounded-lg bg-white p-4">
        <button type="button"
                class="w-full flex items-center justify-between text-left gap-4"
                onclick="document.getElementById('arrearsHistoryPanel').classList.toggle('hidden'); this.querySelector('[data-chevron-history]').classList.toggle('rotate-180');">
            <div>
                <h2 class="text-base font-semibold text-zinc-800">
                    Arrears history by band
                </h2>
                <p class="mt-1 text-sm text-zinc-600">
                    Expand to see the full quarterly time series for each arrears band.
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                @if($periods && $periods->count())
                    <p class="text-xs text-zinc-500">
                        Coverage: {{ $periods->first() }} — {{ $periods->last() }}
                    </p>
                @endif
                <svg data-chevron-history 
                     xmlns="http://www.w3.org/2000/svg" 
                     viewBox="0 0 20 20" 
                     fill="currentColor" 
                     class="h-4 w-4 text-zinc-500 transition-transform duration-150 rotate-180">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.186l3.71-3.955a.75.75 0 111.08 1.04l-4.25 4.53a.75.75 0 01-1.08 0l-4.25-4.53a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </div>
        </button>

        <div id="arrearsHistoryPanel" class="mt-4 space-y-4 hidden">
            <p class="text-sm text-zinc-600">
                The tables below show the full time series for each arrears band. Values are quarterly and expressed as
                a percentage of all regulated mortgage accounts.
            </p>

            <div class="space-y-6">
                @foreach($seriesByBand as $bandKey => $series)
                    @php
                        /** @var \Illuminate\Support\Collection $series */
                        $bandDescription = $bands[$bandKey]->description ?? $bandKey;
                        $isRepossession = \Illuminate\Support\Str::contains(strtolower($bandDescription), 'repossess');
                    @endphp

                    {{-- Skip repossession data --}}
                    @if($isRepossession)
                        @continue
                    @endif

                    <div class="border border-zinc-200 rounded-lg bg-zinc-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-zinc-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-800">
                                {{ $bandDescription }}
                            </h3>
                            <p class="text-xs text-zinc-500">
                                {{ $series->first()->year }} {{ $series->first()->quarter }}
                                —
                                {{ $series->last()->year }} {{ $series->last()->quarter }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <thead class="bg-zinc-50 border-b border-zinc-100">
                                    <tr>
                                        <th class="px-4 py-2 font-medium text-zinc-600">Period</th>
                                        <th class="px-4 py-2 font-medium text-zinc-600">Arrears (% of loans)</th>
                                    </tr>
                                </thead>
                                
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach($series as $row)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-zinc-700">
                                                {{ $row->year }} {{ $row->quarter }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-zinc-900">
                                                {{ number_format($row->value, 3) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- PREPARE CHART DATA --}}
@php
    $chartLabels = $periods ? $periods->values()->all() : [];
    $chartDatasets = [];
@endphp

@foreach($seriesByBand as $bandKey => $series)
    @php
        $bandDescription = $bands[$bandKey]->description ?? $bandKey;
        $isRepossession = \Illuminate\Support\Str::contains(strtolower($bandDescription), 'repossess');
    @endphp

    {{-- Skip repossession data from chart --}}
    @if($isRepossession)
        @continue
    @endif

    @php
        // Map data points to labels
        $map = [];
        foreach ($series as $row) {
            $map[$row->year . ' ' . $row->quarter] = $row->value;
        }
        
        // Build data array matching chart labels
        $dataPoints = [];
        foreach ($chartLabels as $label) {
            $dataPoints[] = $map[$label] ?? null;
        }
        
        // Add to datasets array
        $chartDatasets[] = [
            'label' => $bandDescription,
            'data' => $dataPoints,
        ];
    @endphp
@endforeach

{{-- Chart.js initialization script --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('arrearsChart');
    if (!ctx) return;

    // Data from Laravel controller passed as JSON
    const labels = @json($chartLabels);
    const rawDatasets = @json($chartDatasets);

    // Validation checks
    if (!labels.length || !rawDatasets.length || typeof Chart === 'undefined') {
        return;
    }

    // Define distinct colors for each arrears band (improved color scheme)
    const colors = [
        { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.1)' },   // Blue - 1.5 < 2.5%
        { border: 'rgb(239, 68, 68)', bg: 'rgba(239, 68, 68, 0.1)' },     // Red - 2.5 < 5%
        { border: 'rgb(249, 115, 22)', bg: 'rgba(249, 115, 22, 0.1)' },   // Orange - 5 < 7.5%
        { border: 'rgb(234, 179, 8)', bg: 'rgba(234, 179, 8, 0.1)' },     // Yellow - 7.5 < 10%
        { border: 'rgb(20, 184, 166)', bg: 'rgba(20, 184, 166, 0.1)' },   // Teal - 10%+
        { border: 'rgb(168, 85, 247)', bg: 'rgba(168, 85, 247, 0.1)' },   // Purple - Possession
    ];

    // Configure datasets with smaller points and distinct colors
    const datasets = rawDatasets.map((series, index) => ({
        label: series.label,
        data: series.data,
        borderColor: colors[index % colors.length].border,
        backgroundColor: colors[index % colors.length].bg,
        borderWidth: 2,
        tension: 0.2,           // Smooth curves
        pointRadius: 1.5,       // SMALLER POINTS - less busy
        pointHoverRadius: 4,    // Larger on hover for interaction
        pointBackgroundColor: colors[index % colors.length].border,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        spanGaps: true,
        fill: false             // No area fill - cleaner look
    }));

    // Create Chart.js instance
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: { size: 11 },
                        padding: 12,
                        usePointStyle: false  // Use boxes instead of point styles
                    },
                },
                tooltip: {
                    callbacks: {
                        // Format tooltip values with 3 decimal places
                        label: function (context) {
                            const value = context.parsed.y;
                            if (value === null || typeof value === 'undefined') return '';
                            return context.dataset.label + ': ' + value.toFixed(3) + '%';
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 20,  // Show more labels for better context
                        callback: function(value, index, ticks) {
                            const label = this.getLabelForValue(value);
                            if (!label) return '';
                            // Show year only, every 2-3 years for cleaner axis
                            const year = label.split(' ')[0];
                            // Show label if it's a new year
                            if (index === 0 || year !== this.getLabelForValue(value - 1).split(' ')[0]) {
                                return year;
                            }
                            return '';
                        }
                    },
                    grid: {
                        display: false  // Cleaner look without vertical grid lines
                    }
                },
                y: {
                    ticks: {
                        callback: function (value) {
                            return value + '%';
                        },
                    },
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'  // Subtle horizontal grid lines
                    }
                },
            },
        },
    });
});
</script>
@endpush
@endsection
