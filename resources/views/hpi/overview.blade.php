@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- HERO SECTION --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">UK House Price Index – National Overview</h1>

            @if($latest)
                @php
                    $latestPrice = (float) ($latest->AveragePrice ?? 0);
                    $latestChange = $latest->twelve_m_change ?? null;
                @endphp
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    Latest average UK price:
                    <span class="font-semibold">£{{ number_format($latestPrice, 0) }}</span>
                    <span class="text-gray-600">for</span>
                    <span class="font-medium">
                        @php
                            try {
                                echo \Illuminate\Support\Carbon::parse($latest->Date)->format('M Y');
                            } catch (\Throwable $e) {
                                echo e($latest->Date);
                            }
                        @endphp
                    </span>.
                </p>

                @if(!is_null($latestChange))
                    <p class="mt-1 text-sm leading-6 text-gray-700">
                        12-month change:
                        @php
                            $chg = (float) $latestChange;
                            $isUp = $chg > 0; $isDown = $chg < 0;
                        @endphp
                        <span class="font-semibold {{ $isUp ? 'text-emerald-700' : ($isDown ? 'text-red-700' : 'text-zinc-900') }}">
                            @if($isUp)
                                +{{ number_format(abs($chg), 1) }}%
                            @elseif($isDown)
                                -{{ number_format(abs($chg), 1) }}%
                            @else
                                0.0%
                            @endif
                        </span>
                        <span class="text-gray-600">year-on-year</span>
                    </p>
                @endif

                @if($previous)
                    @php
                        $prevPrice = (float) ($previous->AveragePrice ?? 0);
                        $deltaPrice = $latestPrice - $prevPrice;
                        $deltaPct = $prevPrice != 0 ? ($deltaPrice / $prevPrice * 100) : null;
                    @endphp
                    <p class="mt-1 text-sm leading-6 text-gray-700">
                        Month-on-month change:
                        @if(!is_null($deltaPct))
                            <span class="font-semibold {{ $deltaPrice > 0 ? 'text-emerald-700' : ($deltaPrice < 0 ? 'text-red-700' : 'text-zinc-900') }}">
                                @if($deltaPrice > 0)
                                    +£{{ number_format(abs($deltaPrice), 0) }}
                                @elseif($deltaPrice < 0)
                                    -£{{ number_format(abs($deltaPrice), 0) }}
                                @else
                                    No change
                                @endif
                            </span>
                            <span class="text-gray-600">
                                (about {{ number_format($deltaPct, 2) }}% vs previous month)
                            </span>
                        @else
                            <span class="font-semibold text-zinc-900">n/a</span>
                        @endif
                    </p>
                @endif
            @else
                <p class="mt-2 text-sm text-gray-700">No HPI data available yet.</p>
            @endif
        </div>

        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/hpi2.svg') }}" alt="House Price Index" class="w-64 h-auto">
        </div>
    </section>

    {{-- MAIN CHART --}}
    <section class="mb-6">
        <div class="border p-4 bg-white rounded-lg shadow">
            <div class="mb-2 text-sm font-medium text-gray-700">Average UK house price over time (HPI)</div>
            @if(empty($labels))
                <p class="text-sm text-gray-500">No data available yet.</p>
            @else
                <div class="h-96">
                    <canvas id="hpiOverviewChart"></canvas>
                </div>
            @endif
        </div>
    </section>

    {{-- INFLATION-LIKE EXPLANATION FOR HPI CYCLES --}}
    <section class="mb-6">
        <details class="group rounded-lg border border-amber-200 bg-amber-50 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-amber-900 flex items-center justify-between">
                Understanding UK house price cycles
                <span class="text-xs text-amber-700 ml-3 group-open:hidden">Show</span>
                <span class="text-xs text-red-600 ml-3 hidden group-open:inline">Hide</span>
            </summary>
            <div class="px-5 pb-5 pt-3 text-sm text-zinc-800">
                <p>
                    This series tracks the <span class="font-semibold">average UK house price</span> from the official House Price Index.
                    Big swings usually line up with changes in interest rates, credit conditions and the wider economy.
                </p>
                <p class="mt-2">Some of the best-known periods in the modern UK housing cycle include:</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    <li>
                        <span class="font-medium">Late 1980s to early 1990s:</span>
                        a sharp boom followed by a crash, linked to high interest rates, the early-90s recession and a long
                        period of negative equity for many homeowners.
                    </li>
                    <li>
                        <span class="font-medium">2003–2007 credit boom and 2008–2012 correction:</span>
                        easy credit, high loan-to-value lending and strong wage and bonus growth pushed prices up before the
                        global financial crisis triggered a sharp adjustment.
                    </li>
                    <li>
                        <span class="font-medium">2020–2022 COVID and ultra-low rates period:</span>
                        stamp duty holidays, very low mortgage rates and a shift in housing preferences drove rapid price rises
                        despite broader economic uncertainty.
                    </li>
                    <li>
                        <span class="font-medium">2022 onwards — rate shock and affordability squeeze:</span>
                        the rapid rise in interest rates from late 2021/2022 has cooled activity and, in some areas, pushed
                        prices into outright decline as affordability has been squeezed.
                    </li>
                </ul>
                <p class="mt-2 text-xs text-amber-900">
                    In combination with the other indicators on this site (rates, inflation, wages, unemployment, approvals and
                    repossessions), the HPI series helps to show when the housing market is overheating, treading water or
                    starting to come under pressure.
                </p>
            </div>
        </details>
    </section>

    {{-- SIMPLE STATS CARDS --}}
    @if($latest && !empty($prices))
        @php
            $priceCollection = $prices instanceof \Illuminate\Support\Collection ? $prices : collect($prices);
            $maxPrice = (float) $priceCollection->max();
            $minPrice = (float) $priceCollection->min();
            $startPrice = (float) $priceCollection->first();
            $changeSinceStart = $latestPrice - $startPrice;
            $pctSinceStart = $startPrice != 0 ? ($changeSinceStart / $startPrice * 100) : null;
        @endphp
        <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">All-time high (UK average)</div>
                <div class="mt-1 text-2xl font-semibold">£{{ number_format($maxPrice, 0) }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Earliest point in this series</div>
                <div class="mt-1 text-2xl font-semibold">£{{ number_format($startPrice, 0) }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Change since start</div>
                @if(!is_null($pctSinceStart))
                    <div class="mt-1 text-2xl font-semibold {{ $changeSinceStart >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        @if($changeSinceStart >= 0)
                            +£{{ number_format(abs($changeSinceStart), 0) }}
                        @else
                            -£{{ number_format(abs($changeSinceStart), 0) }}
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">{{ number_format($pctSinceStart, 1) }}% over the period</div>
                @else
                    <div class="mt-1 text-sm text-gray-600">n/a</div>
                @endif
            </div>
        </section>
    @endif

    {{-- RECENT TABLE (LAST 24 MONTHS) --}}
    @if($latest)
        @php
            // Build a reversed collection for the table (most recent first) and limit to last 24 entries
            $allForTable = collect();
            // Reconstruct a small collection from labels/prices/changes assuming same order
            foreach ($labels as $idx => $lbl) {
                $allForTable->push((object) [
                    'label' => $lbl,
                    'price' => $prices[$idx] ?? null,
                    'change' => $changes[$idx] ?? null,
                ]);
            }
            $recentRows = $allForTable->reverse()->take(24);
        @endphp
        <div class="overflow-hidden border-gray-200 bg-white shadow-sm rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="border-b border-gray-200 px-4 py-2">Month</th>
                            <th class="border-b border-gray-200 px-4 py-2">Average price</th>
                            <th class="border-b border-gray-200 px-4 py-2">12m change (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentRows as $row)
                        @php
                            $dispDate = $row->label;
                            try {
                                $dispDate = \Illuminate\Support\Carbon::parse($dispDate)->format('M Y');
                            } catch (\Throwable $e) {
                                $dispDate = (string) $dispDate;
                            }
                            $chg = $row->change;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="border-b border-gray-100 px-4 py-2">{{ $dispDate }}</td>
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">£{{ number_format($row->price ?? 0, 0) }}</td>
                            <td class="border-b border-gray-100 px-4 py-2 font-medium">
                                @if(is_null($chg))
                                    <span class="text-gray-500">n/a</span>
                                @else
                                    @php $chgF = (float) $chg; @endphp
                                    <span class="{{ $chgF>0?'text-emerald-700':($chgF<0?'text-red-700':'text-zinc-900') }}">
                                        @if($chgF>0)+@endif{{ number_format($chgF, 1) }}%
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No recent data available.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const labels = @json($labels);
    const prices = @json($prices);

    const canvas = document.getElementById('hpiOverviewChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (window._hpiOverviewChart) window._hpiOverviewChart.destroy();
    if (canvas.parentElement) {
        canvas.height = canvas.parentElement.clientHeight;
    }

    // Extract year-only labels for the x-axis, but keep index mapping
    const yearLabels = labels.map(l => {
        const s = String(l);
        const m = s.match(/(19|20)\d{2}/);
        return m ? m[0] : s.slice(0, 4);
    });

    window._hpiOverviewChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: yearLabels,
            datasets: [{
                label: 'Average UK price (HPI)',
                data: prices,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.10)',
                tension: 0.15,
                pointRadius: 0,
                borderWidth: 2,
                spanGaps: true
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
                        title: function(items) {
                            if (!items || !items.length) return '';
                            const idx = items[0].dataIndex;
                            const raw = labels[idx];
                            return String(raw);
                        },
                        label: function(ctx) {
                            const v = ctx.parsed.y;
                            if (v === null || v === undefined) return ' n/a';
                            return ' £' + v.toLocaleString('en-GB', { maximumFractionDigits: 0 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxTicksLimit: 14
                    }
                },
                y: {
                    beginAtZero: false,
                    grace: '5%',
                    ticks: {
                        callback: function(value) {
                            return '£' + value.toLocaleString('en-GB', { maximumFractionDigits: 0 });
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endsection
