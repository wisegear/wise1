@extends('layouts.app')

@php
    $areaTitle = ucfirst($type) . ': ' . ucfirst(strtolower($areaName));
    $yearsCount = isset($byYear) ? (is_countable($byYear) ? count($byYear) : $byYear->count()) : 0;
    $salesCount = isset($summary) ? (int) ($summary->sales_count ?? 0) : 0;
    $canIndex = $salesCount > 0 && $yearsCount > 0;
    $metaSales = $salesCount ? number_format($salesCount) : '0';
    $metaAvg = isset($summary) && !is_null($summary->avg_price ?? null) ? '£' . number_format((float) $summary->avg_price, 0) : 'n/a';
    $metaYears = $yearsCount ? $yearsCount . ' years' : 'limited history';
    $metaDesc = "{$areaTitle} house price stats: average {$metaAvg}, {$metaSales} sales, {$metaYears} of data. Trends, types, and tenure breakdowns.";
    $areaLabel = ucfirst(strtolower($areaName));
    $typeLabel = ucfirst($type);

    $formatMoney = function ($value) {
        return is_null($value) ? 'n/a' : '£' . number_format((float) $value, 0);
    };
    $formatCount = function ($value) {
        return number_format((int) $value);
    };
    $percentChange = function ($current, $previous) {
        if (! $previous || $previous == 0) {
            return null;
        }
        return (($current - $previous) / $previous) * 100;
    };

    $overallInsight = "No yearly sales data is available yet for {$areaLabel}.";
    if (! empty($byYear) && $byYear->count() > 0) {
        $latest = $byYear->last();
        $prev = $byYear->count() > 1 ? $byYear->get($byYear->count() - 2) : null;
        $overallInsight = "In {$latest->year}, the average {$typeLabel} sale price in {$areaLabel} was {$formatMoney($latest->avg_price)} across {$formatCount($latest->sales_count)} sales";
        $pct = $prev ? $percentChange($latest->avg_price ?? null, $prev->avg_price ?? null) : null;
        if (! is_null($pct)) {
            $overallInsight .= ', ' . ($pct >= 0 ? 'up ' : 'down ') . number_format(abs($pct), 1) . '% vs ' . (int) $prev->year . '.';
        } else {
            $overallInsight .= '.';
        }
    }

    $buildTypeInsight = function ($series, $label) use ($areaLabel, $formatMoney, $formatCount, $percentChange) {
        if (empty($series) || count($series) === 0) {
            return "No {$label} sales data is available yet for {$areaLabel}.";
        }
        $series = collect($series);
        $latest = $series->last();
        $prev = $series->count() > 1 ? $series->get($series->count() - 2) : null;
        $text = "In {$latest->year}, {$label} homes in {$areaLabel} averaged {$formatMoney($latest->avg_price)} across {$formatCount($latest->sales_count)} sales";
        $pct = $prev ? $percentChange($latest->avg_price ?? null, $prev->avg_price ?? null) : null;
        if (! is_null($pct)) {
            $text .= ', ' . ($pct >= 0 ? 'up ' : 'down ') . number_format(abs($pct), 1) . '% vs ' . (int) $prev->year . '.';
        } else {
            $text .= '.';
        }
        return $text;
    };

    $typeInsights = [];
    if (! empty($byType)) {
        foreach ($byType as $key => $meta) {
            $typeInsights[$key] = $buildTypeInsight($meta['series'] ?? [], $meta['label'] ?? ucfirst($key));
        }
    }

    $typeSplitInsight = "No property type split data is available yet for {$areaLabel}.";
    $typeSplitYears = collect($propertyTypeSplit['years'] ?? []);
    if ($typeSplitYears->count() > 0) {
        $idx = $typeSplitYears->count() - 1;
        $year = (int) $typeSplitYears->get($idx);
        $types = $propertyTypeSplit['types'] ?? [];
        $total = 0;
        $topCount = 0;
        $topLabel = null;
        foreach ($types as $typeData) {
            $count = $typeData['counts'][$idx] ?? 0;
            $total += $count;
            if ($count > $topCount) {
                $topCount = $count;
                $topLabel = $typeData['label'] ?? 'Unknown';
            }
        }
        if ($total > 0 && $topLabel) {
            $pct = ($topCount / $total) * 100;
            $typeSplitInsight = "In {$year}, {$topLabel} accounted for " . number_format($pct, 1) . "% of sales in {$areaLabel}.";
        }
    }

    $newBuildInsight = "No new build data is available yet for {$areaLabel}.";
    $newBuildYears = collect($newBuildSplit['years'] ?? []);
    if ($newBuildYears->count() > 0) {
        $idx = $newBuildYears->count() - 1;
        $year = (int) $newBuildYears->get($idx);
        $newCount = (int) ($newBuildSplit['series']['Y']['counts'][$idx] ?? 0);
        $existingCount = (int) ($newBuildSplit['series']['N']['counts'][$idx] ?? 0);
        $total = $newCount + $existingCount;
        if ($total > 0) {
            $pct = ($newCount / $total) * 100;
            $newBuildInsight = "In {$year}, new build homes made up " . number_format($pct, 1) . "% of sales in {$areaLabel} (" . $formatCount($newCount) . " of " . $formatCount($total) . ").";
        }
    }
@endphp

@section('title', "{$areaTitle} | PropertyResearch.uk")
@section('meta')
    <meta name="description" content="{{ $metaDesc }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="{{ $canIndex ? 'index, follow' : 'noindex, follow' }}">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4">

    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-6xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900"><span class="text-lime-600">{{ ucfirst($type) }}</span>: {{ ucfirst(strtolower($areaName)) }}</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">This page provides a detailed breakdown of historical property sales, prices, and market composition for {{ ucfirst(strtolower($areaName)) }}, based on Land Registry transaction data.</p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/area.svg') }}" alt="Area" class="w-32 h-auto">
        </div>
    </section>

    @if($summary)
        <div class="mt-4 grid gap-4 grid-cols-1 md:grid-cols-4 text-sm">
            <div class="border rounded p-3 bg-white shadow-lg">
                <div class="text-xs text-zinc-500">Total sales</div>
                <div class="text-lg font-semibold">
                    {{ number_format((float) $summary->sales_count) }}
                </div>
            </div>
            <div class="border rounded p-3 bg-white shadow-lg">
                <div class="text-xs text-zinc-500">Average price</div>
                <div class="text-lg font-semibold">
                    £{{ number_format((float) $summary->avg_price, 0) }}
                </div>
            </div>
            <div class="border rounded p-3 bg-white shadow-lg">
                <div class="text-xs text-zinc-500">Lowest recorded</div>
                <div class="text-lg font-semibold">
                    £{{ number_format((float) $summary->min_price, 0) }}
                </div>
            </div>
            <div class="border rounded p-3 bg-white shadow-lg">
                <div class="text-xs text-zinc-500">Highest recorded</div>
                <div class="text-lg font-semibold">
                    £{{ number_format((float) $summary->max_price, 0) }}
                </div>
            </div>
        </div>
    @endif

    <div class="mt-10">

        <div class="border rounded bg-white p-4 shadow-lg">
            <div class="mb-2">
                <h3 class="text-base font-semibold text-gray-900 text-center">Average sale price and number of sales per year for {{ $typeLabel }} in {{ $areaLabel }}</h3>
                <p class="text-xs text-zinc-600 text-center">{{ $overallInsight }}</p>
            </div>
            <div class="w-full">
                <canvas id="areaPriceSalesChart" class="w-full max-h-[360px]"></canvas>
                <div class="chart-empty hidden py-8 text-center text-sm text-zinc-500">No sales data available for this area yet.</div>
            </div>
        </div>
    </div>

    <div class="mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">Sales split by property type in {{ $areaLabel }}</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $typeSplitInsight }}</p>
                </div>
                <canvas id="areaTypeSplitChart" class="w-full max-h-[260px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No property type split data available for this area.</div>
            </div>

            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">New build vs existing sales in {{ $areaLabel }}</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $newBuildInsight }}</p>
                </div>
                <canvas id="newBuildSplitChart" class="w-full max-h-[260px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No new build vs existing data available for this area.</div>
            </div>
        </div>
    </div>

    <div class="mt-10">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">Detached homes in {{ $areaLabel }} - average price and number of sales</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $typeInsights['detached'] ?? "No detached sales data is available yet for {$areaLabel}." }}</p>
                </div>
                <canvas id="areaTypeChart_detached" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No detached sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">Semi-detached homes in {{ $areaLabel }} - average price and number of sales</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $typeInsights['semi'] ?? "No semi-detached sales data is available yet for {$areaLabel}." }}</p>
                </div>
                <canvas id="areaTypeChart_semi" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No semi-detached sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">Terraced homes in {{ $areaLabel }} - average price and number of sales</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $typeInsights['terraced'] ?? "No terraced sales data is available yet for {$areaLabel}." }}</p>
                </div>
                <canvas id="areaTypeChart_terraced" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No terraced sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <div class="mb-2">
                    <h3 class="text-base font-semibold text-gray-900 text-center">Flats in {{ $areaLabel }} - average price and number of sales</h3>
                    <p class="text-xs text-zinc-600 text-center">{{ $typeInsights['flat'] ?? "No flat sales data is available yet for {$areaLabel}." }}</p>
                </div>
                <canvas id="areaTypeChart_flat" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No flat sales data available for this area.</div>
            </div>
        </div>
    </div>

</div>

@php
    $chartYears       = $byYear->pluck('year')->map(fn($y) => (int) $y)->values();
    $chartAvgPrices   = $byYear->pluck('avg_price')->map(fn($p) => round($p))->values();
    $chartSalesCounts = $byYear->pluck('sales_count')->map(fn($c) => (int) $c)->values();

    $typeChartData = [];
    if (!empty($byType)) {
        foreach ($byType as $key => $meta) {
            $series = $meta['series'];
            $typeChartData[$key] = [
                'label'       => $meta['label'],
                'years'       => $series->pluck('year')->map(fn($y) => (int) $y)->values(),
                'avgPrices'   => $series->pluck('avg_price')->map(fn($p) => round($p))->values(),
                'salesCounts' => $series->pluck('sales_count')->map(fn($c) => (int) $c)->values(),
            ];
        }
    }
@endphp

@push('scripts')
<script>
    (function () {
        const years       = @json($chartYears);
        const avgPrices   = @json($chartAvgPrices);
        const salesCounts = @json($chartSalesCounts);


        const showEmpty = (canvasId, message) => {
            const el = document.getElementById(canvasId);
            if (!el) return;
            el.classList.add('hidden');
            const msg = el.parentElement?.querySelector('.chart-empty');
            if (msg) {
                msg.textContent = message || msg.textContent;
                msg.classList.remove('hidden');
            }
        };

        if (!years.length) {
            showEmpty('areaPriceSalesChart', 'No sales data available for this area yet.');
            return;
        }

        const ctx = document.getElementById('areaPriceSalesChart');
        if (!ctx) {
            return;
        }

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: years,
                datasets: [
                    {
                        type: 'line',
                        label: 'Average price',
                        data: avgPrices,
                        yAxisID: 'y1',
                        borderWidth: 2,
                        tension: 0.1,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBorderWidth: 2,
                        pointBackgroundColor: function(context) {
                            const index = context.dataIndex;
                            const value = context.dataset.data[index];
                            if (index === 0) {
                                return 'rgba(54, 162, 235, 1)';
                            }
                            const prev = context.dataset.data[index - 1];
                            if (prev == null || typeof prev === 'undefined') {
                                return 'rgba(54, 162, 235, 1)';
                            }
                            return value < prev ? '#ef4444' : 'rgba(54, 162, 235, 1)';
                        },
                        pointBorderColor: function(context) {
                            const index = context.dataIndex;
                            const value = context.dataset.data[index];
                            if (index === 0) {
                                return 'rgba(54, 162, 235, 1)';
                            }
                            const prev = context.dataset.data[index - 1];
                            if (prev == null || typeof prev === 'undefined') {
                                return 'rgba(54, 162, 235, 1)';
                            }
                            return value < prev ? '#ef4444' : 'rgba(54, 162, 235, 1)';
                        },
                    },
                    {
                        type: 'bar',
                        label: 'Number of sales',
                        data: salesCounts,
                        yAxisID: 'y',
                        borderWidth: 1,
                        backgroundColor: 'rgba(54, 162, 235, 0.4)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        pointStyle: 'rect',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            boxHeight: 10,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const dsLabel = context.dataset.label || '';
                                const value = context.parsed.y;
                                if (context.dataset.yAxisID === 'y1') {
                                    try {
                                        return dsLabel + ': £' + value.toLocaleString('en-GB');
                                    } catch (e) {
                                        return dsLabel + ': £' + value;
                                    }
                                }
                                return dsLabel + ': ' + value.toLocaleString('en-GB');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales count'
                        },
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    y1: {
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average price (£)'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: function(value) {
                                try {
                                    return '£' + value.toLocaleString('en-GB');
                                } catch (e) {
                                    return value;
                                }
                            }
                        }
                    }
                }
            }
        });

        const typeSeries = @json($typeChartData);

        const expectedKeys = ['detached', 'semi', 'terraced', 'flat'];
        expectedKeys.forEach(function (key) {
            const cfg = typeSeries[key];
            if (!cfg || !cfg.years.length) {
                showEmpty('areaTypeChart_' + key, 'No ' + key.replace('-', ' ') + ' sales data available for this area.');
                return;
            }

            const hasSales = (cfg.salesCounts || []).some(v => v && v > 0);
            const hasPrices = (cfg.avgPrices || []).some(v => v && v > 0);
            if (!hasSales && !hasPrices) {
                showEmpty('areaTypeChart_' + key, 'No ' + key.replace('-', ' ') + ' sales data available for this area.');
                return;
            }

            const canvasId = 'areaTypeChart_' + key;
            const el = document.getElementById(canvasId);
            if (!el) {
                return;
            }

            new Chart(el, {
                type: 'bar',
                data: {
                    labels: cfg.years,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Average price',
                            data: cfg.avgPrices,
                            yAxisID: 'y1',
                            borderWidth: 2,
                            tension: 0.1,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBorderWidth: 2,
                            pointBackgroundColor: function(context) {
                                const index = context.dataIndex;
                                const value = context.dataset.data[index];
                                if (index === 0) {
                                    return 'rgba(54, 162, 235, 1)';
                                }
                                const prev = context.dataset.data[index - 1];
                                if (prev == null || typeof prev === 'undefined') {
                                    return 'rgba(54, 162, 235, 1)';
                                }
                                return value < prev ? '#ef4444' : 'rgba(54, 162, 235, 1)';
                            },
                            pointBorderColor: function(context) {
                                const index = context.dataIndex;
                                const value = context.dataset.data[index];
                                if (index === 0) {
                                    return 'rgba(54, 162, 235, 1)';
                                }
                                const prev = context.dataset.data[index - 1];
                                if (prev == null || typeof prev === 'undefined') {
                                    return 'rgba(54, 162, 235, 1)';
                                }
                                return value < prev ? '#ef4444' : 'rgba(54, 162, 235, 1)';
                            },
                        },
                        {
                            type: 'bar',
                            label: 'Number of sales',
                            data: cfg.salesCounts,
                            yAxisID: 'y',
                            borderWidth: 1,
                            backgroundColor: 'rgba(54, 162, 235, 0.4)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            pointStyle: 'rect',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                boxHeight: 10,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const dsLabel = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    if (context.dataset.yAxisID === 'y1') {
                                        try {
                                            return dsLabel + ': £' + value.toLocaleString('en-GB');
                                        } catch (e) {
                                            return dsLabel + ': £' + value;
                                        }
                                    }
                                    return dsLabel + ': ' + value.toLocaleString('en-GB');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Sales count'
                            },
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        y1: {
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Average price (£)'
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: function(value) {
                                    try {
                                        return '£' + value.toLocaleString('en-GB');
                                    } catch (e) {
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                }
            });
        });

        // --- Property type split chart ---
        const typeSplit = @json($propertyTypeSplit);
        const typeSplitEl = document.getElementById('areaTypeSplitChart');
        if (!typeSplit || !typeSplit.years || !typeSplit.years.length) {
            showEmpty('areaTypeSplitChart', 'No property type split data available for this area.');
        } else if (typeSplitEl) {
            const typeColorMap = {
                'Detached': 'oklch(64.8% 0.2 131.684)',
                'Semi-detached': 'oklch(64.5% 0.246 16.439)',
                'Terraced': 'oklch(66.6% 0.179 58.318)',
                'Flat': 'rgba(54, 162, 235, 0.8)',
                'Other': 'rgba(75, 192, 192, 0.8)'
            };

            const datasets = [];
            Object.keys(typeSplit.types).forEach(function (key) {
                const label = typeSplit.types[key].label;
                const color = typeColorMap[label] || 'rgba(148, 163, 184, 0.8)';

                datasets.push({
                    type: 'bar',
                    label: label,
                    data: typeSplit.types[key].counts,
                    borderWidth: 1,
                    backgroundColor: color,
                    borderColor: color,
                    stacked: true,
                });
            });

            new Chart(typeSplitEl, {
                type: 'bar',
                data: {
                    labels: typeSplit.years,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {},
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Sales count' } }
                    }
                }
            });
        }

        // --- New build split chart ---
        const newSplit = @json($newBuildSplit);
        const newSplitEl = document.getElementById('newBuildSplitChart');
        if (!newSplit || !newSplit.years || !newSplit.years.length) {
            showEmpty('newBuildSplitChart', 'No new build vs existing data available for this area.');
        } else if (newSplitEl) {
            const nbColors = {
                Y: '#22c55e', // New build
                N: '#6b7280', // Existing
            };
            const ds2 = [];
            Object.keys(newSplit.series).forEach(function(flag) {
                ds2.push({
                    type: 'bar',
                    label: newSplit.series[flag].label,
                    data: newSplit.series[flag].counts,
                    borderWidth: 1,
                    backgroundColor: nbColors[flag] || 'rgba(148,163,184,0.8)',
                    borderColor: nbColors[flag] || 'rgba(148,163,184,1)',
                    stacked: true,
                });
            });

            new Chart(newSplitEl, {
                type: 'bar',
                data: {
                    labels: newSplit.years,
                    datasets: ds2
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {},
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Sales count' } }
                    }
                }
            });
        }
    })();
</script>
@endpush
@endsection
