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
    $relatedAreas = [];
    $areasFile = public_path('data/property_districts.json');
    if (is_file($areasFile)) {
        $areas = json_decode(file_get_contents($areasFile), true);
        if (is_array($areas)) {
            $typed = array_values(array_filter($areas, function ($a) use ($type) {
                return isset($a['type']) && $a['type'] === $type;
            }));
            $norm = function ($s) {
                return strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
            };
            $current = $norm($areaName);
            $related = array_values(array_filter($typed, function ($a) use ($current, $norm) {
                return $norm($a['name'] ?? '') !== $current;
            }));
            usort($related, function ($a, $b) {
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            });
            $relatedAreas = array_slice($related, 0, 12);
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
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-6xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900"><span class="text-lime-600">{{ ucfirst($type) }}</span>: {{ ucfirst(strtolower($areaName)) }}</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">Similiar to an individual property search this page gives you a clear overview of a specific area in England/Wales.</p>
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
            <div class="w-full">
                <canvas id="areaPriceSalesChart" class="w-full max-h-[360px]"></canvas>
                <div class="chart-empty hidden py-8 text-center text-sm text-zinc-500">No sales data available for this area yet.</div>
            </div>
        </div>
    </div>

    <div class="mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeSplitChart" class="w-full max-h-[260px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No property type split data available for this area.</div>
            </div>

            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="newBuildSplitChart" class="w-full max-h-[260px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No new build vs existing data available for this area.</div>
            </div>
        </div>
    </div>

    <div class="mt-10">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_detached" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No detached sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_semi" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No semi-detached sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_terraced" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No terraced sales data available for this area.</div>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_flat" class="w-full max-h-[320px]"></canvas>
                <div class="chart-empty hidden py-6 text-center text-sm text-zinc-500">No flat sales data available for this area.</div>
            </div>
        </div>
    </div>

    @if(!empty($relatedAreas))
        <div class="mt-10 rounded-lg border border-zinc-200 bg-white p-4 shadow-lg">
            <h2 class="text-sm font-semibold text-zinc-700 mb-3">Explore nearby {{ ucfirst($type) }} areas</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($relatedAreas as $area)
                    <a href="{{ $area['path'] ?? '#' }}"
                       class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-700 hover:bg-zinc-100">
                        {{ $area['label'] ?? $area['name'] ?? 'Area' }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

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
                    title: {
                        display: true,
                        text: 'Average sale price and number of sales per year for this {{ ucfirst($type) }}',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
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
                        title: {
                            display: true,
                            text: cfg.label + ' – average sale price and number of sales per year',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: {
                                top: 8,
                                bottom: 12
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
                    plugins: {
                        title: {
                            display: true,
                            text: 'Sales split by property type',
                            font: { size: 16, weight: 'bold' }
                        }
                    },
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
                    plugins: {
                        title: {
                            display: true,
                            text: 'New build vs existing sales',
                            font: { size: 16, weight: 'bold' }
                        }
                    },
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
