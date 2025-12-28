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
    <div class="border rounded p-3 bg-white">
      <div class="relative" style="height: 320px;">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
  </div>

  {{-- Nation trends --}}
  <div class="mb-6">
    <h2 class="text-xl font-semibold mb-3">Last 15 years — by nation</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="border rounded p-3 bg-white">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">England</h3>
        <div class="relative" style="height: 280px;">
          <canvas id="trendChartEngland"></canvas>
        </div>
      </div>

      <div class="border rounded p-3 bg-white">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Scotland</h3>
        <div class="relative" style="height: 280px;">
          <canvas id="trendChartScotland"></canvas>
        </div>
      </div>

      <div class="border rounded p-3 bg-white">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Wales</h3>
        <div class="relative" style="height: 280px;">
          <canvas id="trendChartWales"></canvas>
        </div>
      </div>

      <div class="border rounded p-3 bg-white">
        <h3 class="text-sm font-semibold mb-2 text-zinc-700">Northern Ireland</h3>
        <div class="relative" style="height: 280px;">
          <canvas id="trendChartNorthernIreland"></canvas>
        </div>
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

        const chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'New build',
                type: 'bar',
                data: newData,
                borderWidth: 0,
                backgroundColor: (ctx) => {
                  const i = ctx.dataIndex;
                  const val = ctx.parsed.y;
                  if (i <= 0 || val == null) return '#16a34a'; // green-600
                  const prev = ctx.chart.data.datasets[0].data[i - 1];
                  const prevVal = (prev != null && typeof prev === 'object' && 'y' in prev) ? prev.y : prev;
                  return (prevVal != null && val < prevVal) ? '#dc2626' : '#16a34a'; // red-600 if down
                }
              },
              {
                label: 'Existing',
                type: 'line',
                data: oldData,
                tension: 0.25,
                fill: false,
                borderWidth: 2,
                pointRadius: 2,
                borderColor: '#000000',
                backgroundColor: '#000000',
                segment: {
                  borderColor: ctx =>
                    ctx.p0.parsed.y > ctx.p1.parsed.y ? '#dc2626' : '#000000' // red if down, black otherwise
                }
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            categoryPercentage: 0.75,
            barPercentage: 0.9,
            interaction: { mode: 'index', intersect: false },
            plugins: {
              legend: { position: 'top' },
              tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${new Intl.NumberFormat().format(ctx.parsed.y)}` } }
            },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } }
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