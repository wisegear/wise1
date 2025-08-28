@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-5xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                The dashboard allows various options and search patters by address variables.  Land registry data is provided monthly.  In total there are over 32m records dating back to 1995.  Given the large dataset
                all of the static charts that are not interactive, therefore will not change during the month are cached so that visitors do not have to wait for queries to run.
            </p>
            <div class="mt-2 flex flex-wrap gap-2"> <!-- Avoids unset in css -->
                <a href="/property/search" class="standard-button">Individual Property Search</a>
                <a href="/property/outer-prime-london" class="standard-button">Outer Prime London</a>
                <a href="/property/prime-central-london" class="standard-button">Prime Central London</a>
                <a href="/property/ultra-prime-central-london" class="standard-button">Ultra Prime Central London</a>
            </div>
        </div>
    </section>

<!-- Charts -->

<div class="flex justify-center text-rose-400 text-sm mb-4 font-semibold">Note that the current year in the charts below is only a part year therefore more data to come before the year is complete.</div>
<div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <canvas id="salesChart" class="w-full h-full"></canvas>
    </div>
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <canvas id="topSaleChart" class="w-full h-full"></canvas>
    </div>
    <div class="border p-4 bg-white rounded-lg shadow h-80 md:h-80 lg:h-96 md:col-span-2 overflow-hidden">
        <canvas id="p90AvgTop5Chart" class="w-full h-full"></canvas>
    </div>
</div>

<div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2 mt-6">
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-medium text-zinc-700 mb-2 text-center">Sales Volume YoY % Change</h3>
        <canvas id="salesYoyBar" class="w-full h-full"></canvas>
    </div>
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-medium text-zinc-700 mb-2 text-center">Avg Price YoY % Change</h3>
        <canvas id="avgYoyBar" class="w-full h-full"></canvas>
    </div>
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-medium text-zinc-700 mb-2 text-center">90th Percentile YoY % Change</h3>
        <canvas id="p90YoyBar" class="w-full h-full"></canvas>
    </div>
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-medium text-zinc-700 mb-2 text-center">Top 5% Avg YoY % Change</h3>
        <canvas id="top5YoyBar" class="w-full h-full"></canvas>
    </div>
</div>

@php
    // England & Wales series alignment
    $ewYears = $avgPriceByYear->pluck('year');
    $ewP90Map = $ewP90->keyBy('year');
    $ewTop5Map = $ewTop5->keyBy('year');
    $ewP90Series = $ewYears->map(function($y) use ($ewP90Map){ return optional($ewP90Map->get($y))->p90_price; });
    $ewTop5Series = $ewYears->map(function($y) use ($ewTop5Map){ return optional($ewTop5Map->get($y))->top5_avg; });
    $ewTopSaleMap = $ewTopSalePerYear->keyBy('year');
    $ewTopSaleSeries = $ewYears->map(function($y) use ($ewTopSaleMap){ return optional($ewTopSaleMap->get($y))->top_sale; });

    // Build a lightweight map year -> [{rn, price, postcode, date}, ... up to 3] for tooltip
    $ewTop3ByYearArr = isset($ewTop3PerYear)
        ? $ewTop3PerYear->groupBy('year')->map(function($g){
            return $g->sortBy('rn')->map(function($row){
                return [
                    'rn' => (int) ($row->rn ?? 0),
                    'price' => (int) ($row->Price ?? 0),
                    'postcode' => $row->Postcode ?? null,
                    'date' => $row->Date ?? null,
                ];
            })->values();
        })
        : collect();

    // === Year-over-Year % change (aligned to $ewYears) ===
    $avgMap = $avgPriceByYear->keyBy('year');
    $avgSeries = $ewYears->map(fn($y) => optional($avgMap->get($y))->avg_price);

    $avgYoY = collect();
    $p90YoY = collect();
    $top5YoY = collect();
    for ($i = 0; $i < count($ewYears); $i++) {
        if ($i === 0) {
            $avgYoY->push(null);
            $p90YoY->push(null);
            $top5YoY->push(null);
            continue;
        }
        $prev = $i - 1;
        $prevAvg = (float) ($avgSeries[$prev] ?? 0);
        $currAvg = (float) ($avgSeries[$i] ?? 0);
        $prevP90 = (float) ($ewP90Series[$prev] ?? 0);
        $currP90 = (float) ($ewP90Series[$i] ?? 0);
        $prevTop5 = (float) ($ewTop5Series[$prev] ?? 0);
        $currTop5 = (float) ($ewTop5Series[$i] ?? 0);

        $avgYoY->push(($prevAvg > 0) ? (($currAvg - $prevAvg) / $prevAvg) * 100 : null);
        $p90YoY->push(($prevP90 > 0) ? (($currP90 - $prevP90) / $prevP90) * 100 : null);
        $top5YoY->push(($prevTop5 > 0) ? (($currTop5 - $prevTop5) / $prevTop5) * 100 : null);
    }

    // Sales volume YoY % (aligned to $ewYears)
    $salesMap = $salesByYear->keyBy('year');
    $salesSeries = $ewYears->map(fn($y) => optional($salesMap->get($y))->total);
    $salesYoY = collect();
    for ($i = 0; $i < count($ewYears); $i++) {
        if ($i === 0) { $salesYoY->push(null); continue; }
        $prev = $i - 1;
        $prevSales = (float) ($salesSeries[$prev] ?? 0);
        $currSales = (float) ($salesSeries[$i] ?? 0);
        $salesYoY->push(($prevSales > 0) ? (($currSales - $prevSales) / $prevSales) * 100 : null);
    }

    // === Axis helpers to tighten top-row charts ===
    $salesMinVal = (int) collect($salesByYear->pluck('total'))->min();
    $topSaleMinVal = (int) collect($ewTopSaleSeries)->filter(fn($v) => !is_null($v))->min();
    $p90BlockMinVal = (int) collect([
        (int) collect($avgPriceByYear->pluck('avg_price'))->min(),
        (int) collect($ewP90Series)->filter(fn($v) => !is_null($v))->min(),
        (int) collect($ewTop5Series)->filter(fn($v) => !is_null($v))->min(),
    ])->min();
@endphp

<script>
    // === Axis minimums for tighter y-axes ===
    const salesMinVal   = {!! json_encode($salesMinVal) !!};
    const topSaleMinVal = {!! json_encode($topSaleMinVal) !!};
    const p90BlockMinVal = {!! json_encode($p90BlockMinVal) !!};

    const ctx = document.getElementById('salesChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($salesByYear->pluck('year')) !!},
            datasets: [{
                label: 'Number of Sales per Year across England & Wales',
                data: {!! json_encode($salesByYear->pluck('total')) !!},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1,
                pointBackgroundColor: function(ctx) {
                    const index = ctx.dataIndex;
                    const data = ctx.dataset.data;
                    if (index === 0) return 'rgb(54, 162, 235)';
                    return data[index] < data[index-1] ? 'red' : 'rgb(54, 162, 235)';
                },
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 12, right: 12, bottom: 8, left: 12 } },
            scales: {
                x: {
                    offset: false,
                    ticks: {
                        callback: function(value, index, ticks) {
                            const lbl = this.getLabelForValue(value);
                            const clean = String(lbl).replace(/,/g, '');
                            // Show every second year only
                            return (index % 2 === 0) ? clean : '';
                        },
                        padding: 4,
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: false
                    }
                },
                y: {
                    beginAtZero: false,
                    suggestedMin: Math.max(0, salesMinVal * 0.9)
                }
            }
        }
    });
</script>

<script>
    // Map of year -> [{rn, price, postcode, date}, ...]
    const ewTop3ByYear = {!! json_encode($ewTop3ByYearArr, JSON_UNESCAPED_UNICODE) !!};
</script>

<script>
    const ctxTopSale = document.getElementById('topSaleChart').getContext('2d');

    // Build scatter points with explicit {x: year, y: top_sale}
    const scatterYears = {!! json_encode($avgPriceByYear->pluck('year')) !!}.map(s => parseInt(String(s).replace(/,/g,''), 10));
    const scatterYvals = {!! json_encode($ewTopSaleSeries) !!};
    const scatterData = scatterYears.map((y, i) => ({ x: y, y: scatterYvals[i] }));

    new Chart(ctxTopSale, {
        type: 'scatter',
        data: {
            // labels not needed when using point objects with linear x-scale
            datasets: [
                {
                    label: 'Largest Sale (hover over points to see top 3)',
                    data: scatterData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    showLine: false,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointStyle: 'circle'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 12, right: 12, bottom: 8, left: 12 } },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        // Show the year as the primary label
                        label: function(context) {
                            return 'Year ' + context.parsed.x;
                        },
                        afterBody: function(items) {
                            if (!items.length) return [];
                            const year = String(items[0].parsed.x);
                            const rows = ewTop3ByYear[year] || [];
                            const nf = new Intl.NumberFormat('en-GB');
                            if (!rows.length) return ['No data for top 3.'];
                            return rows.map((r, i) => {
                                const price = '£' + nf.format(r.price || 0);
                                const pc = r.postcode ? ` – ${r.postcode}` : '';
                                const dt = r.date ? (() => {
                                    const raw = String(r.date).split(' ')[0];
                                    const parts = raw.includes('-') ? raw.split('-') : raw.split('/');
                                    if (parts.length === 3) {
                                        // assume incoming is YYYY-MM-DD or YYYY/MM/DD
                                        const [yyyy, mm, dd] = parts;
                                        return ` (${dd}/${mm}/${yyyy})`;
                                    }
                                    return ` (${raw})`;
                                })() : '';
                                return `Top ${i+1}: ${price}${pc}${dt}`;
                            });
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'linear',
                    min: Math.min(...scatterYears),
                    max: Math.max(...scatterYears),
                    offset: false,
                    ticks: {
                        stepSize: 2,
                        callback: function(value) { return String(value); },
                        padding: 4,
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: false,
                        precision: 0
                    }
                },
                y: {
                    beginAtZero: false,
                    suggestedMin: Math.max(0, topSaleMinVal * 0.9)
                }
            }
        }
    });
</script>

<script>
    const ctxP90AvgTop5 = document.getElementById('p90AvgTop5Chart').getContext('2d');
    new Chart(ctxP90AvgTop5, {
        type: 'line',
        data: {
            labels: {!! json_encode($avgPriceByYear->pluck('year')) !!},
            datasets: [
                {
                    label: 'Average Sale Price',
                    data: {!! json_encode($avgPriceByYear->pluck('avg_price')) !!},
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    pointRadius: 3,
                    pointHoverRadius: 4
                },
                {
                    label: '90th Percentile',
                    data: {!! json_encode($ewP90Series) !!},
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.15)',
                    borderDash: [6,1],
                    tension: 0.1,
                    pointRadius: 4,
                    pointHoverRadius: 5
                },
                {
                    label: 'Top 5% Average',
                    data: {!! json_encode($ewTop5Series) !!},
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.15)',
                    tension: 0.1,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 12, right: 12, bottom: 8, left: 12 } },
            plugins: { legend: { position: 'top' } },
            scales: { 
                x: { 
                    offset: false,
                    ticks: { 
                        callback: function(value, index, ticks) {
                            const lbl = this.getLabelForValue(value);
                            const clean = String(lbl).replace(/,/g, '');
                            // Show every second year only
                            return (index % 2 === 0) ? clean : '';
                        },
                        padding: 4,
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: false
                    } 
                }, 
                y: {
                    beginAtZero: false,
                    suggestedMin: Math.max(0, p90BlockMinVal * 0.9)
                }
            }
        }
    });
</script>

<script>
    const labelsYoY = {!! json_encode($ewYears) !!}.map(String).map(s => s.replace(/,/g,''));

    function barColorsFrom(values) {
        return values.map(v => {
            if (v === null || typeof v === 'undefined') return 'rgba(150,150,150,0.6)';
            return v >= 0 ? 'rgba(34,197,94,0.7)' : 'rgba(239,68,68,0.7)'; // green for up, red for down
        });
    }

    function borderColorsFrom(values) {
        return values.map(v => {
            if (v === null || typeof v === 'undefined') return 'rgba(150,150,150,1)';
            return v >= 0 ? 'rgba(34,197,94,1)' : 'rgba(239,68,68,1)';
        });
    }

    function makeYoyBar(canvasId, series, label) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsYoY,
                datasets: [{
                    label: label,
                    data: series,
                    backgroundColor: barColorsFrom(series),
                    borderColor: borderColorsFrom(series),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 12, right: 12, bottom: 28, left: 12 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const v = context.parsed.y;
                                if (v === null || typeof v === 'undefined') return 'No prior year';
                                const sign = v >= 0 ? '+' : '';
                                return `${sign}${v.toFixed(2)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value, index, ticks) {
                                const lbl = this.getLabelForValue(value);
                                const clean = String(lbl).replace(/,/g, '');
                                // Show every second year only
                                return (index % 2 === 0) ? clean : '';
                            },
                            padding: 12,
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) { return value + '%'; }
                        },
                        grid: { drawBorder: false }
                    }
                }
            }
        });
    }

    const avgSeriesYoY  = {!! json_encode($avgYoY->map(fn($v) => is_null($v) ? null : round($v, 2))) !!};
    const p90SeriesYoY  = {!! json_encode($p90YoY->map(fn($v) => is_null($v) ? null : round($v, 2))) !!};
    const top5SeriesYoY = {!! json_encode($top5YoY->map(fn($v) => is_null($v) ? null : round($v, 2))) !!};
    const salesSeriesYoY = {!! json_encode($salesYoY->map(fn($v) => is_null($v) ? null : round($v, 2))) !!};

    // Build charts
    (function(){
        // Average
        makeYoyBar('avgYoyBar', avgSeriesYoY, 'Avg Price YoY %');
        // Patch dataset with the right series inside the function-created chart
        // 90th Percentile
        const ctxP90 = document.getElementById('p90YoyBar').getContext('2d');
        new Chart(ctxP90, {
            type: 'bar',
            data: {
                labels: labelsYoY,
                datasets: [{
                    data: p90SeriesYoY,
                    backgroundColor: barColorsFrom(p90SeriesYoY),
                    borderColor: borderColorsFrom(p90SeriesYoY),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 12, right: 12, bottom: 28, left: 12 } },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ const v=c.parsed.y; if (v==null) return 'No prior year'; const s=v>=0?'+':''; return `${s}${v.toFixed(2)}%`; } } } },
                scales: {
                    x: { 
                        ticks: { 
                            callback: function(value, index, ticks) {
                                const lbl = this.getLabelForValue(value);
                                const clean = String(lbl).replace(/,/g, '');
                                // Show every second year only
                                return (index % 2 === 0) ? clean : '';
                            },
                            padding: 12, maxRotation: 0, minRotation: 0, autoSkip: false 
                        } 
                    },
                    y: { beginAtZero: false, ticks: { callback: v => v + '%' } }
                }
            }
        });

        // Top 5%
        const ctxTop5 = document.getElementById('top5YoyBar').getContext('2d');
        new Chart(ctxTop5, {
            type: 'bar',
            data: {
                labels: labelsYoY,
                datasets: [{
                    data: top5SeriesYoY,
                    backgroundColor: barColorsFrom(top5SeriesYoY),
                    borderColor: borderColorsFrom(top5SeriesYoY),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 12, right: 12, bottom: 28, left: 12 } },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ const v=c.parsed.y; if (v==null) return 'No prior year'; const s=v>=0?'+':''; return `${s}${v.toFixed(2)}%`; } } } },
                scales: {
                    x: { 
                        ticks: { 
                            callback: function(value, index, ticks) {
                                const lbl = this.getLabelForValue(value);
                                const clean = String(lbl).replace(/,/g, '');
                                // Show every second year only
                                return (index % 2 === 0) ? clean : '';
                            },
                            padding: 12, maxRotation: 0, minRotation: 0, autoSkip: false 
                        } 
                    },
                    y: { beginAtZero: false, ticks: { callback: v => v + '%' } }
                }
            }
        });

        // Sales Volume
        makeYoyBar('salesYoyBar', salesSeriesYoY, 'Sales Volume YoY %');
    })();
</script>

</div>

@endsection