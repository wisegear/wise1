@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                The dashboard allows you to create search patters by address variables.  Be aware though, with 35.3m records these searches can be slow.  Caching is in use but there is only
                so much that can be cached to speed up the searches.
            </p>
            <div class="mt-6">
                <a href="/property/search" class="border text-sm p-2 rounded-sm bg-zinc-50">Property Search</a>
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
            datasets: [{
                label: 'Average Price per Year England & Wales',
                data: {!! json_encode($avgPriceByYear->pluck('avg_price')) !!},
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
            datasets: [{
                label: 'Average Price per Year - Prime Central London',
                data: {!! json_encode($avgPricePrimeCentralByYear->pluck('avg_price')) !!},
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
        datasets: [{
            label: 'Average Price per Year - Ultra Prime London',
            data: @json($avgPriceUltraPrimeByYear->pluck('avg_price')),
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