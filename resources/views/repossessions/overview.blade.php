

@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">UK Repossessions (Annual)</h1>

            @if($latest)
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest year: <span class="font-semibold">{{ $latest->year }}</span>
                    — <span class="font-semibold">{{ number_format($latest->total) }}</span> cases.
                </p>
            @else
                <p class="mt-2 text-sm leading-6 text-gray-700">No repossession data available yet.</p>
            @endif

            @if($previous)
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Year-on-year change:
                    @php $up = $yoy > 0; $down = $yoy < 0; @endphp
                    <span class="font-semibold {{ $up ? 'text-red-600' : ($down ? 'text-emerald-700' : 'text-zinc-900') }}">
                        @if($up)
                            +{{ number_format(abs($yoy)) }}
                        @elseif($down)
                            -{{ number_format(abs($yoy)) }}
                        @else
                            No change
                        @endif
                    </span>
                    <span class="text-gray-600">(vs {{ $previous->year }}: {{ number_format($previous->total) }})</span>
                </p>
            @endif
        </div>

        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/repos.svg') }}" alt="Repossessions" class="w-64 h-auto">
        </div>
    </section>

    {{-- Chart --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">Repossessions over time (annual total)</div>
            @if($yearly->isEmpty())
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96"><canvas id="repoChart"></canvas></div>
            @endif
        </div>
    </section>

    @if($yearly->isNotEmpty())
        @php
            $max = $yearly->max('total');
            $min = $yearly->min('total');
            $maxRow = $yearly->firstWhere('total', $max);
            $minRow = $yearly->firstWhere('total', $min);

            // Last 5-year average (if available)
            $latestYear = $latest->year ?? null;
            $start = $latestYear ? $latestYear - 4 : null;
            $last5 = $start ? $yearly->whereBetween('year', [$start, $latestYear]) : collect();
            $avg5 = $last5->isNotEmpty() ? round($last5->avg('total')) : null;
        @endphp

        <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Highest recorded</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($max) }}</div>
                <div class="text-sm text-gray-600">in {{ $maxRow->year }}</div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Lowest recorded</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($min) }}</div>
                <div class="text-sm text-gray-600">in {{ $minRow->year }}</div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Last 5-year average</div>
                @if($avg5)
                    <div class="mt-1 text-2xl font-semibold">{{ number_format($avg5) }}</div>
                    <div class="text-sm text-gray-600">{{ $start }}–{{ $latestYear }}</div>
                @else
                    <div class="mt-1 text-sm text-gray-600">Not enough data.</div>
                @endif
            </div>
        </section>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden border-gray-200 bg-white shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Year</th>
                        <th class="border-b border-gray-200 px-4 py-2">Total repossessions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($yearly->sortByDesc('year') as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="border-b border-gray-100 px-4 py-2">{{ $row->year }}</td>
                        <td class="border-b border-gray-100 px-4 py-2 font-medium">{{ number_format($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">No data available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const labels = {!! $labels !!};
    const values = {!! $values !!};

    const el = document.getElementById('repoChart');
    if (!el) return;

    const ctx = el.getContext('2d');
    if (window._repoChart) window._repoChart.destroy();
    if (el.parentElement) el.height = el.parentElement.clientHeight;

    window._repoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Repossessions (annual cases)',
                data: values,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.15)',
                fill: true,
                tension: 0.15,
                pointRadius: 0,
                pointHoverRadius: 3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(ctx) {
                            return ` ${ctx.parsed.y.toLocaleString()} cases`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grace: '5%'
                }
            }
        }
    });
})();
</script>
@endsection