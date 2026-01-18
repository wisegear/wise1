@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  {{-- Hero / summary card --}}
  <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
    <div class="max-w-4xl">
      <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">New Build vs Existing Sales Dashboard</h1>
      <p class="mt-2 text-sm leading-6 text-gray-700">
        This dashboard compares <span class="font-semibold">new build</span> and <span class="font-semibold">existing property</span> sales across the UK.  This data is provided as part of
        the Government's HPI data which may differ from the England/Wales Land Registry information used elsewhere on this site.
      </p>
    </div>
    <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
      <img src="{{ asset('assets/images/site/new_old.svg') }}" alt="New vs Existing" class="w-64 h-auto">
    </div>
  </section>

  @php
    // If the controller doesn't supply nation_trends yet, build them here (keeps the page working).
    // NOTE: This is intentionally minimal and mirrors the controller logic we discussed.
    $nation_trends = $nation_trends ?? [];

    if (empty($nation_trends)) {
      $includeAggregates = (bool) ($include_aggregates ?? false);

      $trendBase = DB::table('hpi_monthly')
        ->when(!$includeAggregates, fn($q) => $q->whereRaw("LEFT(`AreaCode`, 1) <> 'K'"));

      $nationTrendRows = (clone $trendBase)
        ->selectRaw("CASE LEFT(`AreaCode`, 1)
          WHEN 'E' THEN 'England'
          WHEN 'S' THEN 'Scotland'
          WHEN 'W' THEN 'Wales'
          WHEN 'N' THEN 'Northern Ireland'
          ELSE NULL
        END as nation")
        ->selectRaw('YEAR(`Date`) as year')
        ->selectRaw('SUM(`NewSalesVolume`) as new_vol')
        ->selectRaw('SUM(`OldSalesVolume`) as old_vol')
        ->whereRaw("LEFT(`AreaCode`, 1) IN ('E','S','W','N')")
        ->groupBy('nation', DB::raw('YEAR(`Date`)'))
        ->orderBy(DB::raw('YEAR(`Date`)'), 'desc')
        ->limit(15 * 4)
        ->get();

      $nation_trends = collect(['England','Scotland','Wales','Northern Ireland'])->mapWithKeys(function ($nation) use ($nationTrendRows) {
        $rows = $nationTrendRows
          ->where('nation', $nation)
          ->sortBy('year')
          ->values()
          ->take(-15)
          ->values();

        return [
          $nation => $rows->map(fn($r) => [
            'date'    => (string) $r->year,
            'new_vol' => (int) $r->new_vol,
            'old_vol' => (int) $r->old_vol,
          ])->all()
        ];
      })->all();
    }
  @endphp

  {{-- UK trend --}}
  <div class="mb-6">
    <h2 class="text-xl font-semibold mb-3">Last 15 years — UK totals</h2>
    <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
      <canvas id="trendChart" class="w-full h-full"></canvas>
    </div>
  </div>

  {{-- Nation trends --}}
  <div class="mb-6">
    <h2 class="text-xl font-semibold mb-3">Last 15 years — by nation</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">England</h3>
        <canvas id="trendChartEngland" class="w-full h-full"></canvas>
      </div>

      <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Scotland</h3>
        <canvas id="trendChartScotland" class="w-full h-full"></canvas>
      </div>

      <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Wales</h3>
        <canvas id="trendChartWales" class="w-full h-full"></canvas>
      </div>

      <div class="border p-4 bg-white rounded-lg shadow h-80 overflow-hidden">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Northern Ireland</h3>
        <canvas id="trendChartNorthernIreland" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    (function(){
      const trendData = @json($trend ?? []);
      const labels = trendData.map(d => d.date);
      const newData = trendData.map(d => d.new_vol);
      const oldData = trendData.map(d => d.old_vol);
      const nationTrends = @json($nation_trends ?? []);

      function renderLineChart(canvasId, labels, newData, oldData){
        const ctx = document.getElementById(canvasId);
        if(!ctx) return;

        // If a chart instance already exists (hot reload/navigation), destroy it
        if (ctx._chartInstance) { ctx._chartInstance.destroy(); }

        if (!labels.length) {
          const wrap = ctx.closest('.border');
          if (wrap) {
            wrap.innerHTML = '<div class="p-6 text-sm text-zinc-600">No trend data available for the selected date range.</div>';
          }
          return;
        }

        const formatNumber = (value) => new Intl.NumberFormat('en-GB').format(value);
        const barFill = 'rgba(87, 161, 0, 0.70)';
        const barBorder = 'rgba(87, 161, 0, 1)';
        const lineColor = 'rgb(54, 162, 235)';
        const lineFill = 'rgba(54, 162, 235, 0.2)';

        const chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'New build',
                type: 'bar',
                data: newData,
                backgroundColor: barFill,
                borderColor: barBorder,
                borderWidth: 1,
                maxBarThickness: 28
              },
              {
                label: 'Existing',
                type: 'line',
                data: oldData,
                borderColor: lineColor,
                backgroundColor: lineFill,
                tension: 0.1,
                fill: false,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: function(ctx) {
                  const index = ctx.dataIndex;
                  const data = ctx.dataset.data;
                  if (index === 0) return lineColor;
                  return data[index] < data[index - 1] ? 'red' : lineColor;
                }
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 12, right: 12, bottom: 28, left: 12 } },
            interaction: { mode: 'index', intersect: false },
            plugins: {
              legend: {
                position: 'top'
              },
              tooltip: {
                callbacks: {
                  label: (ctx) => `${ctx.dataset.label}: ${formatNumber(ctx.parsed.y)}`
                }
              }
            },
            scales: {
              x: {
                ticks: {
                  callback: function(value, index) {
                    const lbl = this.getLabelForValue(value);
                    const clean = String(lbl).replace(/,/g, '');
                    return (index % 2 === 0) ? clean : '';
                  },
                  padding: 12,
                  maxRotation: 0,
                  minRotation: 0,
                  autoSkip: false
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0,
                  callback: (value) => formatNumber(value)
                }
              }
            }
          }
        });

        ctx._chartInstance = chart;
      }

      function renderAllTrendCharts(){
        // UK
        renderLineChart('trendChart', labels, newData, oldData);

        // Nations
        const nations = [
          { key: 'England', id: 'trendChartEngland' },
          { key: 'Scotland', id: 'trendChartScotland' },
          { key: 'Wales', id: 'trendChartWales' },
          { key: 'Northern Ireland', id: 'trendChartNorthernIreland' },
        ];

        nations.forEach(n => {
          const rows = (nationTrends && nationTrends[n.key]) ? nationTrends[n.key] : [];
          const nLabels = rows.map(d => d.date);
          const nNew = rows.map(d => d.new_vol);
          const nOld = rows.map(d => d.old_vol);
          renderLineChart(n.id, nLabels, nNew, nOld);
        });
      }

      if (window.Chart) {
        renderAllTrendCharts();
      } else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        s.onload = renderAllTrendCharts;
        document.head.appendChild(s);
      }
    })();
  </script>
  @endpush

</div>
@endsection
