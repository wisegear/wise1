@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">EPC Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                <span class="font-semibold">Only England &amp; Wales are currently available</span>
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from January 2008 to July 2025
            </p>
            <div class="mt-2 flex flex-wrap gap-2"> <!-- Avoids unset in css -->
                <a href="/epc/search" class="standard-button">Search For EPCs</a>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/epc_search.svg') }}" alt="EPC Dashboard" class="w-64 h-auto">
        </div>
    </section>

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

    {{-- EPCs by Year --}}
    <div class="mb-8 border rounded-lg bg-white p-4 shadow">
        <h2 class="text-lg font-semibold mb-2">Certificates issued by year</h2>
        <div class="w-full h-72">
          <canvas id="certificatesByYearChart" class="w-full h-full"></canvas>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('certificatesByYearChart').getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: @json($byYear->pluck('yr')),
            datasets: [{
              label: 'Certificates',
              data: @json($byYear->pluck('cnt')),
              borderColor: 'rgba(75, 192, 192, 1)',
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              fill: true,
              tension: 0.3
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              x: { title: { display: true, text: 'Year' } },
              y: { title: { display: true, text: 'Count' }, beginAtZero: true }
            }
          }
        });
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