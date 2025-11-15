@extends('layouts.app')

@section('title', 'Mortgage arrears – MLAR')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    {{-- HERO SECTION --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Mortgage arrears (MLAR)</h1>
            <p>Number of mortgage accounts in arrears as a percentage of all regulated mortgage accounts, split by arrears band.</p>
            @if($latest)
                <p class="mt-3 text-sm text-gray-600">
                    <span class="font-medium">Latest data:</span> {{ $latest->year }} {{ $latest->quarter }}
                </p>
            @endif
        </div>

        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/arrears.svg') }}" alt="Wage Growth" class="w-64 h-auto">
        </div>
    </section>

        {{-- Explainer + latest snapshot + chart --}}
        <div class="grid md:grid-cols-[2fr,3fr] gap-6">
            {{-- Collapsible explainer --}}
            <div class="border border-zinc-200 rounded-lg bg-white">
                <button type="button" class="w-full flex items-center justify-between px-4 py-3 text-left" onclick="document.getElementById('arrearsExplainer').classList.toggle('hidden'); this.querySelector('[data-chevron]').classList.toggle('rotate-180');">
                    <span class="text-sm font-semibold text-zinc-800">How to read this indicator</span>
                    <svg data-chevron xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-zinc-500 transition-transform duration-150">
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

            {{-- Latest quarter snapshot + chart --}}
            <div class="space-y-4">
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

                <div class="border border-zinc-200 rounded-lg bg-white p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-zinc-800">Arrears over time</h2>
                        @if($periods && $periods->count())
                            <p class="text-xs text-zinc-500">
                                {{ $periods->first() }} – {{ $periods->last() }}
                            </p>
                        @endif
                    </div>
                    <div class="h-80">
                        <canvas id="arrearsChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Full history --}}
        <div class="border border-zinc-200 rounded-lg bg-white p-4 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-base font-semibold text-zinc-800">
                    Arrears history by band
                </h2>
                @if($periods && $periods->count())
                    <p class="text-xs text-zinc-500">
                        Coverage: {{ $periods->first() }} – {{ $periods->last() }}
                    </p>
                @endif
            </div>

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
                                –
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

    @php
        $chartLabels = $periods ? $periods->values()->all() : [];
        $chartDatasets = [];
    @endphp

    @foreach($seriesByBand as $bandKey => $series)
        @php
            $bandDescription = $bands[$bandKey]->description ?? $bandKey;
            $isRepossession = \Illuminate\Support\Str::contains(strtolower($bandDescription), 'repossess');
        @endphp

        @if($isRepossession)
            @continue
        @endif

        @php
            $map = [];
            foreach ($series as $row) {
                $map[$row->year . ' ' . $row->quarter] = $row->value;
            }
            $dataPoints = [];
            foreach ($chartLabels as $label) {
                $dataPoints[] = $map[$label] ?? null;
            }
            $chartDatasets[] = [
                'label' => $bandDescription,
                'data' => $dataPoints,
            ];
        @endphp
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('arrearsChart');
                if (!ctx) return;

                const labels = @json($chartLabels);
                const rawDatasets = @json($chartDatasets);

                if (!labels.length || !rawDatasets.length || typeof Chart === 'undefined') {
                    return;
                }

                const datasets = rawDatasets.map((series, index) => ({
                    label: series.label,
                    data: series.data,
                    borderWidth: 1.5,
                    tension: 0.25,
                    spanGaps: true,
                }));

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
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10 },
                                },
                            },
                            tooltip: {
                                callbacks: {
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
                                    callback: function(value, index, ticks) {
                                        const label = this.getLabelForValue(value);
                                        if (!label) return '';
                                        return label.split(' ')[0]; // Show only the year
                                    }
                                },
                            },
                            y: {
                                ticks: {
                                    callback: function (value) {
                                        return value + '%';
                                    },
                                },
                                beginAtZero: true,
                            },
                        },
                    },
                });
            });
        </script>
    @endpush
@endsection