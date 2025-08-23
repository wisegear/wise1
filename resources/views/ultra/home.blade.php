@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    <div class="relative flex flex-col justify-center items-center h-[150px] border border-colour mb-10 bg-gray-100 shadow-lg">
        <i class="fa-solid fa-city fa-6x text-slate-200 absolute left-5 opacity-70"></i>
        <h2 class="font-bold text-center text-2xl z-10">Ultra Prime Central London</h2>
        <p class="text-center text-gray-500 z-10">Analysis of London's most prestigious postcodes</p>
        <div class="mt-2 flex items-center justify-between text-xs text-neutral-600 z-10 px-3 py-1 rounded">
            <p>30-year history • sourced from Land Registry</p>
            <p class="ml-4">
                Data last cached:
                @php
                    $ts = $lastCachedAt ?? \Illuminate\Support\Facades\Cache::get('upcl:v3:last_warm');
                @endphp
                @if(!empty($ts))
                    {{ \Carbon\Carbon::parse($ts)->timezone(config('app.timezone'))->format('j M Y, H:i') }}
                @else
                    not yet warmed
                @endif
            </p>
        </div>
        <i class="fa-solid fa-city fa-6x text-slate-200 absolute right-5 opacity-70"></i>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <label for="districtFilter" class="text-sm text-neutral-700">Filter:</label>
        <select id="districtFilter" class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">All postcodes</option>
            @foreach($districts as $d)
                <option value="{{ $d }}">{{ $d }}</option>
            @endforeach
        </select>
    </div>

    @if(($districts ?? collect())->isEmpty())
        <div class="rounded border p-6 bg-neutral-50">No Ultra Prime districts found.</div>
    @else
        @foreach($districts as $district)
            <section class="mb-10 district-section" data-district="{{ $district }}">
                <h2 class="text-xl font-semibold mb-4">{{ $district }} – Overview</h2>
                @if(!empty($notes[$district] ?? null))
                    <p class="mb-4 text-sm text-neutral-600 whitespace-pre-line">{{ $notes[$district] }}</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Property Types (stacked bar) -->
                    <div class="rounded-xl border p-4">
                        <h3 class="font-semibold mb-2">Property Types in {{ $district }}</h3>
                        <canvas id="pt_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Average Price (line) -->
                    <div class="rounded-xl border p-4">
                        <h3 class="font-semibold mb-2">Average Price of property in {{ $district }}</h3>
                        <canvas id="ap_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>

                    <!-- Number of Sales (line) -->
                    <div class="rounded-xl border p-4">
                        <h3 class="font-semibold mb-2">Number of Sales in {{ $district }}</h3>
                        <canvas id="sc_{{ $district }}" class="w-full h-[220px] md:h-[260px]"></canvas>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
</div>
@endsection

<script>
(function(){
    if (window.__upclInit) return; // prevent duplicate init (HMR/Turbo/etc.)
    window.__upclInit = true;
    window.__upclCharts = window.__upclCharts || {}; // registry to store Chart instances per canvas id

    const chartsPayload = @json($charts ?? []);
    console.log('UPCL charts payload', chartsPayload);

    function renderCharts() {
        const charts = chartsPayload || {};

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

            const years = [...new Set([
                ...avgPrice.map(r => r.year),
                ...sales.map(r => r.year),
                ...propertyTypes.map(r => r.year)
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

            // Average Price
            const apCtx = document.getElementById(`ap_${district}`);
            if (apCtx) {
                apCtx.style.display = 'block';
                apCtx.width = apCtx.clientWidth; apCtx.height = apCtx.clientHeight;
                const apId = `ap_${district}`; if (window.__upclCharts[apId]) { window.__upclCharts[apId].destroy(); }
                const apByYear = new Map(avgPrice.map(r => [r.year, r.avg_price]));
                const apData = years.map(y => apByYear.get(y) ?? null);
                new Chart(apCtx, { type: 'line', data: { labels: years, datasets: [{ label: 'Average Price (£)', data: apData, pointRadius: 3, tension: 0.2 }]}, options: { animation: false, responsiveAnimationDuration: 0, responsive: false, maintainAspectRatio: false, plugins: { legend: { display: true }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtGBP(ctx.parsed.y)}` } } }, scales: { y: { ticks: { callback: (v) => fmtGBP(v) } } } } });
                window.__upclCharts[apId] = Chart.getChart(apCtx);
            }

            // Sales Count
            const scCtx = document.getElementById(`sc_${district}`);
            if (scCtx) {
                scCtx.style.display = 'block';
                scCtx.width = scCtx.clientWidth; scCtx.height = scCtx.clientHeight;
                const scId = `sc_${district}`; if (window.__upclCharts[scId]) { window.__upclCharts[scId].destroy(); }
                const scByYear = new Map(sales.map(r => [r.year, r.sales]));
                const scData = years.map(y => scByYear.get(y) ?? 0);
                new Chart(scCtx, { type: 'line', data: { labels: years, datasets: [{ label: 'Sales Count', data: scData, pointRadius: 3, tension: 0.2 }]}, options: { animation: false, responsiveAnimationDuration: 0, responsive: false, maintainAspectRatio: false, plugins: { legend: { display: true }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}` } } }, scales: { y: { ticks: { callback: (v) => fmtNum(v) } } } } });
                window.__upclCharts[scId] = Chart.getChart(scCtx);
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
                new Chart(ptCtx, { type: 'bar', data: { labels: years, datasets }, options: { animation: false, responsiveAnimationDuration: 0, responsive: false, maintainAspectRatio: false, plugins: { legend: { display: true }, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y)}` } } }, scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: (v) => fmtNum(v) } } } } });
                window.__upclCharts[ptId] = Chart.getChart(ptCtx);
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