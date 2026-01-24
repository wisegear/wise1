@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Prime Central London</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                Analysis of London's prestigious postcodes.
                <span class="font-semibold">Category A sales only</span>.  This represents the widely accepted postcodes in the Prime areas. 
                Some will refine that down further to specific streets or neighbourhoods, but for this site it's suitable for a broad overview.
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                30-year history • sourced from Land Registry
                <span class="ml-2 text-neutral-600">|
                    Data last cached:
                    @php
                        $ts = $lastCachedAt
                            ?? \Illuminate\Support\Facades\Cache::get('pcl:v3:catA:last_warm')
                            ?? \Illuminate\Support\Facades\Cache::get('pcl:v2:catA:last_warm');
                    @endphp
                    @if(!empty($ts))
                        {{ \Carbon\Carbon::parse($ts)->timezone(config('app.timezone'))->format('j M Y, H:i') }}
                    @else
                        not yet warmed
                    @endif
                </span>
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/prime_central.svg') }}" alt="Prime Central London" class="w-64 h-auto">
        </div>
    </section>

    <div class="mb-6 flex flex-col items-center gap-3">
        <div id="districtFilterGroup" class="flex flex-wrap justify-center gap-2">

            @if(($districts ?? collect())->contains('ALL'))
                <button
                    type="button"
                    class="inner-button"
                    data-district-filter="ALL"
                >
                    All Prime Central
                </button>
            @endif

            @foreach($districts as $d)
                @if($d !== 'ALL')
                    <button
                        type="button"
                        class="inner-button"
                        data-district-filter="{{ $d }}"
                    >
                        {{ $d }}
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    @if(($districts ?? collect())->isEmpty())
        <div class="rounded border p-6 bg-neutral-50">No Prime Central districts found.</div>
    @else
        @foreach($districts as $district)
            @php $__label = ($district === 'ALL') ? 'All Prime Central' : $district; @endphp
            <section class="mb-10 district-section" data-district="{{ $district }}">
                @if($district === 'ALL')
                    <div class="mb-4 rounded-md border border-zinc-200 bg-white p-4 text-sm text-neutral-700">
                        <h2 class="text-lg font-semibold mb-2">All Prime Central – Overview</h2>
                        <p>This section aggregates <strong>all Prime Central London postcodes</strong> into a single area for year-by-year analysis.</p>
                    </div>
                @elseif(!empty($notes[$district] ?? null))
                    <div class="mb-4 rounded-md border border-zinc-200 bg-white p-4 text-sm text-neutral-700">
                        <h2 class="text-lg font-semibold mb-2">{{ $district }} – Overview</h2>
                        <p>{{ $notes[$district] }}</p>
                    </div>
                @endif

                <div class="flex flex-col gap-4 md:grid md:grid-cols-2">
                    <!-- Number of Sales (line) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">Number of Sales in {{ $__label }}</h3>
                        <canvas id="sc_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                    <!-- Average Price (line) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">Average Price of property in {{ $__label }}</h3>
                        <canvas id="ap_{{ $district }}" class="w-full h-full"></canvas>
                    </div>


                    <!-- Property Types (stacked bar) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">Property Types in {{ $__label }}</h3>
                        <canvas id="pt_{{ $district }}" class="w-full h-full"></canvas>
                    </div>

                    <!-- Average Price by Property Type (line) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">Average Price by Property Type in {{ $__label }}</h3>
                        <canvas id="apt_{{ $district }}" class="w-full h-full"></canvas>
                    </div>

                    <!-- New Build vs Existing (% stacked bar) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">New Build vs Existing (% of sales) in {{ $__label }}</h3>
                        <canvas id="nb_{{ $district }}" class="w-full h-full"></canvas>
                    </div>

                    <!-- Freehold vs Leasehold (% stacked bar) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="font-semibold mb-2">Freehold vs Leasehold (% of sales) in {{ $__label }}</h3>
                        <canvas id="dur_{{ $district }}" class="w-full h-full"></canvas>
                    </div>

                    <!-- Top Sale Marker (scatter) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72 col-span-2">
                        <h3 class="font-semibold mb-2">Top 3 Sales each year in {{ $__label }} (hover over dot to see detail)</h3>
                        <canvas id="ts_{{ $district }}" class="w-full h-full"></canvas>
                    </div>

                    <!-- Average + Prime Indicators (line) -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72 col-span-2">
                        <h3 class="font-semibold mb-2">Average & Prime Indicators in {{ $__label }}</h3>
                        <canvas id="api_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                    <!-- YoY % Change Charts -->
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="text-sm font-medium text-zinc-700 mb-2">YoY % Change – Sales in {{ $__label }}</h3>
                        <canvas id="yoy_sales_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="text-sm font-medium text-zinc-700 mb-2">YoY % Change – 90th Percentile in {{ $__label }}</h3>
                        <canvas id="yoy_p90_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="text-sm font-medium text-zinc-700 mb-2">YoY % Change – Average Price in {{ $__label }}</h3>
                        <canvas id="yoy_avg_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                    <div class="rounded-lg border p-4 bg-white overflow-hidden h-56 sm:h-60 md:h-64 lg:h-72">
                        <h3 class="text-sm font-medium text-zinc-700 mb-2">YoY % Change – Top 5% Avg in {{ $__label }}</h3>
                        <canvas id="yoy_top5_{{ $district }}" class="w-full h-full"></canvas>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
    </div>
@endsection

<style>
    .prime-filter-btn-active {
        background-color: #18181b;
        color: #ffffff;
        border-color: #18181b;
    }
</style>

<script>
(function(){
    if (window.__upclInit) return; // prevent duplicate init (HMR/Turbo/etc.)
    window.__upclInit = true;
    window.__upclCharts = window.__upclCharts || {}; // registry to store Chart instances per canvas id

    const chartsPayload = @json($charts ?? []);
    console.log('PCL charts payload', chartsPayload);

    function renderCharts() {
        const charts = chartsPayload || {};

        // Plugin to force a solid white canvas background (no transparency)
        const whiteBgPlugin = {
            id: 'whiteBg',
            beforeDraw(chart) {
                const { ctx, canvas } = chart;
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.restore();
            }
        };

        const TYPE_LABELS = { D: 'Detached', S: 'Semi', T: 'Terraced', F: 'Flat', O: 'Other' };
        const fmtGBP = (v) => new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 0 }).format(v);
        const fmtNum = (v) => new Intl.NumberFormat('en-GB').format(v);
        const baseColors = ['#60a5fa','#f472b6','#fbbf24','#34d399','#a78bfa'];

        window.__renderedDistricts = window.__renderedDistricts || new Set();

        function renderDistrict(district) {
            if (!charts[district]) return;
            if (window.__renderedDistricts.has(district)) return; // already rendered

            const data = charts[district] || {};
            const avgPrice = (data.avgPrice || []).map(r => ({year: Number(r.year), avg_price: Number(r.avg_price)}));
            const sales = (data.sales || []).map(r => ({year: Number(r.year), sales: Number(r.sales)}));
            const propertyTypes = (data.propertyTypes || []).map(r => ({year: Number(r.year), type: r.type, count: Number(r.count)}));
            const p90 = (data.p90 || []).map(r => ({year: Number(r.year), p90: Number(r.p90)}));
            const top5 = (data.top5 || []).map(r => ({year: Number(r.year), top5_avg: Number(r.top5_avg)}));
            const topSalePerYear = (data.topSalePerYear || []).map(r => ({year: Number(r.year), top_sale: Number(r.top_sale)}));
            const top3PerYear = (data.top3PerYear || []).map(r => ({
                year: Number(r.year),
                Date: r.Date,
                Postcode: r.Postcode,
                Price: Number(r.Price),
                rn: Number(r.rn)
            }));
            // Expected from controller: [{ year: 2020, type: 'D', avg_price: 1234567 }, ...]
            const avgPriceByType = (data.avgPriceByType || []).map(r => ({
                year: Number(r.year),
                type: r.type,
                avg_price: Number(r.avg_price)
            }));
            // Expected from controller: [{ year: 2020, new_pct: 12.3, existing_pct: 87.7 }, ...]
            const newBuildPct = (data.newBuildPct || []).map(r => ({
                year: Number(r.year),
                new_pct: Number(r.new_pct),
                existing_pct: Number(r.existing_pct)
            }));
            // Expected from controller: [{ year: 2020, free_pct: 12.3, lease_pct: 87.7 }, ...]
            const tenurePct = (data.tenurePct || []).map(r => ({
                year: Number(r.year),
                free_pct: Number(r.free_pct),
                lease_pct: Number(r.lease_pct)
            }));

            const years = [...new Set([
                ...avgPrice.map(r => r.year),
                ...sales.map(r => r.year),
                ...propertyTypes.map(r => r.year),
                ...avgPriceByType.map(r => r.year),
                ...newBuildPct.map(r => r.year),
                ...tenurePct.map(r => r.year),
                ...p90.map(r => r.year),
                ...top5.map(r => r.year),
                ...topSalePerYear.map(r => r.year),
                ...top3PerYear.map(r => r.year)
            ])].sort((a,b) => a-b);

            if (years.length === 0) {
                const card = document.querySelector(`#pt_${district}`)?.parentElement;
                if (card) {
                    const note = document.createElement('p');
                    note.className = 'mt-2 text-xs text-neutral-500';
                    note.textContent = 'No data found for this district (check cache warm / filter logic).';
                    card.appendChild(note);
                }
                window.__renderedDistricts.add(district);
                return;
            }

            // === YoY helpers ===
            function seriesFromMap(valuesByYear) {
                return years.map(y => valuesByYear.get(y) ?? null);
            }
            function computeYoY(values) {
                const out = [];
                for (let i = 0; i < values.length; i++) {
                    if (i === 0 || values[i-1] === null || values[i] === null || values[i-1] === 0) { out.push(null); continue; }
                    out.push(((values[i] - values[i-1]) / values[i-1]) * 100);
                }
                return out;
            }
            function barColorsFrom(arr) {
                return arr.map(v => (v == null) ? 'rgba(150,150,150,0.6)' : (v >= 0 ? 'rgba(34,197,94,0.7)' : 'rgba(239,68,68,0.7)'));
            }
            function borderColorsFrom(arr) {
                return arr.map(v => (v == null) ? 'rgba(150,150,150,1)' : (v >= 0 ? 'rgba(34,197,94,1)' : 'rgba(239,68,68,1)'));
            }

            // Show every other year on YoY charts (labels remain numeric years)
            const tickEveryN = (value, index) => {
                // index corresponds to position in the labels array
                return (index % 2 === 0) ? String(years[index] ?? value) : '';
            };

            const apByYear_full = new Map(avgPrice.map(r => [r.year, r.avg_price]));
            const scByYear_full = new Map(sales.map(r => [r.year, r.sales]));
            const p90ByYear_full = new Map(p90.map(r => [r.year, r.p90]));
            const top5ByYear_full = new Map(top5.map(r => [r.year, r.top5_avg]));

            const avgSeries = seriesFromMap(apByYear_full);
            const salesSeries = seriesFromMap(scByYear_full);
            const p90Series = seriesFromMap(p90ByYear_full);
            const top5Series = seriesFromMap(top5ByYear_full);

            const yoyAvg  = computeYoY(avgSeries).map(v => v == null ? null : Math.round(v * 100) / 100);
            const yoySales = computeYoY(salesSeries).map(v => v == null ? null : Math.round(v * 100) / 100);
            const yoyP90  = computeYoY(p90Series).map(v => v == null ? null : Math.round(v * 100) / 100);
            const yoyTop5 = computeYoY(top5Series).map(v => v == null ? null : Math.round(v * 100) / 100);

            // Average Price (only avgPrice line)
            const apCtx = document.getElementById(`ap_${district}`);
            if (apCtx) {
                const apId = `ap_${district}`;
                if (window.__upclCharts[apId]) {
                    window.__upclCharts[apId].destroy();
                }

                const apByYear = new Map(avgPrice.map(r => [r.year, r.avg_price]));
                const apData = years.map(y => apByYear.get(y) ?? null);

                window.__upclCharts[apId] = new Chart(apCtx, {
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'Average Price (£)',
                                data: apData,
                                pointRadius: 3,
                                tension: 0.2
                            }
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 12,
                                bottom: 24,
                                left: 8
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) =>
                                        `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: (v) => fmtGBP(v)
                                }
                            }
                        }
                    }
                });

                apCtx.style.backgroundColor = '#ffffff';
            }

            // Sales Count
            const scCtx = document.getElementById(`sc_${district}`);
            if (scCtx) {
                const scId = `sc_${district}`;
                if (window.__upclCharts[scId]) {
                    window.__upclCharts[scId].destroy();
                }

                const scByYear = new Map(sales.map(r => [r.year, r.sales]));
                const scData = years.map(y => scByYear.get(y) ?? 0);

                window.__upclCharts[scId] = new Chart(scCtx, {
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'Sales Count',
                                data: scData,
                                pointRadius: 3,
                                tension: 0.2
                            }
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 12,
                                bottom: 24,
                                left: 8
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) =>
                                        `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: (v) => fmtNum(v)
                                }
                            }
                        }
                    }
                });

                scCtx.style.backgroundColor = '#ffffff';
            }

            // Property Types (stacked)
            const ptCtx = document.getElementById(`pt_${district}`);
            if (ptCtx) {
                const ptId = `pt_${district}`;
                if (window.__upclCharts[ptId]) {
                    window.__upclCharts[ptId].destroy();
                }

                const yearTypeMap = new Map();
                propertyTypes.forEach(r => {
                    if (!yearTypeMap.has(r.year)) yearTypeMap.set(r.year, new Map());
                    const m = yearTypeMap.get(r.year);
                    m.set(r.type, (m.get(r.type) || 0) + r.count);
                });

                const allTypes = ['D','S','T','F','O'].filter(t =>
                    propertyTypes.some(r => r.type === t)
                );

                const datasets = allTypes.map((t, i) => ({
                    label: TYPE_LABELS[t] || t,
                    data: years.map(y => (yearTypeMap.get(y)?.get(t)) || 0),
                    backgroundColor: baseColors[i % baseColors.length],
                    borderWidth: 1,
                    borderColor: 'rgba(0,0,0,0.1)'
                }));

                window.__upclCharts[ptId] = new Chart(ptCtx, {
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: { labels: years, datasets },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 12,
                                bottom: 24,
                                left: 8
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) =>
                                        `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}`
                                }
                            }
                        },
                        scales: {
                            x: { stacked: true },
                            y: {
                                stacked: true,
                                ticks: { callback: (v) => fmtNum(v) }
                            }
                        }
                    }
                });

                ptCtx.style.backgroundColor = '#ffffff';
            }

            // Average Price by Property Type (line)
            const aptCtx = document.getElementById(`apt_${district}`);
            if (aptCtx) {
                const aptId = `apt_${district}`;
                if (window.__upclCharts[aptId]) {
                    window.__upclCharts[aptId].destroy();
                }

                if (!avgPriceByType.length) {
                    const card = aptCtx.parentElement;
                    const note = document.createElement('p');
                    note.className = 'mt-2 text-xs text-neutral-500';
                    note.textContent = 'No per-property-type average price series found (controller needs to provide avgPriceByType).';
                    card.appendChild(note);
                    window.__renderedDistricts.add(district);
                } else {
                    // Build year -> type -> avg map
                    const yearTypeAvg = new Map();
                    avgPriceByType.forEach(r => {
                        if (!yearTypeAvg.has(r.year)) yearTypeAvg.set(r.year, new Map());
                        yearTypeAvg.get(r.year).set(r.type, r.avg_price);
                    });

                    const typeOrder = ['D','S','T','F'];
                    const presentTypes = typeOrder.filter(t => avgPriceByType.some(r => r.type === t));

                    const datasets = presentTypes.map((t, i) => ({
                        label: `${TYPE_LABELS[t] || t} Avg (£)`,
                        data: years.map(y => (yearTypeAvg.get(y)?.get(t)) ?? null),
                        pointRadius: 2,
                        tension: 0.2,
                        borderWidth: 2,
                        borderColor: baseColors[i % baseColors.length],
                        backgroundColor: baseColors[i % baseColors.length]
                    }));

                    window.__upclCharts[aptId] = new Chart(aptCtx, {
                        type: 'line',
                        plugins: [whiteBgPlugin],
                        data: {
                            labels: years,
                            datasets
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 12,
                                    right: 12,
                                    bottom: 24,
                                    left: 8
                                }
                            },
                            plugins: {
                                legend: { display: true },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    ticks: { callback: (v) => fmtGBP(v) }
                                }
                            }
                        }
                    });

                    aptCtx.style.backgroundColor = '#ffffff';
                }
            }

            // New Build vs Existing (% stacked bar)
            const nbCtx = document.getElementById(`nb_${district}`);
            if (nbCtx) {
                const nbId = `nb_${district}`;
                if (window.__upclCharts[nbId]) {
                    window.__upclCharts[nbId].destroy();
                }

                if (!newBuildPct.length) {
                    const card = nbCtx.parentElement;
                    const note = document.createElement('p');
                    note.className = 'mt-2 text-xs text-neutral-500';
                    note.textContent = 'No new-build % series found (controller/warmer needs to provide newBuildPct).';
                    card.appendChild(note);
                } else {
                    const nbByYear = new Map(newBuildPct.map(r => [r.year, r]));
                    const newData = years.map(y => (nbByYear.get(y)?.new_pct) ?? null);
                    const exData  = years.map(y => (nbByYear.get(y)?.existing_pct) ?? null);

                    window.__upclCharts[nbId] = new Chart(nbCtx, {
                        type: 'bar',
                        plugins: [whiteBgPlugin],
                        data: {
                            labels: years,
                            datasets: [
                                {
                                    label: 'New Build %',
                                    data: newData,
                                    backgroundColor: baseColors[3] || '#34d399',
                                    borderWidth: 1,
                                    borderColor: 'rgba(0,0,0,0.1)'
                                },
                                {
                                    label: 'Existing %',
                                    data: exData,
                                    backgroundColor: baseColors[0] || '#60a5fa',
                                    borderWidth: 1,
                                    borderColor: 'rgba(0,0,0,0.1)'
                                }
                            ]
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 12,
                                    right: 12,
                                    bottom: 24,
                                    left: 8
                                }
                            },
                            plugins: {
                                legend: { display: true },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const v = ctx.parsed.y;
                                            return `${ctx.dataset.label}: ${v == null ? 'n/a' : v.toFixed(1)}%`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { stacked: true },
                                y: {
                                    stacked: true,
                                    min: 0,
                                    max: 100,
                                    ticks: { callback: (v) => v + '%' }
                                }
                            }
                        }
                    });

                    nbCtx.style.backgroundColor = '#ffffff';
                }
            }

            // Freehold vs Leasehold (% stacked bar)
            const durCtx = document.getElementById(`dur_${district}`);
            if (durCtx) {
                const durId = `dur_${district}`;
                if (window.__upclCharts[durId]) {
                    window.__upclCharts[durId].destroy();
                }

                if (!tenurePct.length) {
                    const card = durCtx.parentElement;
                    const note = document.createElement('p');
                    note.className = 'mt-2 text-xs text-neutral-500';
                    note.textContent = 'No tenure % series found (controller/warmer needs to provide tenurePct).';
                    card.appendChild(note);
                } else {
                    const tByYear = new Map(tenurePct.map(r => [r.year, r]));
                    const freeData = years.map(y => (tByYear.get(y)?.free_pct) ?? null);
                    const leaseData = years.map(y => (tByYear.get(y)?.lease_pct) ?? null);

                    window.__upclCharts[durId] = new Chart(durCtx, {
                        type: 'bar',
                        plugins: [whiteBgPlugin],
                        data: {
                            labels: years,
                            datasets: [
                                {
                                    label: 'Freehold %',
                                    data: freeData,
                                    backgroundColor: baseColors[2] || '#fbbf24',
                                    borderWidth: 1,
                                    borderColor: 'rgba(0,0,0,0.1)'
                                },
                                {
                                    label: 'Leasehold %',
                                    data: leaseData,
                                    backgroundColor: baseColors[1] || '#f472b6',
                                    borderWidth: 1,
                                    borderColor: 'rgba(0,0,0,0.1)'
                                }
                            ]
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 12,
                                    right: 12,
                                    bottom: 24,
                                    left: 8
                                }
                            },
                            plugins: {
                                legend: { display: true },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const v = ctx.parsed.y;
                                            return `${ctx.dataset.label}: ${v == null ? 'n/a' : v.toFixed(1)}%`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { stacked: true },
                                y: {
                                    stacked: true,
                                    min: 0,
                                    max: 100,
                                    ticks: { callback: (v) => v + '%' }
                                }
                            }
                        }
                    });

                    durCtx.style.backgroundColor = '#ffffff';
                }
            }

            // Top Sale Marker (scatter)
            const tsCtx = document.getElementById(`ts_${district}`);
            if (tsCtx) {
                const tsId = `ts_${district}`;
                if (window.__upclCharts[tsId]) {
                    window.__upclCharts[tsId].destroy();
                }

                const top3Index = new Map();
                for (const r of top3PerYear) {
                    if (!top3Index.has(r.year)) top3Index.set(r.year, []);
                    top3Index.get(r.year).push(r);
                }

                const tsByYear = new Map(topSalePerYear.map(r => [r.year, r.top_sale]));
                const tsData = Array.from(tsByYear, ([year, value]) => ({ x: year, y: value }));

                window.__upclCharts[tsId] = new Chart(tsCtx, {
                    type: 'scatter',
                    plugins: [whiteBgPlugin],
                    data: {
                        datasets: [
                            {
                                label: 'Top Sale (£)',
                                data: tsData,
                                pointRadius: 5,
                                backgroundColor: '#ef4444'
                            }
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 12,
                                bottom: 24,
                                left: 8
                            }
                        },
                        parsing: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        const year = ctx.raw.x;
                                        const rows = (top3Index.get(year) || [])
                                            .slice()
                                            .sort((a, b) => a.rn - b.rn);
                                        if (!rows.length) {
                                            return `Year ${year}: ${fmtGBP(ctx.raw.y)}`;
                                        }
                                        const header = `Year ${year}: ${fmtGBP(ctx.raw.y)}`;
                                        const lines = rows.map(r =>
                                            `#${r.rn} ${fmtGBP(r.Price)} – ${r.Postcode} (${new Date(r.Date).toLocaleDateString('en-GB')})`
                                        );
                                        return [header, ...lines];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                type: 'linear',
                                ticks: {
                                    stepSize: 1,
                                    callback: (value) => value.toString()
                                },
                                title: { display: false }
                            },
                            y: {
                                ticks: { callback: (v) => fmtGBP(v) }
                            }
                        }
                    }
                });

                tsCtx.style.backgroundColor = '#ffffff';
            }

            // Average + Prime Indicators (line)
            const apiCtx = document.getElementById(`api_${district}`);
            if (apiCtx) {
                const apiId = `api_${district}`;
                if (window.__upclCharts[apiId]) {
                    window.__upclCharts[apiId].destroy();
                }

                const apByYear = new Map(avgPrice.map(r => [r.year, r.avg_price]));
                const apData = years.map(y => apByYear.get(y) ?? null);

                const p90ByYear = new Map(p90.map(r => [r.year, r.p90]));
                const p90Data = years.map(y => p90ByYear.get(y) ?? null);

                const top5ByYear = new Map(top5.map(r => [r.year, r.top5_avg]));
                const top5Data = years.map(y => top5ByYear.get(y) ?? null);

                window.__upclCharts[apiId] = new Chart(apiCtx, {
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'Average Price (£)',
                                data: apData,
                                pointRadius: 3,
                                tension: 0.2
                            },
                            {
                                label: '90th Percentile (£)',
                                data: p90Data,
                                pointRadius: 0,
                                borderDash: [6, 4],
                                tension: 0.1
                            },
                            {
                                label: 'Top 5% Average (£)',
                                data: top5Data,
                                pointRadius: 2,
                                borderWidth: 1,
                                tension: 0.15
                            }
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 12,
                                bottom: 24,
                                left: 8
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) =>
                                        `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: (v) => fmtGBP(v)
                                }
                            }
                        }
                    }
                });

                apiCtx.style.backgroundColor = '#ffffff';
            }

            // YoY – Sales
            const yoySalesCtx = document.getElementById(`yoy_sales_${district}`);
            if (yoySalesCtx) {
                const id = `yoy_sales_${district}`; if (window.__upclCharts[id]) { window.__upclCharts[id].destroy(); }
                new Chart(yoySalesCtx, {
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [{
                            data: yoySales,
                            backgroundColor: barColorsFrom(yoySales),
                            borderColor: borderColorsFrom(yoySales),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 12, right: 12, bottom: 20, left: 12 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => {
                                const v = ctx.parsed.y;
                                return (v == null) ? 'No prior year' : `${v.toFixed(2)}%`;
                            }}}
                        },
                        scales: {
                            x: { 
                                ticks: { 
                                    callback: tickEveryN,
                                    autoSkip: false,
                                    padding: 8,
                                    maxRotation: 0,
                                    minRotation: 0
                                } 
                            },
                            y: { ticks: { callback: (v) => v + '%' } }
                        }
                    }
                });
                window.__upclCharts[id] = Chart.getChart(yoySalesCtx);
                yoySalesCtx.style.backgroundColor = '#ffffff';
            }

            // YoY – 90th Percentile
            const yoyP90Ctx = document.getElementById(`yoy_p90_${district}`);
            if (yoyP90Ctx) {
                const id = `yoy_p90_${district}`; if (window.__upclCharts[id]) { window.__upclCharts[id].destroy(); }
                new Chart(yoyP90Ctx, {
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [{
                            data: yoyP90,
                            backgroundColor: barColorsFrom(yoyP90),
                            borderColor: borderColorsFrom(yoyP90),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 12, right: 12, bottom: 20, left: 12 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => {
                                const v = ctx.parsed.y;
                                return (v == null) ? 'No prior year' : `${v.toFixed(2)}%`;
                            }}}
                        },
                        scales: {
                            x: { 
                                ticks: { 
                                    callback: tickEveryN,
                                    autoSkip: false,
                                    padding: 8,
                                    maxRotation: 0,
                                    minRotation: 0
                                } 
                            },
                            y: { ticks: { callback: (v) => v + '%' } }
                        }
                    }
                });
                window.__upclCharts[id] = Chart.getChart(yoyP90Ctx);
                yoyP90Ctx.style.backgroundColor = '#ffffff';
            }

            // YoY – Average Price
            const yoyAvgCtx = document.getElementById(`yoy_avg_${district}`);
            if (yoyAvgCtx) {
                const id = `yoy_avg_${district}`; if (window.__upclCharts[id]) { window.__upclCharts[id].destroy(); }
                new Chart(yoyAvgCtx, {
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [{
                            data: yoyAvg,
                            backgroundColor: barColorsFrom(yoyAvg),
                            borderColor: borderColorsFrom(yoyAvg),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 12, right: 12, bottom: 20, left: 12 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => {
                                const v = ctx.parsed.y;
                                return (v == null) ? 'No prior year' : `${v.toFixed(2)}%`;
                            }}}
                        },
                        scales: {
                            x: { 
                                ticks: { 
                                    callback: tickEveryN,
                                    autoSkip: false,
                                    padding: 8,
                                    maxRotation: 0,
                                    minRotation: 0
                                } 
                            },
                            y: { ticks: { callback: (v) => v + '%' } }
                        }
                    }
                });
                window.__upclCharts[id] = Chart.getChart(yoyAvgCtx);
                yoyAvgCtx.style.backgroundColor = '#ffffff';
            }

            // YoY – Top 5% Avg
            const yoyTop5Ctx = document.getElementById(`yoy_top5_${district}`);
            if (yoyTop5Ctx) {
                const id = `yoy_top5_${district}`; if (window.__upclCharts[id]) { window.__upclCharts[id].destroy(); }
                new Chart(yoyTop5Ctx, {
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: {
                        labels: years,
                        datasets: [{
                            data: yoyTop5,
                            backgroundColor: barColorsFrom(yoyTop5),
                            borderColor: borderColorsFrom(yoyTop5),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 12, right: 12, bottom: 20, left: 12 } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => {
                                const v = ctx.parsed.y;
                                return (v == null) ? 'No prior year' : `${v.toFixed(2)}%`;
                            }}}
                        },
                        scales: {
                            x: { 
                                ticks: { 
                                    callback: tickEveryN,
                                    autoSkip: false,
                                    padding: 8,
                                    maxRotation: 0,
                                    minRotation: 0
                                } 
                            },
                            y: { ticks: { callback: (v) => v + '%' } }
                        }
                    }
                });
                window.__upclCharts[id] = Chart.getChart(yoyTop5Ctx);
                yoyTop5Ctx.style.backgroundColor = '#ffffff';
            }

            window.__renderedDistricts.add(district);
        }

        // Initial render based on button group
        const filterButtons = document.querySelectorAll('[data-district-filter]');
        let currentFilter = '';

        function setActiveButton(val) {
            filterButtons.forEach(btn => {
                const btnVal = btn.getAttribute('data-district-filter') || '';
                if (btnVal === val) {
                    btn.classList.add('prime-filter-btn-active');
                } else {
                    btn.classList.remove('prime-filter-btn-active');
                }
            });
        }

        function applyFilter(val) {
            const sections = document.querySelectorAll('.district-section');
            if (!val) {
                sections.forEach(sec => sec.style.display = 'block');
                Object.keys(charts).forEach(d => renderDistrict(d));
            } else {
                sections.forEach(sec => {
                    const d = sec.getAttribute('data-district');
                    sec.style.display = (d === val) ? 'block' : 'none';
                });
                renderDistrict(val);
            }
            currentFilter = val;
            setActiveButton(val);
        }

        // Choose default filter: aggregated "ALL" if present, else all sections
        let defaultFilter = '';
        if ([...filterButtons].some(btn => (btn.getAttribute('data-district-filter') || '') === 'ALL')) {
            defaultFilter = 'ALL';
        }

        // Attach click handlers
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.getAttribute('data-district-filter') || '';
                applyFilter(val);
            });
        });

        // Initial render
        applyFilter(defaultFilter);
    }

    function boot() {
        if (typeof window.Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            s.onload = renderCharts;
            document.head.appendChild(s);
        } else {
            renderCharts();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
