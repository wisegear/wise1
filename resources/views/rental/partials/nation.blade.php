@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">{{ $nationName }} Rental Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                <span class="font-semibold">Quarterly rental costs and changes for {{ $nationName }}.</span>
            </p>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                Charts show average rent levels alongside quarter-on-quarter percentage changes.  Information shows average overall and then splits it down into 1,2,3, 
                and 4+ bedroom properties. Alsco covers detached, semi-detached, terraced, and flats.
            </p>
            @if($latestPeriod)
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest data: <span class="font-semibold">{{ $latestPeriod }}</span>
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <a href="{{ route('rental.index') }}" class="inner-button" aria-label="Rental index">
                    <i class="fa-solid fa-house"></i>
                </a>
                @if($nationName !== 'England')
                    <a href="{{ route('rental.england') }}" class="inner-button">
                        England
                    </a>
                @endif
                @if($nationName !== 'Scotland')
                    <a href="{{ route('rental.scotland') }}" class="inner-button">
                        Scotland
                    </a>
                @endif
                @if($nationName !== 'Wales')
                    <a href="{{ route('rental.wales') }}" class="inner-button">
                        Wales
                    </a>
                @endif
                @if($nationName !== 'Northern Ireland')
                    <a href="{{ route('rental.northern-ireland') }}" class="inner-button">
                        Northern Ireland
                    </a>
                @endif
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/rental.jpg') }}" alt="Rental dashboard" class="w-72 h-auto">
        </div>
    </section>

    <h2 class="text-xl font-semibold mt-8">Quarterly Rent Change</h2>
    <p class="mb-4 text-sm text-zinc-700">Rental price line uses pounds; quarterly change line uses percentage.</p>

    @if(isset($seriesByArea[0]))
        <div class="rounded-lg border bg-white p-4 mb-8">
            <div class="mb-2 text-sm text-neutral-600 font-semibold">{{ $seriesByArea[0]['name'] }}</div>
            <div class="h-64">
                <canvas id="rentalChart0" aria-label="{{ $seriesByArea[0]['name'] }} rental change" class="w-full h-full"></canvas>
            </div>
        </div>
    @endif

    <h2 class="text-xl font-semibold mt-8">Quarterly Rental Change by Property Type</h2>
    <p class="mb-4 text-sm text-zinc-700">Series reflect the same quarterly aggregation for each property type.</p>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach($typeSeries as $type)
            <div class="rounded-lg border bg-white p-4">
                <div class="mb-2 text-sm text-neutral-600 font-semibold">{{ $type['label'] }}</div>
                <div class="h-56">
                    <canvas id="rentalTypeChart_{{ $type['key'] }}" aria-label="{{ $type['label'] }} rental change" class="w-full h-full"></canvas>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
(function () {
    try {
        const series = @json($seriesByArea);
        const typeSeries = @json($typeSeries);
        const PRICE = '#2563eb';
        const CHANGE = '#16a34a';

        const renderChart = (el, labels, prices, changes) => {
            if (!el || !labels || !labels.length) return;
            new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Rental price',
                            data: prices,
                            yAxisID: 'y1',
                            borderColor: PRICE,
                            backgroundColor: 'transparent',
                            spanGaps: true,
                            pointRadius: 2,
                            pointHoverRadius: 4,
                            borderWidth: 2,
                            tension: 0.2,
                        },
                        {
                            label: 'Quarterly change',
                            data: changes,
                            yAxisID: 'y',
                            borderColor: CHANGE,
                            backgroundColor: 'transparent',
                            spanGaps: true,
                            pointRadius: 2,
                            pointHoverRadius: 4,
                            borderWidth: 2,
                            tension: 0.2,
                        },
                    ],
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
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                boxHeight: 10,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const dsLabel = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    if (context.dataset.yAxisID === 'y1') {
                                        try {
                                            return dsLabel + ': £' + value.toLocaleString('en-GB', { maximumFractionDigits: 0 });
                                        } catch (e) {
                                            return dsLabel + ': £' + value;
                                        }
                                    }
                                    try {
                                        return dsLabel + ': ' + value.toLocaleString('en-GB', { maximumFractionDigits: 2 }) + '%';
                                    } catch (e) {
                                        return dsLabel + ': ' + value + '%';
                                    }
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Quarterly change (%)',
                            },
                            ticks: {
                                callback: function (value) {
                                    return value + '%';
                                },
                            },
                        },
                        y1: {
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Rental price (£)',
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function (value) {
                                    try {
                                        return '£' + value.toLocaleString('en-GB', { maximumFractionDigits: 0 });
                                    } catch (e) {
                                        return value;
                                    }
                                },
                            },
                        },
                    },
                },
            });
        };

        if (Array.isArray(series)) {
            series.forEach((s, i) => {
                const el = document.getElementById('rentalChart' + i);
                renderChart(el, s.labels || [], s.prices || [], s.changes || []);
            });
        }

        if (Array.isArray(typeSeries)) {
            typeSeries.forEach((s) => {
                const el = document.getElementById('rentalTypeChart_' + s.key);
                renderChart(el, s.labels || [], s.prices || [], s.changes || []);
            });
        }
    } catch (e) {
        console.error('Rental chart init error', e);
    }
})();
</script>
@endsection
