@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-10">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-5xl relative z-10">
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-3xl">
                Scottish Council Housing Stock Figures
            </h1>
            <p class="mt-3 text-sm text-zinc-500">
                This is the most accurate official data I can provide.  Couple of issues.  Some council such as Glasgow don't show, the reason for that is because all of their housing
                was transferred to housing associations in the early 2000s, so they appear to have no stock to report as official data does not track housing associations.  Some other anoomalies
                exist such as fife combining all it's tenement and four in a block into a single figure in 2009 showing a blip then changing it back in 2010.  I will update if and when I can find better data.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-600">
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                    Latest data<datalist></datalist>: <class="">Dec 2025
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next update Expected: <class="">Dec 2026
                </span>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/home.svg') }}" alt="Property Research" class="w-64 h-auto">
        </div>
</section>

    {{-- Controls --}}
    <section class="mt-10 w-1/2 mx-auto">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col items-center justify-center">
                <label for="councilSelect" class="block text-sm text-zinc-700">
                    Use the dropdown to see specific council data
                </label>

                <div class="mt-3 w-full max-w-md">
                    <select id="councilSelect"
                            class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-zinc-900 text-sm shadow-sm focus:border-lime-500 focus:ring-lime-500">
                        @foreach($councils as $council)
                            <option value="{{ $council }}">{{ $council }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </section>

    {{-- Council overview (selected council) --}}
    <section class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 id="totalChartTitle" class="text-md font-semibold text-zinc-900 mb-3">
                Total housing stock
            </h3>
            <div class="relative h-72">
                <canvas id="councilTotalChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-md font-semibold text-zinc-900 mb-3">
                Property age profile – <span id="ageChartCouncil"></span>
            </h3>
            <div class="relative h-72">
                <canvas id="councilAgeChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </section>

    {{-- Council detail --}}
    <section class="mt-10 grid grid-cols-1 gap-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 id="typeChartTitle" class="text-md font-semibold text-zinc-900 mb-3">
                Property type mix
            </h3>
            <div class="relative h-96">
                <canvas id="councilTypeChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </section>

    {{-- National overview --}}
    <section class="mt-10">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900 mb-4">
                Scotland – Total Council Housing Stock (All Councils)
            </h2>
            <div class="relative h-72">
                <canvas id="nationalTotalChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </section>

    {{-- Top movers --}}
    <section class="mt-12">
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">
            Largest changes in total stock ({{ $baselineYear }} → {{ $compareYear }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Declines --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-zinc-900 mb-3">Top 10 declines</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-zinc-500 border-b">
                        <tr>
                            <th class="py-2 text-left">Council</th>
                            <th class="py-2 text-right">2000</th>
                            <th class="py-2 text-right">2025</th>
                            <th class="py-2 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($biggestDeclines as $row)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $row['council'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2000']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2025']) }}</td>
                                <td class="py-2 text-right text-rose-600">
                                    {{ number_format($row['delta']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Increases --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-zinc-900">Top 10 increases</h3>
                <p class="text-sm text-zinc-500 mb-3">Yup, currently there is only one council over the past 25 years that has more stock.</p>
                <table class="min-w-full text-sm">
                    <thead class="text-zinc-500 border-b">
                        <tr>
                            <th class="py-2 text-left">Council</th>
                            <th class="py-2 text-right">2000</th>
                            <th class="py-2 text-right">2025</th>
                            <th class="py-2 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($biggestIncreases as $row)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $row['council'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2000']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2025']) }}</td>
                                <td class="py-2 text-right text-lime-600">
                                    +{{ number_format($row['delta']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Top movers (recent window) --}}
    <section class="mt-12">
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">
            Largest changes in total stock ({{ $baselineYearRecent }} → {{ $compareYear }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Declines --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-zinc-900">Top 10 declines</h3>
                <p class="text-sm text-zinc-500 mb-3">Yup, also correct, no councils show a decline in the last 5 years.  Bear in mind that right to buy ended in Scotland during 2016.  Soon after the
                    stock started going back up. </p>
                <table class="min-w-full text-sm">
                    <thead class="text-zinc-500 border-b">
                        <tr>
                            <th class="py-2 text-left">Council</th>
                            <th class="py-2 text-right">2020</th>
                            <th class="py-2 text-right">2025</th>
                            <th class="py-2 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($biggestDeclines2020_2025 as $row)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $row['council'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2020']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2025']) }}</td>
                                <td class="py-2 text-right text-rose-600">
                                    {{ number_format($row['delta']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Increases --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-zinc-900 mb-3">Top 10 increases</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-zinc-500 border-b">
                        <tr>
                            <th class="py-2 text-left">Council</th>
                            <th class="py-2 text-right">2020</th>
                            <th class="py-2 text-right">2025</th>
                            <th class="py-2 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($biggestIncreases2020_2025 as $row)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $row['council'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2020']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['year_2025']) }}</td>
                                <td class="py-2 text-right text-lime-600">
                                    +{{ number_format($row['delta']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Data for JS --}}
    <script>
        window.scotlandHousingData = {
            years: @json($years),
            councils: @json($councils),
            byCouncil: @json($byCouncil),
            national: @json($national),
        };
    </script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.Chart || !window.scotlandHousingData) return;

    const { years, byCouncil, national } = window.scotlandHousingData;

    const elNational = document.getElementById('nationalTotalChart');
    const elCouncilTotal = document.getElementById('councilTotalChart');
    const elCouncilType = document.getElementById('councilTypeChart');
    const elCouncilAge = document.getElementById('councilAgeChart');
    const select = document.getElementById('councilSelect');

    if (!elNational || !elCouncilTotal || !elCouncilType || !elCouncilAge || !select) return;

    let nationalChart, totalChart, typeChart, ageChart;

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
            x: {
                ticks: {
                    callback: (value, index) => {
                        // Chart.js category scale often passes `value` as an index.
                        // Linear/time scales pass the actual year value.
                        let year;

                        if (typeof value === 'string' && /^\d{4}$/.test(value)) {
                            year = Number(value);
                        } else {
                            const i = Number(value);
                            if (Number.isFinite(i) && years[Math.round(i)] !== undefined) {
                                year = Number(years[Math.round(i)]);
                            } else if (years[index] !== undefined) {
                                year = Number(years[index]);
                            }
                        }

                        if (!year) return '';
                        return year >= 2000 && (year - 2000) % 5 === 0 ? year : '';
                    },
                    autoSkip: false,
                    maxRotation: 0,
                    minRotation: 0
                }
            },
            y: {
                ticks: {
                    callback: v => v.toLocaleString()
                }
            }
        }
    };

    const drawNational = () => {
        nationalChart?.destroy();
        nationalChart = new Chart(elNational, {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label: 'Total stock (Scotland)',
                    data: years.map(y => national[y].total_stock),
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options
        });
    };

    const drawCouncil = (council) => {
        const typeTitle = document.getElementById('typeChartTitle');
        const ageTitle = document.getElementById('ageChartCouncil');

        if (typeTitle) typeTitle.textContent = `Property type mix – ${council}`;
        if (ageTitle) ageTitle.textContent = council;

        const totalTitle = document.getElementById('totalChartTitle');
        if (totalTitle) totalTitle.textContent = `Total housing stock – ${council}`;

        const rows = byCouncil[council];

        totalChart?.destroy();
        typeChart?.destroy();
        ageChart?.destroy();

        totalChart = new Chart(elCouncilTotal, {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label: 'Total stock',
                    data: years.map(y => rows[y]?.total_stock ?? 0),
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options
        });

        typeChart = new Chart(elCouncilType, {
            type: 'line',
            data: {
                labels: years,
                datasets: [
                    ['house','House'],
                    ['high_rise_flat','High‑rise flats'],
                    ['other_flat','Other flats'],
                    ['tenement','Tenement'],
                    ['four_in_a_block','4‑in‑a‑block']
                ].map(([k,l]) => ({
                    label: l,
                    data: years.map(y => rows[y]?.[k] ?? 0),
                    pointRadius: 3,
                    pointHoverRadius: 5
                }))
            },
            options
        });

        ageChart = new Chart(elCouncilAge, {
            type: 'line',
            data: {
                labels: years,
                datasets: [
                    ['pre_1919','Pre-1919'],
                    ['y1919_44','1919–44'],
                    ['y1945_64','1945–64'],
                    ['y1965_1982','1965–82'],
                    ['post_1982','Post-1982']
                ].map(([k,l]) => ({
                    label: l,
                    data: years.map(y => rows[y]?.[k] ?? 0),
                    pointRadius: 3,
                    pointHoverRadius: 5
                }))
            },
            options
        });
    };

    drawNational();
    drawCouncil(select.value);

    select.addEventListener('change', e => {
        drawCouncil(e.target.value);
    });
});
</script>

</div>
@endsection