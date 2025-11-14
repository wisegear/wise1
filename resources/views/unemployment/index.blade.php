@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    @php
        $sorted = $series->sortBy('date')->values();
        $latestRow = $latest ?? $sorted->last();
        $firstRow  = $sorted->first();
    @endphp

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">UK Unemployment (millions)</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                @if($latestRow)
                    Latest estimate: <span class="font-semibold">{{ number_format((float)$latestRow->rate, 1) }} million</span>
                    <span class="text-gray-600">for</span>
                    <span class="font-medium">{{ optional($latestRow->date)->format('M Y') }}</span>.
                @else
                    No unemployment data available yet.
                @endif
            </p>
            @if($firstRow && $latestRow && $firstRow !== $latestRow)
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Series coverage: {{ optional($firstRow->date)->format('Y') }} to {{ optional($latestRow->date)->format('Y') }}.
                </p>
            @endif

            @if(!is_null($yearOnYearDelta) && $previousYear)
                <p class="mt-2 text-sm leading-6">
                    <span class="text-gray-700">Change versus the same month a year earlier (millions):</span>
                    @php
                        $yoyUp = $yearOnYearDelta > 0;
                        $yoyDown = $yearOnYearDelta < 0;
                    @endphp
                    <span class="font-semibold {{ $yoyUp ? 'text-red-600' : ($yoyDown ? 'text-emerald-700' : 'text-zinc-900') }}">
                        @if($yoyUp)
                            +{{ number_format(abs($yearOnYearDelta), 1) }} million
                        @elseif($yoyDown)
                            -{{ number_format(abs($yearOnYearDelta), 1) }} million
                        @else
                            No change
                        @endif
                    </span>
                    <span class="text-gray-600">
                        ({{ optional($previousYear->date)->format('M Y') }}: {{ number_format((float)$previousYear->rate, 1) }} million).
                    </span>
                </p>
            @endif
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/interest.svg') }}" alt="Unemployment" class="w-64 h-auto">
        </div>
    </section>

    {{-- Chart --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">Unemployment (millions) over time (hover for details)</div>
            @if($sorted->isEmpty())
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="unemploymentChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- Unemployment spikes explanation --}}
    <section class="mb-6">
        <details class="group rounded-lg border border-amber-200 bg-amber-50 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-amber-900 flex items-center justify-between">
                Understanding unemployment spikes over time
                <span class="text-xs text-amber-700 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-600 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            <div class="px-5 pb-5 pt-3 text-sm text-zinc-800">
                <p>
                    This series shows the estimated number of people <span class="font-semibold">unemployed in the UK (millions)</span>,
                    based on the Labour Force Survey. Big swings in unemployment usually line up with major economic shocks
                    or long periods of weak growth.
                </p>
                <p class="mt-2">Some of the more notable rises in unemployment typically coincide with:</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    <li>
                        <span class="font-medium">Early 1980s recession:</span>
                        tight monetary policy, industrial restructuring and high interest rates pushed unemployment sharply higher
                        as the economy adjusted.
                    </li>
                    <li>
                        <span class="font-medium">Early 1990s downturn:</span>
                        the housing market correction, high real interest rates and the UK’s exit from the ERM contributed to a
                        prolonged period of elevated unemployment.
                    </li>
                    <li>
                        <span class="font-medium">2008–2010 global financial crisis:</span>
                        bank failures, a collapse in credit and a deep global recession led to widespread job losses, with
                        unemployment rising even after output began to stabilise.
                    </li>
                    <li>
                        <span class="font-medium">2020–2021 COVID-19 shock:</span>
                        lockdowns and public-health restrictions caused a sudden fall in activity; furlough schemes softened the
                        headline unemployment spike, but some sectors still saw significant job losses.
                    </li>
                </ul>
                <p class="mt-2 text-xs text-amber-900">
                    For the housing market, sharp and sustained rises in unemployment tend to weigh on transaction volumes,
                    confidence and forced sales, while low and stable unemployment generally supports demand and mortgage
                    repayment capacity.
                </p>
            </div>
        </details>
    </section>

    @if($sorted->isNotEmpty())
        @php
            $maxRate   = $sorted->max('rate');
            $minRate   = $sorted->min('rate');
            $maxRow    = $sorted->filter(fn($r) => (float)$r->rate === (float)$maxRate)->last();
            $minRow    = $sorted->filter(fn($r) => (float)$r->rate === (float)$minRate)->last();

            // Last 12 months average (based on last available month)
            $lastDate = $sorted->last()->date ?? null;
            $last12Start = $lastDate ? $lastDate->copy()->subYear()->addMonth()->startOfMonth() : null; // inclusive of 12-month window
            $last12 = $last12Start
                ? $sorted->filter(fn($r) => $r->date >= $last12Start && $r->date <= $lastDate)
                : collect();
            $last12Avg = $last12->isNotEmpty() ? $last12->avg('rate') : null;
        @endphp

        {{-- Summary stats cards --}}
        <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Highest recorded</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ number_format((float) $maxRate, 1) }} million
                </div>
                @if($maxRow)
                    <div class="text-sm text-gray-600">
                        in {{ optional($maxRow->date)->format('M Y') }}
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Lowest recorded</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ number_format((float) $minRate, 1) }} million
                </div>
                @if($minRow)
                    <div class="text-sm text-gray-600">
                        in {{ optional($minRow->date)->format('M Y') }}
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Last 12 months (average)</div>
                @if(!is_null($last12Avg) && $lastDate)
                    <div class="mt-1 text-2xl font-semibold">{{ number_format((float)$last12Avg, 1) }} million</div>
                    <div class="text-sm text-gray-600">
                        {{ $last12Start->format('M Y') }} – {{ $lastDate->format('M Y') }}
                    </div>
                @else
                    <div class="mt-1 text-sm text-gray-600">Not enough data yet.</div>
                @endif
            </div>
        </section>
    @endif

    {{-- Data table --}}
    <div class="overflow-hidden border-gray-200 bg-white shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Month</th>
                        <th class="border-b border-gray-200 px-4 py-2">Unemployed (millions)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($series->sortByDesc('date') as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ optional($row->date)->format('M Y') }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                {{ number_format((float)$row->rate, 1) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-500">No data to display.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const labels = {!! $labels !!};
    const data   = {!! $values !!};
    const yearLabels = labels.map(l => {
        const s = String(l);
        const m = s.match(/(19|20)\d{2}/);
        return m ? m[0] : s.slice(0, 4);
    });

    const el = document.getElementById('unemploymentChart');
    if (!el || !labels || !data) return;

    const ctx = el.getContext('2d');
    if (window._unemploymentChart) { window._unemploymentChart.destroy(); }
    if (el.parentElement) { el.height = el.parentElement.clientHeight; }

    window._unemploymentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: yearLabels,
            datasets: [{
                label: 'Unemployed (millions)',
                data: data,
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
            resizeDelay: 150,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                decimation: { enabled: true, algorithm: 'min-max' },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(items) {
                            if (!items || !items.length) return '';
                            const idx = items[0].dataIndex;
                            const raw = labels[idx];
                            return String(raw);
                        },
                        label: function(ctx) {
                            const v = (ctx.parsed.y ?? 0).toFixed(1);
                            return ` ${v} million`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(value, index) {
                            return yearLabels[index];
                        },
                        maxTicksLimit: 14
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '5%',
                    ticks: {
                        callback: (v) => v
                    }
                }
            }
        }
    });
})();
</script>
@endsection
