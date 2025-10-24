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
      <button class="px-3 py-2 rounded bg-lime-600 hover:bg-lime-700 text-white text-sm cursor-pointer">Apply</button>
    </form>
  </div>

  {{-- Nations table --}}
  <div class="mb-8">
    <h2 class="text-xl font-semibold">Nations — annual sales volumes</h2>
    <p class="mb-3 text-sm">Note that Northern Ireland only provides updates annually, so the current year will have no data.</p>
    <div class="overflow-x-auto border rounded">
      <table class="min-w-full text-sm">
        <thead class="bg-zinc-50">
          <tr class="text-left">
            <th class="py-2 px-3">Nation</th>
            <th class="py-2 px-3">New</th>
            <th class="py-2 px-3">Existing</th>
            <th class="py-2 px-3">Total</th>
            <th class="py-2 px-3">% New</th>
          </tr>
        </thead>
        <tbody>
          @forelse(collect($countries)->sortByDesc('new_share_pct') as $c)
            @php $total = (int)($c['new_vol'] + $c['old_vol']); @endphp
            <tr class="border-t">
              <td class="py-2 px-3">{{ $c['country'] }}</td>
              <td class="py-2 px-3">{{ number_format($c['new_vol']) }}</td>
              <td class="py-2 px-3">{{ number_format($c['old_vol']) }}</td>
              <td class="py-2 px-3 font-semibold">{{ number_format($total) }}</td>
              <td class="py-2 px-3">{{ $c['new_share_pct'] }}%</td>
            </tr>
          @empty
            <tr><td colspan="5" class="py-6 px-3 text-center text-zinc-500">No nation data for this year.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Areas table (paginated) --}}
  <div class="mb-10">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-xl font-semibold">All areas — annual sales volume</h2>
      @if($regions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <span class="text-sm text-zinc-500">Page {{ $regions->currentPage() }} of {{ $regions->lastPage() }}</span>
      @endif
    </div>

    @php
      $currentSort = request('sort', 'new_share_pct');
      $currentDir  = strtolower(request('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
      $qsBase = request()->except(['sort','direction','page']);
      function sort_qs($col, $currentSort, $currentDir, $qsBase) {
          $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
          return '?'.http_build_query(array_merge($qsBase, ['sort' => $col, 'direction' => $dir]));
      }
      function sort_label($label, $col, $currentSort, $currentDir) {
          if ($currentSort === $col) {
              return $label.($currentDir === 'asc' ? ' ▲' : ' ▼');
          }
          return $label.' ↕';
      }
    @endphp

    <div class="overflow-x-auto border rounded">
      <table class="min-w-full text-sm">
        <thead class="bg-zinc-50">
          <tr class="text-left">
            <th class="py-2 px-3"><a href="{{ sort_qs('region_name', $currentSort, $currentDir, $qsBase) }}" class="hover:underline">{{ sort_label('Area', 'region_name', $currentSort, $currentDir) }}</a></th>
            <th class="py-2 px-3"><a href="{{ sort_qs('new_vol', $currentSort, $currentDir, $qsBase) }}" class="hover:underline">{{ sort_label('New', 'new_vol', $currentSort, $currentDir) }}</a></th>
            <th class="py-2 px-3"><a href="{{ sort_qs('old_vol', $currentSort, $currentDir, $qsBase) }}" class="hover:underline">{{ sort_label('Existing', 'old_vol', $currentSort, $currentDir) }}</a></th>
            <th class="py-2 px-3"><a href="{{ sort_qs('total_vol', $currentSort, $currentDir, $qsBase) }}" class="hover:underline">{{ sort_label('Total', 'total_vol', $currentSort, $currentDir) }}</a></th>
            <th class="py-2 px-3"><a href="{{ sort_qs('new_share_pct', $currentSort, $currentDir, $qsBase) }}" class="hover:underline">{{ sort_label('% New', 'new_share_pct', $currentSort, $currentDir) }}</a></th>
          </tr>
        </thead>
        <tbody>
          @forelse($regions as $r)
            @php
              $regionName = is_array($r) ? ($r['region_name'] ?? '') : ($r->region_name ?? '');
              $areaCode   = is_array($r) ? ($r['area_code'] ?? '')   : ($r->area_code ?? '');
              $newVol     = (int) (is_array($r) ? ($r['new_vol'] ?? 0)     : ($r->new_vol ?? 0));
              $oldVol     = (int) (is_array($r) ? ($r['old_vol'] ?? 0)     : ($r->old_vol ?? 0));
              $totalVol   = (int) (is_array($r) ? ($r['total_vol'] ?? 0)   : ($r->total_vol ?? 0));
              $sharePct   = (float) (is_array($r) ? ($r['new_share_pct'] ?? 0) : ($r->new_share_pct ?? 0));
            @endphp
            <tr class="border-t">
              <td class="py-2 px-3">{{ $regionName }}</td>
              <td class="py-2 px-3">{{ number_format($newVol) }}</td>
              <td class="py-2 px-3">{{ number_format($oldVol) }}</td>
              <td class="py-2 px-3 font-semibold">{{ number_format($totalVol) }}</td>
              <td class="py-2 px-3">{{ number_format($sharePct, 1) }}%</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-6 px-3 text-center text-zinc-500">No area data for this year.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      @if($regions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $regions->onEachSide(1)->links() }}
      @endif
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