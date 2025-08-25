@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                The dashboard allows various options and search patters by address variables.  Land registry data is provided monthly.  In total there are over 35m records dating back to 1995.  Given the large dataset
                all of the static charts that are not interactive, therefore will not change during the month are cached so that visitors do not have to wait for queries to run.
            </p>
            <div class="mt-6 space-x-4">
                <a href="/property/search" class="border text-sm p-2 rounded-sm bg-zinc-200">Property Search</a>
                <a href="/property/prime-central-london" class="border text-sm p-2 rounded-sm bg-zinc-200">Prime Central London</a>
                <a href="/property/ultra-prime-central-london" class="border text-sm p-2 rounded-sm bg-zinc-200">Ultra Prime Central London</a>
            </div>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gradient-to-br from-lime-100 to-lime-400 blur-2xl"></div>
    </section>

<!-- Charts -->

<div class="flex justify-center text-zinc-500 text-sm mb-4">Note that the current year in the charts below is only a part year therefore more data to come before the year is complete.</div>
<div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="salesChart" class="w-full h-96"></canvas>
    </div>
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="avgPriceChart" class="w-full h-96"></canvas>
    </div>
</div>

<div class="max-w-7xl mx-auto my-10 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="primeCentralSalesChart" class="w-full h-96"></canvas>
    </div>
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="avgPricePrimeChart" class="w-full h-96"></canvas>
    </div>
</div>

<div class="max-w-7xl mx-auto my-10 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="ultraPrimeSalesChart" class="w-full h-96"></canvas>
    </div>
    <div class="border p-4 bg-white rounded shadow h-full">
        <canvas id="avgPriceUltraPrimeChart" class="w-full h-96"></canvas>
    </div>
</div>

@php
    // England & Wales series alignment
    $ewYears = $avgPriceByYear->pluck('year');
    $ewP90Map = $ewP90->keyBy('year');
    $ewTop5Map = $ewTop5->keyBy('year');
    $ewP90Series = $ewYears->map(function($y) use ($ewP90Map){ return optional($ewP90Map->get($y))->p90_price; });
    $ewTop5Series = $ewYears->map(function($y) use ($ewTop5Map){ return optional($ewTop5Map->get($y))->top5_avg; });

    // Prime Central alignment
    $primeYears = $avgPricePrimeCentralByYear->pluck('year');
    $primeP90Map = $primeP90->keyBy('year');
    $primeTop5Map = $primeTop5->keyBy('year');
    $primeP90Series = $primeYears->map(function($y) use ($primeP90Map){ return optional($primeP90Map->get($y))->p90_price; });
    $primeTop5Series = $primeYears->map(function($y) use ($primeTop5Map){ return optional($primeTop5Map->get($y))->top5_avg; });

    // Ultra Prime alignment
    $ultraYears = $avgPriceUltraPrimeByYear->pluck('year');
    $ultraP90Map = $ultraP90->keyBy('year');
    $ultraTop5Map = $ultraTop5->keyBy('year');
    $ultraP90Series = $ultraYears->map(function($y) use ($ultraP90Map){ return optional($ultraP90Map->get($y))->p90_price; });
    $ultraTop5Series = $ultraYears->map(function($y) use ($ultraTop5Map){ return optional($ultraTop5Map->get($y))->top5_avg; });
@endphp

<script>
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
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<script>
    const ctxAvg = document.getElementById('avgPriceChart').getContext('2d');

    new Chart(ctxAvg, {
        type: 'line',
        data: {
            labels: {!! json_encode($avgPriceByYear->pluck('year')) !!},
            datasets: [
                {
                    label: 'Average Price per Year (E&W, Cat A)',
                    data: {!! json_encode($avgPriceByYear->pluck('avg_price')) !!},
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 4
                },
                {
                    label: '90th Percentile (E&W, Cat A)',
                    data: {!! json_encode($ewP90Series) !!},
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.15)',
                    borderDash: [6,4],
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointHitRadius: 6,
                },
                {
                    label: 'Top 5% Avg (E&W, Cat A)',
                    data: {!! json_encode($ewTop5Series) !!},
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.15)',
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointHitRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
</script>

<script>
    const ctxPrime = document.getElementById('avgPricePrimeChart').getContext('2d');

    new Chart(ctxPrime, {
        type: 'line',
        data: {
            labels: {!! json_encode($avgPricePrimeCentralByYear->pluck('year')) !!},
            datasets: [
                {
                    label: 'Average (Prime Central, Cat A)',
                    data: {!! json_encode($avgPricePrimeCentralByYear->pluck('avg_price')) !!},
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 4
                },
                {
                    label: '90th Percentile (Prime Central, Cat A)',
                    data: {!! json_encode($primeP90Series) !!},
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.15)',
                    borderDash: [6,4],
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointHitRadius: 6,
                },
                {
                    label: 'Top 5% Avg (Prime Central, Cat A)',
                    data: {!! json_encode($primeTop5Series) !!},
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.15)',
                    tension: 0.1,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointHitRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
</script>


<script>
const primeCentralSalesCtx = document.getElementById('primeCentralSalesChart').getContext('2d');
new Chart(primeCentralSalesCtx, {
    type: 'line',
    data: {
        labels: @json($primeCentralSalesByYear->pluck('year')),
        datasets: [{
            label: 'Prime Central London Sales',
            data: @json($primeCentralSalesByYear->pluck('total_sales')),
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
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});
</script>

<script>
const avgPriceUltraPrimeCtx = document.getElementById('avgPriceUltraPrimeChart').getContext('2d');
new Chart(avgPriceUltraPrimeCtx, {
    type: 'line',
    data: {
        labels: @json($avgPriceUltraPrimeByYear->pluck('year')),
        datasets: [
            {
                label: 'Average (Ultra Prime, Cat A)',
                data: @json($avgPriceUltraPrimeByYear->pluck('avg_price')),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1,
                pointRadius: 2,
                pointHoverRadius: 4
            },
            {
                label: '90th Percentile (Ultra Prime, Cat A)',
                data: {!! json_encode($ultraP90Series) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.15)',
                borderDash: [6,4],
                tension: 0.1,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointHitRadius: 6,
            },
            {
                label: 'Top 5% Avg (Ultra Prime, Cat A)',
                data: {!! json_encode($ultraTop5Series) !!},
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.15)',
                tension: 0.1,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointHitRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});
</script>

<script>
const ultraPrimeSalesCtx = document.getElementById('ultraPrimeSalesChart').getContext('2d');
new Chart(ultraPrimeSalesCtx, {
    type: 'line',
    data: {
        labels: @json($ultraPrimeSalesByYear->pluck('year')),
        datasets: [{
            label: 'Ultra Prime London Sales',
            data: @json($ultraPrimeSalesByYear->pluck('total_sales')),
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
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});
</script>

</div>

@endsection