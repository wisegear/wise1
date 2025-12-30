@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900 mb-2">
                Repossession claims – <span class="text-lime-600">{{ $local_authority }}</span>
            </h1>
            <p class="text-zinc-500 text-sm">
                Data from 2003 to 2025 provided by the court service. Next update January 2026, data is provided quarterly. It's important to note that Repossessions are part 
                of a long process, only the possession action title "Repossession" reflects a property being repossessed.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/repossession.svg') }}" alt="Repossessions" class="w-56 h-auto">
        </div>
    </section>

    @php
        // Year labels + totals
        $labels = collect($yearly)->pluck('year')->map(fn($y) => (int)$y)->values();
        $totals = collect($yearly)->pluck('total')->map(fn($v) => (int)$v)->values();

        // Types: fixed order
        $typeOrder = ['Accelerated_Landlord','Mortgage','Private_Landlord','Social_Landlord'];
        $typeSeries = [];
        foreach ($typeOrder as $t) {
            $typeSeries[$t] = array_fill(0, $labels->count(), 0);
        }
        $yearIndex = $labels->flip();
        foreach (collect($byType) as $r) {
            $y = (int)($r->year ?? 0);
            $t = (string)($r->possession_type ?? '');
            if ($t === '' || !$yearIndex->has($y) || !isset($typeSeries[$t])) continue;
            $typeSeries[$t][$yearIndex[$y]] = (int)($r->total ?? 0);
        }

        // Actions: keep readable – top 8 overall + Other
        $topN = 8;
        $actionTotals = collect($byAction)
            ->groupBy(fn($r) => trim((string)($r->possession_action ?? '')))
            ->map(fn($rows) => (int)collect($rows)->sum('total'))
            ->filter(fn($v, $k) => trim((string)$k) !== '')
            ->sortDesc();

        $topActions = $actionTotals->keys()->take($topN)->values()->all();

        $actionSeries = [];
        foreach ($topActions as $a) {
            $actionSeries[$a] = array_fill(0, $labels->count(), 0);
        }
        $actionSeries['Other'] = array_fill(0, $labels->count(), 0);

        foreach (collect($byAction) as $r) {
            $y = (int)($r->year ?? 0);
            $a = trim((string)($r->possession_action ?? ''));
            if ($a === '' || !$yearIndex->has($y)) continue;
            $idx = $yearIndex[$y];
            $val = (int)($r->total ?? 0);
            if (in_array($a, $topActions, true)) {
                $actionSeries[$a][$idx] = $val;
            } else {
                $actionSeries['Other'][$idx] += $val;
            }
        }

        // Summary
        $latestTotal = $totals->last();
        $prevTotal = $totals->count() > 1 ? $totals[$totals->count() - 2] : null;
        $latestYear = $labels->last();
        $prevYear = $labels->count() > 1 ? $labels[$labels->count() - 2] : null;
        $yoy = ($latestTotal !== null && $prevTotal !== null) ? ((int)$latestTotal - (int)$prevTotal) : null;
    @endphp

    {{-- Summary panels --}}
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total claims year to date</div>
            <div class="mt-1 text-2xl font-semibold">
                {{ (int)($latestYear ?? 0) }}
                <span class="ml-2 text-sm text-gray-500">{{ number_format((int)($latestTotal ?? 0)) }}</span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total Claims in the Previous year</div>
            <div class="mt-1 text-2xl font-semibold">
                {{ (int)($prevYear ?? 0) }}
                <span class="ml-2 text-sm text-gray-500">{{ number_format((int)($prevTotal ?? 0)) }}</span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Year-on-year Change</div>
            <div class="mt-1 text-2xl font-semibold {{ ($yoy ?? 0) < 0 ? 'text-lime-600' : 'text-rose-600' }}">
                {{ $yoy === null ? '—' : (($yoy >= 0 ? '+' : '') . number_format((int)$yoy)) }}
            </div>
        </div>
    </div>

    {{-- Charts --}}

    {{-- Total cases (yearly) --}}
    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm mb-6">
        <div class="mb-2 text-sm font-medium text-gray-700">Total nunber of claims</div>
        <div class="h-80">
            <canvas id="laTotalChart"></canvas>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6">
        {{-- Possession type (yearly) --}}
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-2 text-sm font-medium text-gray-700">Possession actions and who raised them</div>
            <div class="h-80">
                <canvas id="laTypeChart"></canvas>
            </div>
            <p class="mt-3 text-xs text-gray-500">Types: Accelerated Landlord, Mortgage, Private Landlord, Social Landlord.</p>
        </section>

        {{-- Possession action (yearly) --}}
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-2 text-sm font-medium text-gray-700">Possession actions</div>
            <div class="h-80">
                <canvas id="laActionChart"></canvas>
            </div>
            <p class="mt-3 text-xs text-gray-500">Showing top actions overall + “Other” to keep the chart readable desipte there being virtually none.</p>
        </section>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const labels = @json($labels);

    // Types
    const typeSeries = @json($typeSeries);
    const typeDatasets = Object.keys(typeSeries).map(k => ({
        label: k.replaceAll('_',' '),
        data: typeSeries[k],
        tension: 0.2,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointStyle: 'circle',
    }));

    const typeCtx = document.getElementById('laTypeChart')?.getContext('2d');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'line',
            data: { labels, datasets: typeDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { ticks: { maxRotation: 0, autoSkip: true } },
                    y: { beginAtZero: true, grace: '5%' }
                }
            }
        });
    }

    // Total
    const totals = @json($totals);
    const totalCtx = document.getElementById('laTotalChart')?.getContext('2d');
    if (totalCtx) {
        new Chart(totalCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Total',
                    data: totals,
                    tension: 0.2,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    pointStyle: 'circle',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { ticks: { maxRotation: 0, autoSkip: true } },
                    y: { beginAtZero: true, grace: '5%' }
                }
            }
        });
    }

    // Actions (stacked)
    const actionSeries = @json($actionSeries);
    const actionDatasets = Object.keys(actionSeries).map(k => ({
        label: k.replaceAll('_',' '),
        data: actionSeries[k],
        stack: 'actions',
    }));

    const actionCtx = document.getElementById('laActionChart')?.getContext('2d');
    if (actionCtx) {
        new Chart(actionCtx, {
            type: 'bar',
            data: { labels, datasets: actionDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { stacked: true, ticks: { maxRotation: 0, autoSkip: true } },
                    y: { stacked: true, beginAtZero: true, grace: '5%' }
                }
            }
        });
    }
})();
</script>
@endsection