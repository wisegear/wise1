@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 md:py-12">

    {{-- Hero / summary card --}}
    @php
        $hp = $seriesData['LPMVTVX'] ?? null;   // House purchase
        $re = $seriesData['LPMB4B3'] ?? null;   // Remortgaging
        $tt = $seriesData['LPMB3C8'] ?? null;   // Total approvals
        $os = $seriesData['LPMB4B4'] ?? null;   // Other secured

        $formatMonth = function($d) {
            try { return \Illuminate\Support\Carbon::parse($d)->isoFormat('MMM YYYY'); }
            catch (\Throwable $e) { return (string) $d; }
        };
    @endphp

    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Mortgage Approvals</h1>
            @if(!empty($latestPeriod))
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest month: <span class="font-medium">{{ $formatMonth($latestPeriod) }}</span>
                </p>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">House purchase</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($hp && $hp['latest'])
                            {{ number_format((int) $hp['latest']->value) }}
                            @if(!is_null($hp['delta']))
                                <span class="{{ $hp['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $hp['delta'] >= 0 ? '+' : '' }}{{ number_format($hp['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Remortgaging</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($re && $re['latest'])
                            {{ number_format((int) $re['latest']->value) }}
                            @if(!is_null($re['delta']))
                                <span class="{{ $re['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $re['delta'] >= 0 ? '+' : '' }}{{ number_format($re['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Other secured</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($os && $os['latest'])
                            {{ number_format((int) $os['latest']->value) }}
                            @if(!is_null($os['delta']))
                                <span class="{{ $os['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $os['delta'] >= 0 ? '+' : '' }}{{ number_format($os['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total approvals</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if($tt && $tt['latest'])
                            {{ number_format((int) $tt['latest']->value) }}
                            @if(!is_null($tt['delta']))
                                <span class="{{ $tt['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-sm font-medium">
                                    ({{ $tt['delta'] >= 0 ? '+' : '' }}{{ number_format($tt['delta']) }})
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gradient-to-br from-sky-100 to-sky-300 blur-2xl"></div>
    </section>

    {{-- Combined chart --}}
    @php
        $labels     = $tt['labels'] ?? collect();
        $dataTotal  = $tt['values'] ?? collect();
        $dataHP     = $hp['values'] ?? collect();
        $dataRe     = $re['values'] ?? collect();
        $dataOther  = $os['values'] ?? collect();
    @endphp
    <section class="mb-6">
        <div class="border p-4 bg-white rounded shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">Approvals over time</div>
            @if(($labels instanceof \Illuminate\Support\Collection ? $labels->isEmpty() : empty($labels)))
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="approvalsChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- Yearly totals (all years) --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Year</th>
                        <th class="border-b border-gray-200 px-4 py-2">House purchase</th>
                        <th class="border-b border-gray-200 px-4 py-2">Remortgaging</th>
                        <th class="border-b border-gray-200 px-4 py-2">Other secured</th>
                        <th class="border-b border-gray-200 px-4 py-2">Total approvals</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($yearTable ?? []) as $y)
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">{{ $y['year'] }}</td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMVTVX']) ? number_format((int)$y['LPMVTVX']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB4B3']) ? number_format((int)$y['LPMB4B3']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB4B4']) ? number_format((int)$y['LPMB4B4']) : '—' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-2">
                                {{ isset($y['LPMB3C8']) ? number_format((int)$y['LPMB3C8']) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No yearly data to display.</td>
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
    const labels = @json($labels ?? []);
    const total  = @json($dataTotal ?? []);
    const hp     = @json($dataHP ?? []);
    const re     = @json($dataRe ?? []);
    const other  = @json($dataOther ?? []);

    const el = document.getElementById('approvalsChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    if (window._approvalsChart) { window._approvalsChart.destroy(); }
    if (el.parentElement) { el.height = el.parentElement.clientHeight; }

    window._approvalsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total',
                    data: total,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.15)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'House purchase',
                    data: hp,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Remortgaging',
                    data: re,
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Other secured',
                    data: other,
                    borderColor: 'rgb(234, 179, 8)',
                    backgroundColor: 'rgba(234, 179, 8, 0.10)',
                    borderWidth: 1.5,
                    tension: 0.1,
                    pointRadius: 0,
                    fill: false
                },
            ]
        },
        options: {
            responsive: true,
            resizeDelay: 150,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: true, position: 'bottom' },
                decimation: { enabled: true, algorithm: 'min-max' },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: (items) => items && items.length ? items[0].label : '',
                        label: (ctx) => {
                            const v = ctx.parsed.y ?? 0;
                            return ` ${ctx.dataset.label}: ${Number(v).toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    offset: false,
                    ticks: {
                        autoSkip: true,
                        includeBounds: true,
                        maxTicksLimit: 14,
                        callback: function(value, index) {
                            const scale = this;
                            const raw = (scale.getLabelForValue ? scale.getLabelForValue(value) : (labels[index] ?? value));
                            // labels are "YYYY-MM"; show year only on axis
                            return String(raw).slice(0, 4);
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '5%',
                    ticks: {
                        callback: (v) => {
                            const n = Number(v);
                            return isFinite(n) ? n.toLocaleString() : v;
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endsection