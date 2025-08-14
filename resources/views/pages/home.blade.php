@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-semibold">Land Registry Lookup</h1>
    <p class="text-zinc-500 text-sm">There are currently <span class="text-lime-700">{{ number_format($records) }} </span> records in this table.  Only England & Wales are currently available</p>
    <p class="mb-10 text-zinc-500 text-sm">Data covers the period from January 1995 to June 2025</p>

    {{-- Search form --}}
    <div class="flex justify-center">
        <form method="GET" action="{{ route('home') }}" class="mb-6 w-1/2 mx-auto">
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
                    class="bg-lime-600 hover:bg-lime-500 text-white font-medium px-4 py-2 rounded-md transition"
                >
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
    @endphpg
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
                <span class="font-medium">New{!! $dirBadge('NewBuild') !!}</span>
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
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Locality') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Locality{!! $dirBadge('Locality') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Locality', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'Locality', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('TownCity') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">Town/City{!! $dirBadge('TownCity') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'TownCity', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'TownCity', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('District') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">District{!! $dirBadge('District') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'District', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'District', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
            </div>
        </th>
        <th class="px-3 py-2 whitespace-nowrap {{ $thClass('County') }}">
            <div class="flex items-center gap-1">
                <span class="font-medium">County{!! $dirBadge('County') !!}</span>
                <a href="{{ route('home', array_merge($base, ['sort' => 'County', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                <a href="{{ route('home', array_merge($base, ['sort' => 'County', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
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
                                    @if(empty($row->PAON))
                                        N/A
                                    @else
                                        {{ $row->PAON }}
                                    @endif
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
                                    @if(empty($row->Locality))
                                        N/A
                                    @else
                                        {{ $row->Locality }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->TownCity }}
                                </td>        
                                <td class="px-3 py-2">
                                    {{ $row->District }}
                                </td>   
                                <td class="px-3 py-2">
                                    {{ $row->County }}
                                </td>                           
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $results->links() }}
            </div>
        @endif
    @endif
</div>

@if(empty($postcode))
<div class="max-w-7xl mx-auto my-10 grid grid-cols-1 md:grid-cols-2 gap-6">
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