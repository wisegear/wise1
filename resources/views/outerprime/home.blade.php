@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Outer Prime London</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                Analysis of London's prestigious postcodes in outer London.
                <span class="font-semibold">Category A sales only</span>
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                30-year history • sourced from Land Registry
                <span class="ml-2 text-neutral-600">|
                    Data last cached:
                    @php
                        $ts = $lastCachedAt ?? \Illuminate\Support\Facades\Cache::get('outerprime:v1:catA:last_warm');
                    @endphp
                    @if(!empty($ts))
                        {{ \Carbon\Carbon::parse($ts)->timezone(config('app.timezone'))->format('j M Y, H:i') }}
                    @else
                        not yet warmed
                    @endif
                </span>
            </p>
        </div>
    </section>

    <div class="mb-6 flex items-center justify-center gap-3">
        <label for="districtFilter" class="text-sm text-neutral-700">Filter:</label>
        <select id="districtFilter" class="border border-zinc-300 bg-white rounded px-3 py-2 text-sm">
            <option class="bg-white text-zinc-800" value="">All postcodes</option>
            @foreach($districts as $d)
                <option class="bg-white text-zinc-800" value="{{ $d }}">{{ $d }}</option>
            @endforeach
        </select>
    </div>

    @if(($districts ?? collect())->isEmpty())
        <div class="rounded border p-6 bg-neutral-50">No Outer Prime districts found.</div>
    @else
        @foreach($districts as $district)
            <section class="mb-10 district-section" data-district="{{ $district }}">
                <h2 class="text-xl font-semibold mb-4">{{ $district }} – Overview</h2>
                @if(!empty($notes[$district] ?? null))
                    <p class="mb-4 text-sm text-neutral-600 whitespace-pre-line">{{ $notes[$district] }}</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Property Types (stacked bar) -->
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="font-semibold mb-2">Property Types in {{ $district }}</h3>
                        <canvas id="pt_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Average Price (line) -->
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="font-semibold mb-2">Average Price of property in {{ $district }}</h3>
                        <canvas id="ap_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Number of Sales (line) -->
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="font-semibold mb-2">Number of Sales in {{ $district }}</h3>
                        <canvas id="sc_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Top Sale Marker (scatter) -->
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="font-semibold mb-2">Top Sale Marker in {{ $district }}</h3>
                        <canvas id="ts_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Average + Prime Indicators (line) -->
                    <div class="rounded-lg border p-4 bg-white col-span-2">
                        <h3 class="font-semibold mb-2">Average & Outer‑Prime Indicators in {{ $district }}</h3>
                        <canvas id="api_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
</div>
@endsection

<script>
(function(){
    if (window.__oplInit) return; // prevent duplicate init (HMR/Turbo/etc.)
    window.__oplInit = true;
    window.__upclCharts = window.__upclCharts || {}; // registry to store Chart instances per canvas id

    const chartsPayload = @json($charts ?? []);
    console.log('OUTER PRIME charts payload', chartsPayload);

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

            const years = [...new Set([
                ...avgPrice.map(r => r.year),
                ...sales.map(r => r.year),
                ...propertyTypes.map(r => r.year),
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

            // Average Price (only avgPrice line)
            const apCtx = document.getElementById(`ap_${district}`);
            if (apCtx) {
                apCtx.style.display = 'block';
                apCtx.width = apCtx.clientWidth; apCtx.height = apCtx.clientHeight;
                const apId = `ap_${district}`; if (window.__upclCharts[apId]) { window.__upclCharts[apId].destroy(); }
                const apByYear = new Map(avgPrice.map(r => [r.year, r.avg_price]));
                const apData = years.map(y => apByYear.get(y) ?? null);

                new Chart(apCtx, { 
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: { 
                        labels: years, 
                        datasets: [
                            { label: 'Average Price (£)', data: apData, pointRadius: 3, tension: 0.2 }
                        ]
                    }, 
                    options: { 
                        animation: false, 
                        responsiveAnimationDuration: 0, 
                        responsive: false, 
                        maintainAspectRatio: false, 
                        plugins: { 
                            legend: { display: true }, 
                            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}` } } 
                        }, 
                        scales: { y: { ticks: { callback: (v) => fmtGBP(v) } } } 
                    } 
                });
                window.__upclCharts[apId] = Chart.getChart(apCtx);
                apCtx.style.backgroundColor = '#ffffff';
            }

            // Sales Count
            const scCtx = document.getElementById(`sc_${district}`);
            if (scCtx) {
                scCtx.style.display = 'block';
                scCtx.width = scCtx.clientWidth; scCtx.height = scCtx.clientHeight;
                const scId = `sc_${district}`; if (window.__upclCharts[scId]) { window.__upclCharts[scId].destroy(); }
                const scByYear = new Map(sales.map(r => [r.year, r.sales]));
                const scData = years.map(y => scByYear.get(y) ?? 0);
                new Chart(scCtx, { 
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: { labels: years, datasets: [{ label: 'Sales Count', data: scData, pointRadius: 3, tension: 0.2 }]},
                    options: { animation: false, responsiveAnimationDuration: 0, responsive: false, maintainAspectRatio: false, plugins: { legend: { display: true }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}` } } }, scales: { y: { ticks: { callback: (v) => fmtNum(v) } } } }
                });
                window.__upclCharts[scId] = Chart.getChart(scCtx);
                scCtx.style.backgroundColor = '#ffffff';
            }

            // Property Types (stacked)
            const ptCtx = document.getElementById(`pt_${district}`);
            if (ptCtx) {
                ptCtx.style.display = 'block';
                ptCtx.width = ptCtx.clientWidth; ptCtx.height = ptCtx.clientHeight;
                const ptId = `pt_${district}`; if (window.__upclCharts[ptId]) { window.__upclCharts[ptId].destroy(); }
                const yearTypeMap = new Map();
                propertyTypes.forEach(r => { if (!yearTypeMap.has(r.year)) yearTypeMap.set(r.year, new Map()); const m = yearTypeMap.get(r.year); m.set(r.type, (m.get(r.type) || 0) + r.count); });
                const allTypes = ['D','S','T','F','O'].filter(t => propertyTypes.some(r => r.type === t));
                const datasets = allTypes.map((t, i) => ({ label: TYPE_LABELS[t] || t, data: years.map(y => (yearTypeMap.get(y)?.get(t)) || 0), backgroundColor: baseColors[i % baseColors.length], borderWidth: 1, borderColor: 'rgba(0,0,0,0.1)' }));
                new Chart(ptCtx, { 
                    type: 'bar',
                    plugins: [whiteBgPlugin],
                    data: { labels: years, datasets },
                    options: { animation: false, responsiveAnimationDuration: 0, responsive: false, maintainAspectRatio: false, plugins: { legend: { display: true }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}` } } }, scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: (v) => fmtNum(v) } } } }
                });
                window.__upclCharts[ptId] = Chart.getChart(ptCtx);
                ptCtx.style.backgroundColor = '#ffffff';
            }

            // Top Sale Marker (scatter)
            const tsCtx = document.getElementById(`ts_${district}`);
            if (tsCtx) {
                tsCtx.style.display = 'block';
                tsCtx.width = tsCtx.clientWidth; tsCtx.height = tsCtx.clientHeight;
                const tsId = `ts_${district}`; if (window.__upclCharts[tsId]) { window.__upclCharts[tsId].destroy(); }

                // Build top3Index: Map from year -> array of top 3 sales (sorted by rn)
                const top3Index = new Map();
                for (const r of top3PerYear) {
                    if (!top3Index.has(r.year)) top3Index.set(r.year, []);
                    top3Index.get(r.year).push(r);
                }

                const tsByYear = new Map(topSalePerYear.map(r => [r.year, r.top_sale]));
                const tsData = Array.from(tsByYear, ([year, value]) => ({ x: year, y: value }));

                new Chart(tsCtx, {
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
                        responsiveAnimationDuration: 0,
                        responsive: false,
                        maintainAspectRatio: false,
                        parsing: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        const year = ctx.raw.x;
                                        const rows = (top3Index.get(year) || []).slice().sort((a,b) => a.rn - b.rn);
                                        if (!rows.length) return `Year ${year}: ${fmtGBP(ctx.raw.y)}`;
                                        const header = `Year ${year}: ${fmtGBP(ctx.raw.y)}`;
                                        const lines = rows.map(r => `#${r.rn} ${fmtGBP(r.Price)} – ${r.Postcode} (${new Date(r.Date).toLocaleDateString('en-GB')})`);
                                        return [header, ...lines];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { type: 'linear', ticks: { stepSize: 1 }, title: { display: true, text: 'Year' } },
                            y: { ticks: { callback: (v) => fmtGBP(v) } }
                        }
                    }
                });
                window.__upclCharts[tsId] = Chart.getChart(tsCtx);
                tsCtx.style.backgroundColor = '#ffffff';
            }

            // Average + Prime Indicators (line)
            const apiCtx = document.getElementById(`api_${district}`);
            if (apiCtx) {
                apiCtx.style.display = 'block';
                apiCtx.width = apiCtx.clientWidth; apiCtx.height = apiCtx.clientHeight;
                const apiId = `api_${district}`; if (window.__upclCharts[apiId]) { window.__upclCharts[apiId].destroy(); }
                const apByYear = new Map(avgPrice.map(r => [r.year, r.avg_price]));
                const apData = years.map(y => apByYear.get(y) ?? null);
                const p90ByYear = new Map(p90.map(r => [r.year, r.p90]));
                const p90Data = years.map(y => p90ByYear.get(y) ?? null);
                const top5ByYear = new Map(top5.map(r => [r.year, r.top5_avg]));
                const top5Data = years.map(y => top5ByYear.get(y) ?? null);

                new Chart(apiCtx, { 
                    type: 'line',
                    plugins: [whiteBgPlugin],
                    data: { 
                        labels: years, 
                        datasets: [
                            { label: 'Average Price (£)', data: apData, pointRadius: 3, tension: 0.2 },
                            { label: '90th Percentile (£)', data: p90Data, pointRadius: 0, borderDash: [6,4], tension: 0.1 },
                            { label: 'Top 5% Average (£)', data: top5Data, pointRadius: 2, borderWidth: 1, tension: 0.15 }
                        ]
                    }, 
                    options: { 
                        animation: false, 
                        responsiveAnimationDuration: 0, 
                        responsive: false, 
                        maintainAspectRatio: false, 
                        plugins: { 
                            legend: { display: true }, 
                            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}` } } 
                        }, 
                        scales: { y: { ticks: { callback: (v) => fmtGBP(v) } } } 
                    } 
                });
                window.__upclCharts[apiId] = Chart.getChart(apiCtx);
                apiCtx.style.backgroundColor = '#ffffff';
            }

            window.__renderedDistricts.add(district);
        }

        // Initial render based on current select value
        const filterEl = document.getElementById('districtFilter');
        function applyFilter() {
            const val = filterEl.value;
            const sections = document.querySelectorAll('.district-section');
            if (val === '') {
                sections.forEach(sec => sec.style.display = 'block');
                Object.keys(charts).forEach(d => renderDistrict(d));
            } else {
                sections.forEach(sec => {
                    const d = sec.getAttribute('data-district');
                    sec.style.display = (d === val) ? 'block' : 'none';
                });
                renderDistrict(val);
            }
        }

        // Render on load and on change
        applyFilter();
        filterEl.addEventListener('change', applyFilter);
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