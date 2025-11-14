@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- HERO SECTION --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">UK Inflation – CPIH (12-month % change)</h1>

            @if($latest)
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest: <span class="font-semibold">{{ number_format($latest->rate, 1) }}%</span>
                    <span class="text-gray-600">for</span>
                    <span class="font-medium">{{ $latest->date->format('M Y') }}</span>.
                </p>
            @else
                <p class="mt-2 text-sm text-gray-700">No inflation data available yet.</p>
            @endif

            @if($previous)
                @php
                    $delta = $latest->rate - $previous->rate;
                    $up = $delta > 0;
                    $down = $delta < 0;
                @endphp
                <p class="mt-1 text-sm leading-6 text-gray-700">
                    Month-on-month change: 
                    <span class="font-semibold {{ $up ? 'text-red-700' : ($down ? 'text-emerald-700' : 'text-zinc-900') }}">
                        @if($up)
                            +{{ number_format(abs($delta), 1) }}%
                        @elseif($down)
                            -{{ number_format(abs($delta), 1) }}%
                        @else
                            No change
                        @endif
                    </span>
                    <span class="text-gray-600">(vs {{ $previous->date->format('M Y') }})</span>
                </p>
            @endif
        </div>

        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/interest.svg') }}" alt="Inflation" class="w-64 h-auto">
        </div>
    </section>

    {{-- MAIN CHART --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">CPIH 12‑month Inflation Rate (Monthly)</div>
            @if(empty($labels))
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="inflationChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- INFLATION SPIKES EXPLANATION --}}
    <section class="mb-6">
        <details class="group rounded-lg border border-amber-200 bg-amber-50 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-amber-900 flex items-center justify-between">
                Understanding CPIH spikes over time
                <span class="text-xs text-amber-700 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-600 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            <div class="px-5 pb-5 pt-3 text-sm text-zinc-800">
                <p>
                    The chart shows the <span class="font-semibold">12-month CPIH inflation rate</span>, which compares prices with
                    the same month a year earlier and includes owner-occupiers' housing costs. Persistent moves above or below
                    target often line up with major economic shocks.
                </p>
                <p class="mt-2">Some of the more notable periods of elevated inflation include:</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    <li>
                        <span class="font-medium">1970s–early 1980s:</span>
                        oil price shocks, wage–price spirals and loose policy led to very high inflation before aggressive
                        interest rate rises eventually brought it back under control.
                    </li>
                    <li>
                        <span class="font-medium">Early 1990s:</span>
                        the UK’s exit from the ERM, a housing downturn and shifting monetary policy frameworks created a period of
                        volatility before inflation targeting became firmly established.
                    </li>
                    <li>
                        <span class="font-medium">2008–2011:</span>
                        the global financial crisis and subsequent commodity and tax changes (for example VAT adjustments and
                        energy cost spikes) pushed inflation above target even as growth was weak.
                    </li>
                    <li>
                        <span class="font-medium">2021–2023:</span>
                        post‑COVID reopening, supply‑chain disruption and a sharp rise in global energy prices drove the most
                        pronounced inflation surge in decades, prompting rapid interest rate increases.
                    </li>
                </ul>
                <p class="mt-2 text-xs text-amber-900">
                    For housing and mortgages, sustained high CPIH usually feeds through into higher interest rates and tighter
                    real household budgets, while periods of low or falling inflation can ease pressure on borrowing costs.
                </p>
            </div>
        </details>
    </section>

    {{-- SUMMARY CARDS --}}
    @php
        $max = $all->max('rate');
        $min = $all->min('rate');
        $maxRow = $all->filter(fn($r) => $r->rate == $max)->last();
        $minRow = $all->filter(fn($r) => $r->rate == $min)->last();
    @endphp

    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Highest CPIH</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($max, 1) }}%</div>
            <div class="text-sm text-gray-600">in {{ $maxRow->date->format('M Y') }}</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Lowest CPIH</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($min, 1) }}%</div>
            <div class="text-sm text-gray-600">in {{ $minRow->date->format('M Y') }}</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Latest annual average</div>
            @php
                $currentYear = $latest->date->format('Y');
                $yearSet = $all->filter(fn($r) => $r->date->format('Y') === $currentYear);
                $avgYear = $yearSet->isNotEmpty() ? number_format($yearSet->avg('rate'), 1) : null;
            @endphp
            @if($avgYear)
                <div class="mt-1 text-2xl font-semibold">{{ $avgYear }}%</div>
                <div class="text-sm text-gray-600">{{ $currentYear }}</div>
            @else
                <div class="mt-1 text-sm text-gray-600">Not enough data.</div>
            @endif
        </div>

    </section>

    {{-- TABLE --}}
    <div class="overflow-hidden border-gray-200 bg-white shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-2">Month</th>
                        <th class="border-b border-gray-200 px-4 py-2">CPIH (%)</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($all->sortByDesc('date') as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="border-b border-gray-100 px-4 py-2">{{ $row->date->format('M Y') }}</td>
                        <td class="border-b border-gray-100 px-4 py-2 font-medium">{{ number_format($row->rate, 1) }}%</td>
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
    const labels = @json($labels);
    const values = @json($values);

    const el = document.getElementById('inflationChart');
    if (!el) return;

    const ctx = el.getContext('2d');
    if (window._inflationChart) window._inflationChart.destroy();
    if (el.parentElement) el.height = el.parentElement.clientHeight;

    window._inflationChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'CPIH (12-month % change)',
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
                            return ` ${ctx.parsed.y.toFixed(1)}%`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(value, index) {
                            const raw = this.getLabelForValue ? this.getLabelForValue(value) : (labels[index] ?? value);
                            return String(raw).slice(0, 4); // YYYY
                        },
                        maxTicksLimit: 14
                    }
                },
                y: {
                    beginAtZero: false,
                    grace: '5%'
                }
            }
        }
    });
})(); 
</script>
@endsection
