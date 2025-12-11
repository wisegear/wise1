@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-semibold mb-2">
        {{ ucfirst($type) }}: {{ $areaName }}
    </h1>

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
            </div>
        </div>
    </div>

    <div class="mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeSplitChart" class="w-full max-h-[260px]"></canvas>
            </div>

            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="newBuildSplitChart" class="w-full max-h-[260px]"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-10">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_detached" class="w-full max-h-[320px]"></canvas>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_semi" class="w-full max-h-[320px]"></canvas>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_terraced" class="w-full max-h-[320px]"></canvas>
            </div>
            <div class="border rounded bg-white p-4 shadow-lg">
                <canvas id="areaTypeChart_flat" class="w-full max-h-[320px]"></canvas>
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


        if (!years.length) {
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

        Object.keys(typeSeries || {}).forEach(function (key) {
            const cfg = typeSeries[key];
            if (!cfg.years.length) {
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
        if (typeSplit && typeSplit.years && typeSplit.years.length) {
            const el = document.getElementById('areaTypeSplitChart');
            if (el) {
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

                new Chart(el, {
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
        }

        // --- New build split chart ---
        const newSplit = @json($newBuildSplit);
        if (newSplit && newSplit.years && newSplit.years.length) {
            const el2 = document.getElementById('newBuildSplitChart');
            if (el2) {
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

                new Chart(el2, {
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
        }
    })();
</script>
@endpush
@endsection