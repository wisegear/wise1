@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">New vs Existing Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                This dashboard compares <span class="font-semibold">new build</span> and <span class="font-semibold">existing property</span> sales across the UK.
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                This page shows <span class="font-semibold">annual totals</span>. Use the controls below to select a year and to include or exclude aggregate.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/new_old.svg') }}" alt="New vs Existing" class="w-64 h-auto">
        </div>
    </section>


  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-semibold">Showing data for <span class="text-zinc-600">{{ $snapshot_year ?? ($available_years[0] ?? date('Y')) }}</span></h1>

    <form method="get" action="{{ route('newold.index') }}" class="flex flex-wrap items-center gap-2">
      <select name="year" class="border rounded px-2 py-1">
        @foreach(($available_years ?? []) as $y)
          <option value="{{ $y }}" @selected(($y ?? null) === ($snapshot_year ?? null))>{{ $y }}</option>
        @endforeach
        @if(empty($available_years))
          <option value="{{ date('Y') }}" selected>{{ date('Y') }}</option>
        @endif
      </select>
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="include_aggregates" value="1" @checked(($include_aggregates ?? false))>
        Include aggregates
      </label>
      <button class="px-3 py-1 rounded bg-zinc-900 text-white">Apply</button>
    </form>
  </div>

  {{-- Country summary cards --}}
  @if(!empty($countries))
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($countries as $c)
      <div class="border rounded p-4 bg-white">
        <div class="text-sm text-zinc-500">{{ $c['country'] }}</div>
        <div class="mt-2 text-sm">New: <span class="font-semibold">{{ number_format($c['new_vol']) }}</span> ({{ $c['new_share_pct'] }}%)</div>
        <div class="text-sm">Existing: <span class="font-semibold">{{ number_format($c['old_vol']) }}</span> ({{ $c['old_share_pct'] }}%)</div>
      </div>
    @endforeach
  </div>
  @endif

  {{-- Top areas table --}}
  <div class="mb-10">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-xl font-semibold">Top areas by annual sales volume</h2>
      <span class="text-sm text-zinc-500">Showing top {{ count($regions ?? []) }}</span>
    </div>

    <div class="overflow-x-auto border rounded">
      <table class="min-w-full text-sm">
        <thead class="bg-zinc-50">
          <tr class="text-left">
            <th class="py-2 px-3">Area</th>
            <th class="py-2 px-3">Area Code</th>
            <th class="py-2 px-3">New</th>
            <th class="py-2 px-3">Existing</th>
            <th class="py-2 px-3">Total</th>
            <th class="py-2 px-3">% New (sorted)</th>
          </tr>
        </thead>
        <tbody>
          @php
            $regions = collect($regions ?? [])->sortByDesc('new_share_pct')->values();
          @endphp
          @forelse($regions as $r)
            <tr class="border-t">
              <td class="py-2 px-3">{{ $r['region_name'] }}</td>
              <td class="py-2 px-3 text-zinc-500">{{ $r['area_code'] }}</td>
              <td class="py-2 px-3">{{ number_format($r['new_vol']) }}</td>
              <td class="py-2 px-3">{{ number_format($r['old_vol']) }}</td>
              <td class="py-2 px-3 font-semibold">{{ number_format($r['total_vol']) }}</td>
              <td class="py-2 px-3">{{ $r['new_share_pct'] }}%</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-6 px-3 text-center text-zinc-500">No data for this year.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Trend (last 12 months) --}}
  <div class="mb-6">
    <h2 class="text-xl font-semibold mb-3">Last 15 years — UK totals</h2>
    <div class="border rounded p-3 bg-white">
      <div class="relative" style="height: 320px;">
        <canvas id="trendChart"></canvas>
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

      function renderTrendChart(){
        const ctx = document.getElementById('trendChart');
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
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'New build',
                data: newData,
                tension: 0.25,
                fill: false,
                borderWidth: 2,
                pointRadius: 2,
                borderColor: '#06b6d4', // cyan-500
                backgroundColor: '#06b6d4'
              },
              {
                label: 'Existing',
                data: oldData,
                tension: 0.25,
                fill: false,
                borderWidth: 2,
                pointRadius: 2,
                borderColor: '#4f46e5', // indigo-600
                backgroundColor: '#4f46e5'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
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

      if (window.Chart) {
        renderTrendChart();
      } else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        s.onload = renderTrendChart;
        document.head.appendChild(s);
      }
    })();
  </script>
  @endpush
</div>
@endsection