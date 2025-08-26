@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Property History</h1>
    @php
        $firstRow = $results->first();
        $parts = [];
        $norm = function($s) { return strtolower(trim((string) $s)); };
        $seen = [];

        // Core address lines
        if (!empty(trim($firstRow->PAON ?? ''))) { $parts[] = trim($firstRow->PAON); }
        if (!empty(trim($firstRow->SAON ?? ''))) { $parts[] = trim($firstRow->SAON); }
        if (!empty(trim($firstRow->Street ?? ''))) { $parts[] = trim($firstRow->Street); }

        // Location hierarchy with de-duplication
        $locality = trim((string) ($firstRow->Locality ?? ''));
        $town     = trim((string) ($firstRow->TownCity ?? ''));
        $district = trim((string) ($firstRow->District ?? ''));
        $county   = trim((string) ($firstRow->County ?? ''));
        $postcode = trim((string) ($firstRow->Postcode ?? ''));

        if ($locality !== '') { $parts[] = $locality; $seen[] = $norm($locality); }
        if ($town !== '' && !in_array($norm($town), $seen, true)) { $parts[] = $town; $seen[] = $norm($town); }
        if ($district !== '' && !in_array($norm($district), $seen, true)) { $parts[] = $district; $seen[] = $norm($district); }
        if ($county !== '' && !in_array($norm($county), $seen, true)) { $parts[] = $county; $seen[] = $norm($county); }

        // Postcode always last if present
        if ($postcode !== '') { $parts[] = $postcode; }

        $displayAddress = implode(', ', $parts);
        // Determine if locality charts should be shown (locality must be non-empty and distinct from TownCity, District, County)
        $showLocalityCharts = ($locality !== '')
            && ($norm($locality) !== $norm($town))
            && ($norm($locality) !== $norm($district))
            && ($norm($locality) !== $norm($county));
        // Determine if town charts should be shown (town must be non-empty and distinct from District, County)
        $showTownCharts = ($town !== '')
            && ($norm($town) !== $norm($district))
            && ($norm($town) !== $norm($county));
        // Determine if district charts should be shown (district must be non-empty and distinct from County)
        $showDistrictCharts = ($district !== '')
            && ($norm($district) !== $norm($county));
    @endphp
    <p class="text-zinc-500 font-semibold mb-1">{{ $displayAddress }}</p>
    
    {{-- PPD Category note --}}
    @php
        $ppdSet = $results->pluck('PPDCategoryType')->filter()->unique();
        $hasA = $ppdSet->contains('A');
        $hasB = $ppdSet->contains('B');
    @endphp
    @if($ppdSet->isNotEmpty())
        @if($hasA && !$hasB)
            <div class="mb-6 text-sm text-zinc-600">
                All transactions shown for this property are <span class="font-semibold">Category A</span> sales.  This means all sales were at market value in an arms length transaction.
            </div>
        @elseif($hasB && !$hasA)
            <div class="mb-6 text-sm text-zinc-600">
                All transactions shown for this property are <span class="font-bold">Category B</span> sales.  It may have been a repossession, power of sale, sale to a company or social landlord, a part transfer, sale of a parking space or simply where the property type is not known. This transaction
                may not be representative of a true sale at market value in an arms length transaction.  Where the transaction is not reflective of general trends in the immediate vicinity it could skew the data below.
            </div>
        @elseif($hasA && $hasB)
            <div class="mb-6 text-sm text-zinc-600">
                This property has a <span class="font-semibold">mix of Category A and Category B</span> sales.  Category A means all sales were at market value in an arms length transaction.  Category B may have been a repossession, power of sale, sale to a company or social landlord, a part transfer, sale of a parking space or simply where the property type is not known. This transaction
                may not be representative of a true sale at market value in an arms length transaction.  Where the transaction is not reflective of general trends in the immediate vicinity it could skew the data below.
            </div>
        @else
            <div class="mb-6 text-sm text-zinc-600">
                Note: Transactions include categories: {{ $ppdSet->join(', ') }}.
            </div>
        @endif
    @endif
    

    <!-- Links: Google Maps & Zoopla -->
    @php
        $postcode = trim(optional($results->first())->Postcode ?? '');
        $town = trim(optional($results->first())->TownCity ?? '');
        $street = trim(optional($results->first())->Street ?? '');
        $county = trim(optional($results->first())->County ?? '');
        $district = trim(optional($results->first())->District ?? '');
        // Build slugs for path when possible (e.g. worcester/barneshall-avenue/wr5-3eu)
        $pcLower = strtolower($postcode);
        $pcSlug = str_replace(' ', '-', $pcLower);
        $townSlug = \Illuminate\Support\Str::slug($town);
        $streetSlug = \Illuminate\Support\Str::slug($street);

        $zooplaPath = ($town && $street)
            ? "/for-sale/property/{$townSlug}/{$streetSlug}/{$pcSlug}/"
            : "/for-sale/property/"; // fallback to generic search path

        $zooplaUrl = "https://www.zoopla.co.uk{$zooplaPath}?q=" . urlencode($postcode) . "&search_source=home";
    @endphp
    <div class="mb-6 flex flex-wrap items-center justify-end gap-2 text-sm">
        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($address) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-md bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a7 7 0 00-7 7c0 5.25 7 12 7 12s7-6.75 7-12a7 7 0 00-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/>
            </svg>
            <span>View in Google Maps</span>
        </a>

        @if($postcode !== '')
        <a href="{{ $zooplaUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-md bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M10.25 3.5a6.75 6.75 0 105.22 11.2l3.4 3.4a1 1 0 001.42-1.42l-3.4-3.4A6.75 6.75 0 0010.25 3.5zm0 2a4.75 4.75 0 110 9.5 4.75 4.75 0 010-9.5z"/>
            </svg>
            <span>For sale on Zoopla</span>
        </a>

        @endif
    </div>

    @if($results->isEmpty())
        <p>No transactions found for this property.</p>
    @else
        <table class="min-w-full text-sm border border-zinc-200 rounded-md">
            <thead class="bg-zinc-50">
                <tr class="text-left">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Price</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Tenure</th>
                    <th class="px-3 py-2">New Build?</th>
                    <th class="px-3 py-2">Category</th>
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
                        @elseif($row->PropertyType === 'O')
                            Other
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
                       @if($row->NewBuild === 'N')
                        No 
                       @elseif($row->NewBuild === 'Y')
                       Yes
                       @endif
                    </td>
                    <td class="px-3 py-2">
                        {{ $row->PPDCategoryType }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

<div class="my-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Price History of this property</h2>
        <canvas id="priceHistoryChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Average Price of property in {{ $postcode }}</h2>
        <canvas id="postcodePriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Number of Sales in {{ $postcode }}</h2>
        <canvas id="postcodeSalesChart"></canvas>
    </div>
    <!-- Locality Charts (moved up) -->
    @if($showLocalityCharts)
    <!-- Locality Charts (shown only when locality is present and distinct) -->
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($locality)) }}</h2>
        <canvas id="localityPropertyTypesChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($locality)) }}</h2>
        <canvas id="localityPriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($locality)) }}</h2>
        <canvas id="localitySalesChart"></canvas>
    </div>
    @endif
    @if($showTownCharts)
    <!-- Town/City Charts -->
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($town)) }}</h2>
        <canvas id="townPropertyTypesChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($town)) }}</h2>
        <canvas id="townPriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($town)) }}</h2>
        <canvas id="townSalesChart"></canvas>
    </div>
    @endif
    <!-- District Charts -->
    @if($showDistrictCharts)
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Property Types in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
        <canvas id="districtPropertyTypesChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Average Price of property in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
        <canvas id="districtPriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Number of Sales in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
        <canvas id="districtSalesChart"></canvas>
    </div>
    @endif
    @if(!empty($county))
    <!-- County Charts -->
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($county)) }}</h2>
        <canvas id="countyPropertyTypesChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($county)) }}</h2>
        <canvas id="countyPriceChart"></canvas>
    </div>
    <div class="border border-zinc-200 rounded-md p-2">
        <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($county)) }}</h2>
        <canvas id="countySalesChart"></canvas>
    </div>
    @endif
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
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
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
const postcodePriceData = @json(($postcodePriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxPostcode, {
    type: 'line',
    data: {
        labels: @json(($postcodePriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: postcodePriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
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
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
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

// District Price Chart with canvas guard
(function(){
  const el = document.getElementById('districtPriceChart');
  if (!el) return;
  const ctxDistrict = el.getContext('2d');
  const districtPriceData = @json(($districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('avg_price'));
  new Chart(ctxDistrict, {
    type: 'line',
    data: {
        labels: @json(($districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: districtPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
})();
@if(!empty($county))
// County Price Chart
const ctxCountyPrice = document.getElementById('countyPriceChart').getContext('2d');
const countyPriceData = @json(($countyPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxCountyPrice, {
    type: 'line',
    data: {
        labels: @json(($countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: countyPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: { beginAtZero: false, ticks: { callback: function(value) { return '£' + value.toLocaleString(); } } }
        }
    }
});
@endif
@if($showTownCharts)
// Town/City Price Chart
const ctxTownPrice = document.getElementById('townPriceChart').getContext('2d');
const townPriceData = @json(($townPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxTownPrice, {
    type: 'line',
    data: {
        labels: @json(($townPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: townPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
@endif
@if($showLocalityCharts)
// Locality Price Chart
const ctxLocality = document.getElementById('localityPriceChart').getContext('2d');
const localityPriceData = @json(($localityPriceHistory ?? $districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxLocality, {
    type: 'line',
    data: {
        labels: @json(($localityPriceHistory ?? $districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: localityPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
@endif
</script>
<script>
// Shared bar colors for all bar charts
const barColors = [
    'rgba(54, 162, 235, 0.7)',
    'rgba(255, 99, 132, 0.7)',
    'rgba(255, 206, 86, 0.7)',
    'rgba(75, 192, 192, 0.7)',
    'rgba(153, 102, 255, 0.7)',
    'rgba(255, 159, 64, 0.7)',
    'rgba(99, 255, 132, 0.7)',
    'rgba(160, 160, 160, 0.7)'
];
// District Property Types Chart with canvas guard
(function(){
  const el = document.getElementById('districtPropertyTypesChart');
  if (!el) return;
  const ctxDistrictTypes = el.getContext('2d');
  const districtTypeLabels = @json(($districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('label'));
  const districtTypeCounts = @json(($districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('value'));
  new Chart(ctxDistrictTypes, {
      type: 'bar',
      data: {
          labels: districtTypeLabels,
          datasets: [{
              label: 'Count',
              data: districtTypeCounts,
              backgroundColor: barColors.slice(0, districtTypeLabels.length),
              borderColor: barColors.slice(0, districtTypeLabels.length).map(c => c.replace('0.7', '1')),
              borderWidth: 1
          }]
      },
      options: {
          responsive: true,
          plugins: {
              legend: { display: false },
          },
          scales: {
              y: {
                  beginAtZero: true,
                  title: { display: true, text: 'Count' },
                  ticks: { precision: 0 }
              }
          }
      }
  });
})();
@if($showLocalityCharts)
// Locality Property Types Chart
const ctxLocalityTypes = document.getElementById('localityPropertyTypesChart').getContext('2d');
const localityTypeLabels = @json(($localityPropertyTypes ?? $districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('label'));
const localityTypeCounts = @json(($localityPropertyTypes ?? $districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('value'));
new Chart(ctxLocalityTypes, {
    type: 'bar',
    data: {
        labels: localityTypeLabels,
        datasets: [{
            label: 'Count',
            data: localityTypeCounts,
            backgroundColor: barColors.slice(0, localityTypeLabels.length),
            borderColor: barColors.slice(0, localityTypeLabels.length).map(c => c.replace('0.7', '1')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Count' },
                ticks: { precision: 0 }
            }
        }
    }
});
@endif
@if($showTownCharts)
// Town/City Property Types Chart
const ctxTownTypes = document.getElementById('townPropertyTypesChart').getContext('2d');
const townTypeLabels = @json(($townPropertyTypes ?? collect())->pluck('label'));
const townTypeCounts = @json(($townPropertyTypes ?? collect())->pluck('value'));
new Chart(ctxTownTypes, {
    type: 'bar',
    data: {
        labels: townTypeLabels,
        datasets: [{
            label: 'Count',
            data: townTypeCounts,
            backgroundColor: barColors.slice(0, townTypeLabels.length),
            borderColor: barColors.slice(0, townTypeLabels.length).map(c => c.replace('0.7', '1')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Count' },
                ticks: { precision: 0 }
            }
        }
    }
});
@endif
@if(!empty($county))
// County Property Types Chart
const ctxCountyTypes = document.getElementById('countyPropertyTypesChart').getContext('2d');
const countyTypeLabels = @json(($countyPropertyTypes ?? collect())->pluck('label'));
const countyTypeCounts = @json(($countyPropertyTypes ?? collect())->pluck('value'));
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
            legend: { display: false },
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Count' }, ticks: { precision: 0 } }
        }
    }
});
@endif
const ctxPostcodeSales = document.getElementById('postcodeSalesChart').getContext('2d');
const postcodeSalesData = @json(($postcodeSalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxPostcodeSales, {
    type: 'line',
    data: {
        labels: @json(($postcodeSalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: postcodeSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: {
                display: false
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
// District Sales Chart with canvas guard
(function(){
  const el = document.getElementById('districtSalesChart');
  if (!el) return;
  const ctxDistrictSales = el.getContext('2d');
  const districtSalesData = @json(($districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('total_sales'));
  new Chart(ctxDistrictSales, {
    type: 'line',
    data: {
        labels: @json(($districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: districtSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
  });
})();
@if(!empty($county))
// County Sales Chart
const ctxCountySales = document.getElementById('countySalesChart').getContext('2d');
const countySalesData = @json(($countySalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxCountySales, {
    type: 'line',
    data: {
        labels: @json(($countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: countySalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
@if($showTownCharts)
// Town/City Sales Chart
const ctxTownSales = document.getElementById('townSalesChart').getContext('2d');
const townSalesData = @json(($townSalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxTownSales, {
    type: 'line',
    data: {
        labels: @json(($townSalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: townSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
@if($showLocalityCharts)
// Locality Sales Chart
const ctxLocalitySales = document.getElementById('localitySalesChart').getContext('2d');
const localitySalesData = @json(($localitySalesHistory ?? $districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxLocalitySales, {
    type: 'line',
    data: {
        labels: @json(($localitySalesHistory ?? $districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: localitySalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
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