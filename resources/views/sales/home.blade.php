@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Search</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                There are currently <span class="text-lime-700">{{ number_format($records) }}</span> records in this table.
                <span class="font-semibold">Only England &amp; Wales are currently available</span>
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from January 1995 to June 2025
            </p>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gradient-to-br from-lime-100 to-emerald-100 blur-2xl"></div>
    </section>

    {{-- Search form --}}
    <div class="flex justify-center">
        <form method="GET" action="{{ route('sales.home') }}" class="mb-10 w-1/2 mx-auto">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="postcode" class="block text-sm font-medium mb-1">Enter a postcode below to get details of all properties sold from 1995.</label>
                    <input
                        id="postcode"
                        name="postcode"
                        type="text"
                        value="{{ old('postcode', $postcode ?? '') }}"
                        placeholder="e.g. WR5 3EU"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-lime-500"
                    />
                    @error('postcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="bg-lime-600 hover:bg-lime-500 text-white font-medium px-4 py-2 rounded-md transition">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Results --}}
    @if(isset($results))
        @if($results->count() === 0)
            <div class="border border-zinc-200 rounded-md p-4 text-zinc-600">
                No results for <span class="font-semibold">{{ $postcode }}</span>.
            </div>
        @else
            <div class="flex justify-center mb-4 text-sm text-zinc-500">
                <p>Click on the magnifying glass on the right hand side to get more detail about a specific property</p>
            </div>
            <div class="overflow-x-auto border border-zinc-200 rounded-md">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50">
    @php
        $currentSort = $sort ?? request('sort', 'Date');
        $currentDir  = $dir ?? request('dir', 'desc');
        $base = [];
        if (!empty($postcode)) { $base['postcode'] = $postcode; }
        $dirBadge = function($key) use ($currentSort, $currentDir) {
            return $currentSort === $key ? ' ('.strtoupper($currentDir).')' : '';
        };
        $thClass = function($key) use ($currentSort) {
            return $currentSort === $key ? 'bg-lime-100 font-bold' : '';
        };
    @endphp
    <tr class="text-left">
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Date') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Date{!! $dirBadge('Date') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Date', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Date', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Price') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Price{!! $dirBadge('Price') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Price', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Price', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PropertyType') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Type{!! $dirBadge('PropertyType') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PropertyType', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PropertyType', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('NewBuild') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">New Build?{!! $dirBadge('NewBuild') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'NewBuild', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'NewBuild', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Duration') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Tenure{!! $dirBadge('Duration') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Duration', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Duration', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PAON') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Primary{!! $dirBadge('PAON') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PAON', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PAON', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('SAON') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Secondary{!! $dirBadge('SAON') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'SAON', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'SAON', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Street') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Street{!! $dirBadge('Street') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Street', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Street', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('County') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">County{!! $dirBadge('County') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'County', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'County', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PPDCategoryType') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Status{!! $dirBadge('PPDCategoryType') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PPDCategoryType', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'PPDCategoryType', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
    </tr>
</thead>
                    <tbody>
                        @foreach($results as $row)
                            <tr class="border-t">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ optional($row->Date)->format('d-m-Y') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    £{{ number_format($row->Price) }}
                                </td>
                                <td class="px-3 py-2">
                                    @if($row->PropertyType === 'D')
                                        Detached
                                    @elseif($row->PropertyType === 'T')
                                        Terraced
                                    @elseif($row->PropertyType === 'S')
                                        Semi-Detached
                                    @elseif($row->PropertyType === 'F')
                                        Flat
                                    @elseif($row->PropertyType === 'O')
                                        Other
                                    @else
                                        {{ $row->PropertyType }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row->NewBuild === 'Y')
                                        Yes
                                    @elseif($row->NewBuild === 'N')
                                        No
                                    @else
                                        {{ $row->NewBuild }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row->Duration === 'F')
                                        Freehold
                                    @elseif($row->Duration === 'L')
                                        Leasehold
                                    @else
                                        {{ $row->Duration }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->PAON }}
                                </td>
                                <td class="px-3 py-2">
                                    @if(empty($row->SAON))
                                        N/A
                                    @else
                                        {{ $row->SAON }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->Street }}
                                </td> 
                                <td class="px-3 py-2">
                                    {{ $row->County }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->PPDCategoryType }}
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        href="{{ route('property.show', [
                                            'postcode' => $row->Postcode ?? '',
                                            'paon'     => $row->PAON,
                                            'street'   => $row->Street ?? '',
                                            'saon'     => $row->SAON ?? ''
                                        ]) }}"
                                        class="bg-lime-600 hover:bg-lime-500 text-white p-2 rounded inline-flex items-center"
                                        title="View property details"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentcolor" viewBox="0 0 20 20" width="16" height="16">
                                            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- was: <div class="mt-4 flex justify-center"> --}}
            <div class="mt-4">
                <div class="w-full">
                    {{ $results->links() }}
                </div>
            </div>

            <div class="mt-10 text-zinc-500 text-sm">
                <h2 class="font-bold mb-2">Notes:</h2>
                <p class="mb-2">The status column indicates a clean sale at market value on an arms legnth basis if set to A.</p>
                <p>If set to B.  repossessions / power-of-sale, buy-to-let where identifiable by a mortgage, sales to companies or social landlords, transfers of part, 
                    transactions not clearly at full market value, or where the property type is unknown.</p>
            </div>
        @endif
    @endif
</div>

@if(empty($postcode))
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

@endif

@endsection