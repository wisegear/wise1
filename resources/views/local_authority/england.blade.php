@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-10">

    {{-- Hero --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-5xl relative z-10">
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-3xl">
                English Council Housing Stock Figures
            </h1>
            <p class="mt-3 text-sm text-zinc-500">
                This is the most accurate official data I can provide.  The data can be split down by larger regions rather than smaller ones.  This as always is due to the data and/or quality of it. I am
                working on getting more granular data in future updates.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-600">
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                    Latest data<datalist></datalist>: <class="">March 2025
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next update Expected: <class="">March 2026
                </span>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/houses.jpg') }}" alt="Property Research" class="w-72 h-auto">
        </div>
</section>

    {{-- Controls --}}
    <section class="mt-10 w-1/2 mx-auto">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col items-center justify-center">
                <label for="regionSelect" class="block text-sm text-zinc-700">
                    Use the dropdown to see specific regional data
                </label>

                <div class="mt-3 w-full max-w-md">
                    <select id="regionSelect"
                            class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-zinc-900 text-sm shadow-sm focus:border-lime-500 focus:ring-lime-500">
                        @foreach($regions as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </section>

    {{-- Charts --}}
    <div class="mt-10 grid grid-cols-1 gap-8">
        {{-- Full-width total stock chart --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
            <h2 id="stockTitle" class="mb-4 text-lg font-semibold">Total council housing stock</h2>
            <canvas id="stockChart" height="120" style="max-height: 260px;"></canvas>
        </div>

        {{-- Two charts side-by-side --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
                <h2 id="newBuildsTitle" class="mb-4 text-lg font-semibold">New builds</h2>
                <canvas id="newBuildsChart" height="120" style="max-height: 260px;"></canvas>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
                <h2 id="acquisitionsTitle" class="mb-4 text-lg font-semibold">Acquisitions</h2>
                <canvas id="acquisitionsChart" height="120" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>

    {{-- England-wide charts --}}
    <div class="mt-10 grid grid-cols-1 gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
            <h2 class="mb-4 text-lg font-semibold">All England – total council housing stock</h2>
            <canvas id="englandStockChart" height="120" style="max-height: 260px;"></canvas>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
                <h2 class="mb-4 text-lg font-semibold">All England – new builds</h2>
                <canvas id="englandNewBuildsChart" height="120" style="max-height: 260px;"></canvas>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm max-h-[340px]">
                <h2 class="mb-4 text-lg font-semibold">All England – acquisitions</h2>
                <canvas id="englandAcquisitionsChart" height="120" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>

    {{-- Movers tables --}}
    <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Top declines ({{ $baselineYear }} → {{ $compareYear }})</h2>
            <table class="min-w-full text-sm">
                <thead class="border-b">
                    <tr class="text-left">
                        <th class="py-2">Region</th>
                        <th class="py-2 text-right">Start</th>
                        <th class="py-2 text-right">End</th>
                        <th class="py-2 text-right">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($biggestDeclines as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $row['region'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['start_stock']) }}</td>
                            <td class="py-2 text-right">{{ number_format($row['end_stock']) }}</td>
                            <td class="py-2 text-right text-rose-600">{{ number_format($row['delta']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Top increases ({{ $baselineYear }} → {{ $compareYear }})</h2>
            <p class="text-sm text-zinc-500 mb-3">Correct, no region has seen an overall increase in stock.  Unlike Scotland, Right to Buy still exists and stock is depleting
                faster than it's growing.
            </p>
            <table class="min-w-full text-sm">
                <thead class="border-b">
                    <tr class="text-left">
                        <th class="py-2">Region</th>
                        <th class="py-2 text-right">Start</th>
                        <th class="py-2 text-right">End</th>
                        <th class="py-2 text-right">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($biggestIncreases as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $row['region'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['start_stock']) }}</td>
                            <td class="py-2 text-right">{{ number_format($row['end_stock']) }}</td>
                            <td class="py-2 text-right text-lime-600">+{{ number_format($row['delta']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Movers tables: last 5 years --}}
    <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Top declines (last 5 years: {{ $baselineYearRecent }} → {{ $compareYear }})</h2>
            <table class="min-w-full text-sm">
                <thead class="border-b">
                    <tr class="text-left">
                        <th class="py-2">Region</th>
                        <th class="py-2 text-right">Start</th>
                        <th class="py-2 text-right">End</th>
                        <th class="py-2 text-right">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($biggestDeclinesRecent as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $row['region'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['start_stock']) }}</td>
                            <td class="py-2 text-right">{{ number_format($row['end_stock']) }}</td>
                            <td class="py-2 text-right text-rose-600">{{ number_format($row['delta']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Top increases (last 5 years: {{ $baselineYearRecent }} → {{ $compareYear }})</h2>
            <table class="min-w-full text-sm">
                <thead class="border-b">
                    <tr class="text-left">
                        <th class="py-2">Region</th>
                        <th class="py-2 text-right">Start</th>
                        <th class="py-2 text-right">End</th>
                        <th class="py-2 text-right">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($biggestIncreasesRecent as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $row['region'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['start_stock']) }}</td>
                            <td class="py-2 text-right">{{ number_format($row['end_stock']) }}</td>
                            <td class="py-2 text-right text-lime-600">+{{ number_format($row['delta']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const years = @json($years);
    const byRegion = @json($byRegion);
    const national = @json($national);

    function buildNationalSeries(key) {
        return years.map(y => national[y]?.[key] ?? null);
    }

    // England-wide charts
    const englandStockChart = makeLineChart(
        document.getElementById('englandStockChart'),
        'England total stock',
        buildNationalSeries('total_stock')
    );

    const englandNewBuildsChart = makeLineChart(
        document.getElementById('englandNewBuildsChart'),
        'England new builds',
        buildNationalSeries('new_builds')
    );

    const englandAcquisitionsChart = makeLineChart(
        document.getElementById('englandAcquisitionsChart'),
        'England acquisitions',
        buildNationalSeries('acquisitions')
    );

    const regionSelect = document.getElementById('regionSelect');

    let stockChart, newBuildsChart, acquisitionsChart;

    function buildSeries(region, key) {
        return years.map(y => byRegion[region]?.[y]?.[key] ?? null);
    }

    function makeLineChart(ctx, label, data) {
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label,
                    data,
                    borderWidth: 2,
                    tension: 0.25,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { ticks: { maxRotation: 0 } },
                    y: { beginAtZero: false }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function renderCharts(region) {
        stockChart?.destroy();
        newBuildsChart?.destroy();
        acquisitionsChart?.destroy();

        document.getElementById('stockTitle').textContent = `Total council housing stock – ${region}`;
        document.getElementById('newBuildsTitle').textContent = `New builds – ${region}`;
        document.getElementById('acquisitionsTitle').textContent = `Acquisitions – ${region}`;

        stockChart = makeLineChart(
            document.getElementById('stockChart'),
            'Total stock',
            buildSeries(region, 'total_stock')
        );

        newBuildsChart = makeLineChart(
            document.getElementById('newBuildsChart'),
            'New builds',
            buildSeries(region, 'new_builds')
        );

        acquisitionsChart = makeLineChart(
            document.getElementById('acquisitionsChart'),
            'Acquisitions',
            buildSeries(region, 'acquisitions')
        );
    }

    // Initial render
    renderCharts(regionSelect.value);

    regionSelect.addEventListener('change', e => {
        renderCharts(e.target.value);
    });
</script>
@endsection
