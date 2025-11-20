@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">EPC Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                @if(($nation ?? 'ew') === 'scotland')
                    <span class="font-semibold">Scotland data (Quarterly)</span>
                @else
                    <span class="font-semibold">England &amp; Wales data (Monthly)</span>
                @endif
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                @if(($nation ?? 'ew') === 'scotland')
                    Data covers the period from 2015 to {{ \Carbon\Carbon::parse($stats['latest_lodgement'])->format('M Y') }}
                @else
                    Data covers the period from January 2008 to {{ \Carbon\Carbon::parse($stats['latest_lodgement'])->format('M Y') }}
                @endif
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                <a href="/epc/search" class="standard-button">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                  </svg>
                  Search England & Wales</a>
                <a href="/epc/search_scotland" class="standard-button">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                  </svg>
                  Search Scotland</a>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/epc_search.svg') }}" alt="EPC Dashboard" class="w-64 h-auto">
        </div>
    </section>

    <!-- Buttons to swith from England to Scotland -->
    <div class="mb-6 flex justify-center gap-4">
        <a href="{{ url('/epc?nation=ew') }}"
           class="standard-button {{ (($nation ?? 'ew') === 'ew') ? 'bg-lime-600 text-white' : '' }}"
           aria-current="{{ (($nation ?? 'ew') === 'ew') ? 'page' : 'false' }}">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
          </svg>
           England &amp; Wales
        </a>
        <a href="{{ url('/epc?nation=scotland') }}"
           class="standard-button {{ (($nation ?? 'ew') === 'scotland') ? 'bg-lime-600 text-white' : '' }}"
           aria-current="{{ (($nation ?? 'ew') === 'scotland') ? 'page' : 'false' }}">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
          </svg>
           Scotland
        </a>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Total Certificates</p>
            <p class="text-xl font-semibold">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Latest Lodgement</p>
            <p class="text-xl font-semibold">{{ \Carbon\Carbon::parse($stats['latest_lodgement'])->format('d M Y') }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Last 30 Days</p>
            <p class="text-xl font-semibold">{{ number_format($stats['last30_count']) }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Last 12 Months</p>
            <p class="text-xl font-semibold">{{ number_format($stats['last365_count']) }}</p>
        </div>
    </div>

    {{-- EPCs by Year & Tenure --}}
    <div class="mb-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Certificates issued by year --}}
        <div class="border rounded-lg bg-white p-4 shadow">
            <h2 class="text-lg font-semibold mb-2">Certificates issued by year</h2>
            <p class="mb-2 text-sm text-gray-600">
                Number of EPC certificates lodged each year for the selected nation.
            </p>
            <div class="w-full h-72">
                <canvas id="certificatesByYearChart" class="w-full h-full"></canvas>
            </div>
        </div>

        {{-- Tenure by year --}}
        <div class="border rounded-lg bg-white p-4 shadow">
            <h2 class="text-lg font-semibold mb-2">Tenure by year</h2>
            <p class="mb-2 text-sm text-gray-600">
                Split of EPCs by tenure (owner-occupied, private rented, social rented) each year.
            </p>
            <div class="w-full h-72">
                <canvas id="tenureByYearChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    @php
        // Normalised tenure labels consistent with controller and warmer
        $tenureCategoriesJs = ['Owner-occupied','Rented (private)','Rented (social)'];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        // Certificates issued by year
        const certCanvas = document.getElementById('certificatesByYearChart');
        if (certCanvas) {
          const ctx = certCanvas.getContext('2d');
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: @json($byYear->pluck('yr')),
              datasets: [{
                label: 'Certificates',
                data: @json($byYear->pluck('cnt')),
                borderWidth: 1,
                fill: false
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false }
              },
              scales: {
                x: { title: { display: false } },
                y: {
                  title: { display: true, text: 'Count' },
                  beginAtZero: true,
                  ticks: {
                    callback: function (value) {
                      return value.toLocaleString();
                    }
                  }
                }
              }
            }
          });
        }

        // Tenure by year
        const tenureCanvas = document.getElementById('tenureByYearChart');
        const tenureRaw = @json($tenureByYear ?? []);
        if (tenureCanvas && Array.isArray(tenureRaw) && tenureRaw.length) {
          const ctxTenure = tenureCanvas.getContext('2d');
          const tenureCategories = @json($tenureCategoriesJs);
          const tenureYears = [...new Set(tenureRaw.map(r => r.yr))].sort();

          const tenureDatasets = tenureCategories.map((cat) => ({
            label: cat,
            data: tenureYears.map(y => {
              const match = tenureRaw.find(r => r.yr === y && r.tenure === cat);
              return match ? match.cnt : 0;
            }),
            borderWidth: 1
          }));

          new Chart(ctxTenure, {
            type: 'line',
            data: {
              labels: tenureYears,
              datasets: tenureDatasets
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom'
                }
              },
              scales: {
                x: {
                  title: { display: true, text: 'Year' }
                },
                y: {
                  beginAtZero: true,
                  title: { display: true, text: 'Certificates' },
                  ticks: {
                    callback: function (value) {
                      return value.toLocaleString();
                    }
                  }
                }
              }
            }
          });
        }
      });
    </script>

    {{-- Energy Ratings by Year (A–G) --}}
    @php
        $years = $ratingByYear->pluck('yr')->unique()->sort()->values();
        $ratings = ['A','B','C','D','E','F','G'];
        // Build counts matrix [rating][year] => count
        $counts = [];
        foreach ($ratingByYear as $row) {
            $counts[$row->rating][$row->yr] = (int) $row->cnt;
        }
        // Totals per year
        $totals = [];
        foreach ($years as $y) {
            $totals[$y] = array_sum(array_map(fn($r) => $counts[$r][$y] ?? 0, $ratings));
        }
        // Build percentage series (rounded to 1 decimal)
        $series = [];
        foreach ($ratings as $r) {
            $data = [];
            foreach ($years as $y) {
                $value = $counts[$r][$y] ?? 0;
                $total = $totals[$y] ?: 1; // avoid division by zero
                $data[] = round(($value / $total) * 100, 1);
            }
            $series[] = ['label' => $r, 'data' => $data];
        }
    @endphp

    <div class="mb-8 border rounded-lg bg-white p-4 shadow">
        <h2 class="text-lg font-semibold">Actual Energy ratings by year (A–G, % of certificates)</h2>
        <p class="mb-2 text-sm text-zinc-700">For clarity, yes the A category is so small you can hardly see it.</p>
        <div class="w-full h-72">
            <canvas id="ratingByYearChart" class="w-full h-full"></canvas>
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const years = @json($years);
        const rawDatasets = @json($series);
        const colors = [
          '#008000', // A dark green
          '#00B050', // B green
          '#92D050', // C light green
          '#FFFF00', // D yellow
          '#FFC000', // E orange
          '#ED7D31', // F dark orange
          '#FF0000'  // G red
        ];
        const borderColors = colors.map(c => c.replace('0.7', '1'));

        const datasets = rawDatasets.map((d, i) => ({
          label: d.label,
          data: d.data,
          backgroundColor: colors[i % colors.length],
          borderColor: borderColors[i % borderColors.length],
          borderWidth: 1,
          stack: 'stack1'
        }));

        const ctx = document.getElementById('ratingByYearChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: { labels: years, datasets },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { 
                position: 'top',
                labels: {
                  padding: 10
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const pct = Number(context.parsed.y) || 0;
                    const label = context.dataset.label || '';
                    return `${label}: ${pct.toFixed(1)}%`;
                  }
                }
              }
            },
            scales: {
              x: { stacked: true, title: { display: true, text: 'Year' } },
              y: {
                stacked: true,
                beginAtZero: true,
                max: 100,
                title: { display: true, text: 'Percentage (%)' },
                ticks: {
                  callback: (val) => `${val}%`
                }
              }
            }
          }
        });
      });
    </script>

    {{-- Potential Energy Ratings by Year (A–G) --}}
    @php
        $yearsPotential = $potentialByYear->pluck('yr')->unique()->sort()->values();
        $ratingsPotential = ['A','B','C','D','E','F','G'];
        $countsPotential = [];
        foreach ($potentialByYear as $row) {
            $countsPotential[$row->rating][$row->yr] = (int) $row->cnt;
        }
        $totalsPotential = [];
        foreach ($yearsPotential as $y) {
            $totalsPotential[$y] = array_sum(array_map(fn($r) => $countsPotential[$r][$y] ?? 0, $ratingsPotential));
        }
        $seriesPotential = [];
        foreach ($ratingsPotential as $r) {
            $data = [];
            foreach ($yearsPotential as $y) {
                $value = $countsPotential[$r][$y] ?? 0;
                $total = $totalsPotential[$y] ?: 1;
                $data[] = round(($value / $total) * 100, 1);
            }
            $seriesPotential[] = ['label' => $r, 'data' => $data];
        }
    @endphp

    <div class="mb-8 border rounded-lg bg-white p-4 shadow">
        <h2 class="text-lg font-semibold">Potential energy ratings by year (A–G, % of certificates)</h2>
        <p class="mb-2 text-sm text-zinc-700">This data show the potential energy ratings if every property completed the recomendations made in the EPC.</p>
        <div class="w-full h-72">
            <canvas id="potentialByYearChart" class="w-full h-full"></canvas>
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const years = @json($yearsPotential);
        const rawDatasets = @json($seriesPotential);
        const colors = [
          '#008000', // A dark green
          '#00B050', // B green
          '#92D050', // C light green
          '#FFFF00', // D yellow
          '#FFC000', // E orange
          '#ED7D31', // F dark orange
          '#FF0000'  // G red
        ];
        const borderColors = colors;

        const datasets = rawDatasets.map((d, i) => ({
          label: d.label,
          data: d.data,
          backgroundColor: colors[i % colors.length],
          borderColor: borderColors[i % borderColors.length],
          borderWidth: 1,
          stack: 'stack1'
        }));

        const ctx = document.getElementById('potentialByYearChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: { labels: years, datasets },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  padding: 10
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const pct = Number(context.parsed.y) || 0;
                    const label = context.dataset.label || '';
                    return `${label}: ${pct.toFixed(1)}%`;
                  }
                }
              }
            },
            scales: {
              x: { stacked: true, title: { display: true, text: 'Year' } },
              y: {
                stacked: true,
                beginAtZero: true,
                max: 100,
                title: { display: true, text: 'Percentage (%)' },
                ticks: {
                  callback: (val) => `${val}%`
                }
              }
            }
          }
        });
      });
    </script>

</div>
@endsection