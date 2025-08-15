@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Property History</h1>
    <p class="text-zinc-500 mb-6">{{ $address }}</p>

    @if($results->isEmpty())
        <p>No transactions found for this property.</p>
    @else
        <table class="min-w-full text-sm border border-zinc-200 rounded-md">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Price</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Tenure</th>
                    <th class="px-3 py-2">Primary</th>
                    <th class="px-3 py-2">Secondary</th>
                    <th class="px-3 py-2">Street</th>
                    <th class="px-3 py-2">Post Code</th>
                    <th class="px-3 py-2">County</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $row)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ \Carbon\Carbon::parse($row->Date)->format('d-m-Y') }}</td>
                    <td class="px-3 py-2">£{{ number_format($row->Price) }}</td>
                    <td class="px-3 py-2">
                        @if($row->PropertyType === 'D')
                            Detached
                        @elseif($row->PropertyType === 'T')
                            Terraced
                        @elseif($row->PropertyType === 'S')
                            Semi-Detached
                        @elseif($row->PropertyType === 'F')
                            Flat
                        @else
                            {{ $row->PropertyType }}
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($row->Duration === 'F')
                            Freehold
                        @elseif($row->Duration === 'L')
                            Leasehold
                        @else
                            N/A
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
                        {{ $row->Postcode }}
                    </td>
                    <td class="px-3 py-2">
                        {{ $row->County }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

<div class="my-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-xl font-bold mb-4">Price History of this property</h2>
        <canvas id="priceHistoryChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-xl font-bold mb-4">Average Price of property in this Post Code</h2>
        <canvas id="postcodePriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-xl font-bold mb-4">Average Price of property in this County</h2>
        <canvas id="countyPriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-xl font-bold mb-4">Property Types in this County</h2>
        <canvas id="countyPropertyTypesChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('priceHistoryChart').getContext('2d');
const priceData = @json($priceHistory->pluck('avg_price'));
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($priceHistory->pluck('year')),
        datasets: [{
            label: 'Sale Price (£)',
            data: priceData,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'blue';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Format as currency with commas
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

const ctxPostcode = document.getElementById('postcodePriceChart').getContext('2d');
const postcodePriceData = @json($postcodePriceHistory->pluck('avg_price'));
new Chart(ctxPostcode, {
    type: 'line',
    data: {
        labels: @json($postcodePriceHistory->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: postcodePriceData,
            borderColor: 'rgb(255, 159, 64)',
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'orange';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Format as currency with commas
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

const ctxCounty = document.getElementById('countyPriceChart').getContext('2d');
const countyPriceData = @json($countyPriceHistory->pluck('avg_price'));
new Chart(ctxCounty, {
    type: 'line',
    data: {
        labels: @json($countyPriceHistory->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: countyPriceData,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'teal';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Format as currency with commas
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
<script>
const ctxCountyTypes = document.getElementById('countyPropertyTypesChart').getContext('2d');
const countyTypeLabels = @json($countyPropertyTypes->pluck('label'));
const countyTypeCounts = @json($countyPropertyTypes->pluck('value'));
const barColors = [
    'rgba(54, 162, 235, 0.7)',  // blue
    'rgba(255, 99, 132, 0.7)',  // red
    'rgba(255, 206, 86, 0.7)',  // yellow
    'rgba(75, 192, 192, 0.7)',  // teal
    'rgba(153, 102, 255, 0.7)', // purple
    'rgba(255, 159, 64, 0.7)',  // orange
    'rgba(99, 255, 132, 0.7)',  // green
    'rgba(160, 160, 160, 0.7)'  // grey
];
new Chart(ctxCountyTypes, {
    type: 'bar',
    data: {
        labels: countyTypeLabels,
        datasets: [{
            label: 'Count',
            data: countyTypeCounts,
            backgroundColor: barColors.slice(0, countyTypeLabels.length),
            borderColor: barColors.slice(0, countyTypeLabels.length).map(c => c.replace('0.7', '1')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Property Types in this County'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Count'
                },
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<!-- Notes -->
<div class="text-sm text-zinc-500">
    <h2 class="font-bold mb-4">Notes:</h2>
    <ol class="list-decimal list-inside space-y-1 pl-4">
        <li>Other Property Type relates to properties such as mixed-use, converted barns, lighthouses or unusual property that does not fit in standard Detached, Semi, Terraced or Flat.</li>
    </ol>    
</div>

</div>

@endsection