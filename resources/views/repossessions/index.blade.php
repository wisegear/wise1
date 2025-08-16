@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold">Repossessions</h1>
        <p class="text-sm text-gray-600">
            {{ $meta['period'] === 'yearly' ? 'Yearly' : 'Quarterly' }} view · grouped by
            <span class="font-medium">{{ $meta['by'] === 'action' ? 'Stage (Action)' : 'Reason (Type)' }}</span>
        </p>
    </header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('repossessions.index') }}" class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        {{-- Top row: period + by toggles --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Period:</span>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="period" value="quarterly" class="size-4" {{ ($meta['period'] ?? 'quarterly') === 'quarterly' ? 'checked' : '' }}>
                    <span class="text-sm">Quarterly</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="period" value="yearly" class="size-4" {{ ($meta['period'] ?? 'quarterly') === 'yearly' ? 'checked' : '' }}>
                    <span class="text-sm">Yearly</span>
                </label>

                <span class="mx-3 h-5 w-px bg-gray-200"></span>

                <span class="text-sm font-medium text-gray-700">Group by:</span>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="by" value="type" class="size-4" {{ ($meta['by'] ?? 'type') === 'type' ? 'checked' : '' }}>
                    <span class="text-sm">Reason (Type)</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="by" value="action" class="size-4" {{ ($meta['by'] ?? 'type') === 'action' ? 'checked' : '' }}>
                    <span class="text-sm">Stage (Action)</span>
                </label>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600" for="per_page">Per page</label>
                <select name="per_page" id="per_page" class="rounded-md border-gray-300 text-sm">
                    @foreach([50,100,200,500] as $opt)
                        <option value="{{ $opt }}" {{ (request('per_page', 100)==$opt) ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Second row: period-specific selectors --}}
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3" x-data>
            {{-- Quarterly selectors --}}
            <div id="quarterly-fields" class="{{ ($meta['period'] ?? 'quarterly') === 'quarterly' ? '' : 'hidden' }} grid grid-cols-2 gap-4 md:col-span-3">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">Year</label>
                    <select name="year" class="w-full rounded-md border-gray-300">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ ( ($meta['year'] ?? null) == $y ) ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">Quarter</label>
                    <select name="quarter" class="w-full rounded-md border-gray-300">
                        @foreach($meta['quarters'] as $q)
                            <option value="{{ $q }}" {{ ( ($meta['quarter'] ?? null) == $q ) ? 'selected' : '' }}>{{ $q }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Yearly selectors --}}
            <div id="yearly-fields" class="{{ ($meta['period'] ?? 'quarterly') === 'yearly' ? '' : 'hidden' }} grid grid-cols-2 gap-4 md:col-span-3">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">From year</label>
                    <select name="year_from" class="w-full rounded-md border-gray-300">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ ( ($meta['year_from'] ?? null) == $y ) ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">To year</label>
                    <select name="year_to" class="w-full rounded-md border-gray-300">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ ( ($meta['year_to'] ?? null) == $y ) ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Third row: geography + type/action filters --}}
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm text-gray-600">Region</label>
                <select name="region" class="w-full rounded-md border-gray-300">
                    <option value="">All regions</option>
                    @foreach($regions as $r)
                        <option value="{{ $r }}" {{ request('region')===$r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm text-gray-600">County</label>
                <select name="county" class="w-full rounded-md border-gray-300">
                    <option value="">All counties</option>
                    @foreach($counties as $c)
                        <option value="{{ $c }}" {{ request('county')===$c ? 'selected' : '' }}>
                            {{ str_replace(' UA','',$c) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Show/hide these two depending on "by" --}}
            <div id="type-filter" class="{{ ($meta['by'] ?? 'type') === 'type' ? '' : 'hidden' }}">
                <label class="mb-1 block text-sm text-gray-600">Reason (Type)</label>
                <select name="type" class="w-full rounded-md border-gray-300">
                    <option value="">All types</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div id="action-filter" class="{{ ($meta['by'] ?? 'type') === 'action' ? '' : 'hidden' }}">
                <label class="mb-1 block text-sm text-gray-600">Stage (Action)</label>
                <select name="action" class="w-full rounded-md border-gray-300">
                    <option value="">All actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action')===$a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="mt-5 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-lime-500 px-4 py-2 text-sm font-medium text-white hover:bg-lime-400">Apply</button>
            <a href="{{ route('repossessions.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Reset</a>
        </div>
    </form>

    {{-- Totals / chips --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total cases (current filters)</div>
            <div class="mt-1 text-2xl font-semibold">
                {{ number_format($totals['all'] ?? 0) }}
                @if(($meta['period'] ?? 'quarterly')==='quarterly')
                    <span class="ml-2 text-sm text-gray-500">{{ $meta['year'] }} {{ $meta['quarter'] }}</span>
                @else
                    <span class="ml-2 text-sm text-gray-500">{{ $meta['year_from'] }}–{{ $meta['year_to'] }}</span>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-2">
            <div class="text-xs uppercase tracking-wide text-gray-500">Breakdown by {{ $meta['by']==='action' ? 'stage' : 'reason' }}</div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($totals['byReason'] as $r)
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-sm">
                        {{ $r->reason }}: <span class="font-medium">{{ number_format($r->total_cases) }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-2 text-sm font-medium text-gray-700">Cases by {{ $meta['by']==='action' ? 'Stage' : 'Reason' }} (current filters)</div>
        <div class="h-64"> {{-- fixed container height; chart fills this --}}
            <canvas id="reasonChart"></canvas>
        </div>
    </div>

    {{-- Results table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-gray-50 text-left text-sm text-gray-600">
                    <tr>
                        @if(($meta['period'] ?? 'quarterly')==='yearly')
                            <th class="sticky left-0 z-10 border-b border-gray-200 px-4 py-2 bg-gray-50">Year</th>
                        @endif
                        <th class="border-b border-gray-200 px-4 py-2">County</th>
                        <th class="border-b border-gray-200 px-4 py-2">{{ $meta['by']==='action' ? 'Stage (Action)' : 'Reason (Type)' }}</th>
                        <th class="border-b border-gray-200 px-4 py-2 text-right">Cases</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50">
                            @if(($meta['period'] ?? 'quarterly')==='yearly')
                                <td class="sticky left-0 z-10 border-b border-gray-100 px-4 py-2 bg-white">{{ $row->year }}</td>
                            @endif
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ str_replace(' UA','', (string)($row->county_ua ?? '')) }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ $row->reason }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2 text-right font-medium">
                                {{ number_format($row->cases) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($meta['period'] ?? 'quarterly')==='yearly' ? 4 : 3 }}" class="px-4 py-8 text-center text-gray-500">
                                No results match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-gray-200 px-4 py-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    // Prepare data for the simple "by reason" chart using totals from the controller (already fully aggregated for current filters)
    const labels = @json(($totals['byReason'] ?? collect())->pluck('reason'));
    const data   = @json(($totals['byReason'] ?? collect())->pluck('total_cases'));

    const canvas = document.getElementById('reasonChart');
    const ctx = canvas.getContext('2d');
    // Destroy any prior instance if the script runs again (e.g., hot reload)
    if (window._reasonChart) { window._reasonChart.destroy(); }
    window._reasonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cases',
                data: data,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // fill the fixed 16rem parent height
            animation: false,           // prevent reflow-induced scrolling
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { ticks: { autoSkip: false } },
                y: { beginAtZero: true, grace: '5%' }
            }
        }
    });

    // Small UI helpers: toggle period-specific and "by" filters without reloading
    const periodRadios = document.querySelectorAll('input[name="period"]');
    const quarterlyFields = document.getElementById('quarterly-fields');
    const yearlyFields = document.getElementById('yearly-fields');

    periodRadios.forEach(r => r.addEventListener('change', () => {
        if (r.checked && r.value === 'yearly') {
            yearlyFields.classList.remove('hidden');
            quarterlyFields.classList.add('hidden');
        } else if (r.checked && r.value === 'quarterly') {
            quarterlyFields.classList.remove('hidden');
            yearlyFields.classList.add('hidden');
        }
    }));

    const byRadios = document.querySelectorAll('input[name="by"]');
    const typeFilter = document.getElementById('type-filter');
    const actionFilter = document.getElementById('action-filter');

    byRadios.forEach(r => r.addEventListener('change', () => {
        if (r.checked && r.value === 'action') {
            actionFilter.classList.remove('hidden');
            typeFilter.classList.add('hidden');
        } else if (r.checked && r.value === 'type') {
            typeFilter.classList.remove('hidden');
            actionFilter.classList.add('hidden');
        }
    }));
})();
</script>
@endsection