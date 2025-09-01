@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">HPI Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                <span class="font-semibold">House Price Index for UK and England, Wales, Scotland & Northern Ireland.</span>
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from 1968 to 2025 (June)
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/hpi.svg') }}" alt="HPI Dashboard" class="w-64 h-auto">
        </div>
    </section>

  <div class="grid md:grid-cols-5 gap-4">
    @foreach($nations as $n)
      <div class="border rounded-lg p-4 bg-white shadow-sm">
        <div class="text-xs text-neutral-500">{{ $n->RegionName }}</div>
        <div class="text-2xl font-semibold">£{{ number_format($n->AveragePrice,0) }}</div>
        <div class="text-xs">
          12m change: 
          @if($n->twelve_m_change > 0)
            <span class="text-green-600">+{{ number_format($n->twelve_m_change,2) }}%</span>
          @elseif($n->twelve_m_change < 0)
            <span class="text-red-600">{{ number_format($n->twelve_m_change,2) }}%</span>
          @else
            {{ number_format($n->twelve_m_change,2) }}%
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <div class="flex gap-4 mb-6">
    <button type="button" data-section="change" class="filter-btn px-3 py-1 rounded bg-lime-600 text-white text-sm cursor-pointer">12m Change</button>
    <button type="button" data-section="types" class="filter-btn px-3 py-1 rounded bg-neutral-200 text-sm cursor-pointer">Property Types</button>
    <button type="button" data-section="movers" class="filter-btn px-3 py-1 rounded bg-neutral-200 text-sm cursor-pointer">Movers / Losers</button>
  </div>
  <div id="section-change">
  <h2 class="text-xl font-semibold mt-8">12‑Month Change by Nation &amp; UK</h2>
  <p class="mb-4 text-sm text-zinc-700">The change in average house prices per year for UK and each nation.</p>

  {{-- UK wide chart (index 0) --}}
  @if(isset($seriesByArea[0]))
    <div class="rounded-lg border bg-white p-4 mb-6">
      <div class="mb-2 text-sm text-neutral-600">{{ $seriesByArea[0]['name'] }}</div>
      <div class="h-64">
        <canvas id="hpiChangeChart0" aria-label="{{ $seriesByArea[0]['name'] }} 12 month change" class="w-full h-full"></canvas>
      </div>
    </div>
  @endif

  {{-- Four nation charts (indexes 1..4) in 2 columns --}}
  <div class="grid gap-6 md:grid-cols-2">
    @foreach($seriesByArea as $i => $s)
      @continue($i === 0)
      <div class="rounded-lg border bg-white p-4">
        <div class="mb-2 text-sm text-neutral-600">{{ $s['name'] }}</div>
        <div class="h-56">
          <canvas id="hpiChangeChart{{ $i }}" aria-label="{{ $s['name'] }} 12 month change" class="w-full h-full"></canvas>
        </div>
      </div>
    @endforeach
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    (function(){
      try {
        const series = @json($seriesByArea);
        const POS = '#16a34a'; const NEG = '#dc2626';
        if (!Array.isArray(series) || series.length === 0) {
          console.warn('seriesByArea is empty or missing');
          return;
        }
        series.forEach((s, i) => {
          const el = document.getElementById('hpiChangeChart' + i);
          if (!el) return;
          const ctx = el.getContext('2d');
          const dataValues = s.twelve_m_change;
          const colors = dataValues.map(v => (v === null || v === undefined) ? 'rgba(0,0,0,0)' : (v >= 0 ? POS : NEG));
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: s.dates,
              datasets: [{
                label: '12m % change',
                data: dataValues,
                backgroundColor: colors,
                hoverBackgroundColor: colors,
                borderWidth: 0,
                borderSkipped: false,
                spanGaps: true
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: { 
                  title: { display: true, text: '%' },
                  beginAtZero: true
                },
                x: {
                  ticks: {
                    callback: function(value, index, ticks) {
                      const label = this.getLabelForValue(value);
                      return label && label.length >= 4 ? label.substring(0,4) : label;
                    },
                    maxTicksLimit: 20
                  }
                }
              },
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: (ctx) => {
                      const v = ctx.parsed.y;
                      if (v === null || v === undefined) return 'No data';
                      const sign = v > 0 ? '+' : '';
                      return `${sign}${v.toFixed(2)}%`;
                    }
                  }
                }
              }
            }
          });
        });
      } catch (e) {
        console.error('Chart init error', e);
      }
    })();
  </script>
  </div>

  <div id="section-types" class="hidden">
  @isset($typePriceSeries)
  <h2 class="text-xl font-semibold mt-8">Property Type – Average Price (UK &amp; Nations)</h2>
  <p class="text-sm text-zinc-700">You can use the property type buttons on each chart to view individual property type instead of all.</p>
  <p class="text-sm text-zinc-700 mb-4">Note that not all nations started recording property type at the same time, which is why they are different.  The UK chart starts at 2005 as that's the first year all nations data exists.</p>

  {{-- UK wide property-type chart (index 0) --}}
  @if(isset($typePriceSeries[0]))
    <div class="rounded-lg border bg-white p-4 mb-6">
      <div class="mb-2 text-sm text-neutral-600">{{ $typePriceSeries[0]['name'] }}</div>
      <div class="mb-2 flex flex-wrap gap-2 text-xs">
        <button type="button" data-action="showAll" class="px-2 py-1 rounded border bg-lime-600 text-white">All</button>
        <button type="button" data-action="focus" data-type="Detached" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Detached</button>
        <button type="button" data-action="focus" data-type="SemiDetached" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Semi-detached</button>
        <button type="button" data-action="focus" data-type="Terraced" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Terraced</button>
        <button type="button" data-action="focus" data-type="Flat" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Flat</button>
      </div>
      <div class="h-64">
        <canvas id="typePriceChart0" aria-label="{{ $typePriceSeries[0]['name'] }} property type prices" class="w-full h-full"></canvas>
      </div>
    </div>
  @endif

  {{-- Four nation property-type charts (indexes 1..4) in 2 columns --}}
  <div class="grid gap-6 md:grid-cols-2">
    @foreach($typePriceSeries as $i => $s)
      @continue($i === 0)
      <div class="rounded-lg border bg-white p-4">
        <div class="mb-2 text-sm text-neutral-600">{{ $s['name'] }}</div>
        <div class="mb-2 flex flex-wrap gap-2 text-xs">
          <button type="button" data-action="showAll" class="px-2 py-1 rounded border bg-lime-600 text-white">All</button>
          <button type="button" data-action="focus" data-type="Detached" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Detached</button>
          <button type="button" data-action="focus" data-type="SemiDetached" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Semi-detached</button>
          <button type="button" data-action="focus" data-type="Terraced" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Terraced</button>
          <button type="button" data-action="focus" data-type="Flat" class="px-2 py-1 rounded border bg-lime-600 text-white cursor-pointer">Flat</button>
        </div>
        <div class="h-56">
          <canvas id="typePriceChart{{ $i }}" aria-label="{{ $s['name'] }} property type prices" class="w-full h-full"></canvas>
        </div>
      </div>
    @endforeach
  </div>

  <script>
    (function(){
      try {
        const series = @json($typePriceSeries);
        if (!Array.isArray(series) || series.length === 0) return;
        const COLORS = {
          Detached: '#3b82f6',      // brighter blue
          SemiDetached: '#f97316',  // brighter orange
          Terraced: '#22c55e',      // brighter green
          Flat: '#ef4444'           // brighter red
        };

        series.forEach((s, i) => {
          const el = document.getElementById('typePriceChart' + i);
          if (!el) return;
          const ctx = el.getContext('2d');
          // Filter out dates before 1995 for England/Wales/UK, and before 2004 for Scotland/NI
          const labels = [];
          const filtered = { Detached: [], SemiDetached: [], Terraced: [], Flat: [] };
          let startYear = 1995; // default
          if (s.code === 'K02000001') startYear = 2005; // United Kingdom
          if (s.code === 'S92000003') startYear = 2004; // Scotland
          if (s.code === 'N92000002') startYear = 2005; // Northern Ireland

          s.dates.forEach((d, idx) => {
            const year = parseInt(d.substring(0,4), 10);
            if (year >= startYear) {
              labels.push(d);
              filtered.Detached.push(s.types.Detached[idx]);
              filtered.SemiDetached.push(s.types.SemiDetached[idx]);
              filtered.Terraced.push(s.types.Terraced[idx]);
              filtered.Flat.push(s.types.Flat[idx]);
            }
          });

          const chart = new Chart(ctx, {
            type: 'line',
            data: {
              labels,
              datasets: [
                { label: 'Detached',      data: filtered.Detached,     borderColor: COLORS.Detached,     backgroundColor: 'transparent', spanGaps: true, pointRadius: 0, borderWidth: 2.5, tension: 0.35, cubicInterpolationMode: 'monotone', fill: false },
                { label: 'Semi-detached', data: filtered.SemiDetached, borderColor: COLORS.SemiDetached, backgroundColor: 'transparent', spanGaps: true, pointRadius: 0, borderWidth: 2.5, tension: 0.35, cubicInterpolationMode: 'monotone', fill: false },
                { label: 'Terraced',      data: filtered.Terraced,     borderColor: COLORS.Terraced,     backgroundColor: 'transparent', spanGaps: true, pointRadius: 0, borderWidth: 2.5, tension: 0.35, cubicInterpolationMode: 'monotone', fill: false },
                { label: 'Flat',          data: filtered.Flat,         borderColor: COLORS.Flat,         backgroundColor: 'transparent', spanGaps: true, pointRadius: 0, borderWidth: 2.5, tension: 0.35, cubicInterpolationMode: 'monotone', fill: false }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: { title: { display: true, text: '£' }, ticks: { callback: (v) => '£' + new Intl.NumberFormat('en-GB', { maximumFractionDigits: 0 }).format(v) } },
                x: {
                  ticks: {
                    callback: function(value){
                      const label = this.getLabelForValue(value);
                      return label && label.length >= 4 ? label.substring(0,4) : label;
                    },
                    maxTicksLimit: 20
                  }
                }
              },
              plugins: {
                legend: { display: true, position: 'bottom' },
                tooltip: {
                  callbacks: {
                    label: (ctx) => {
                      const v = ctx.parsed.y;
                      if (v == null) return `${ctx.dataset.label}: n/a`;
                      const pounds = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 0 }).format(v);
                      return `${ctx.dataset.label}: ${pounds}`;
                    }
                  }
                }
              },
              elements: { point: { hitRadius: 6 } }
            }
          });
          el._chartInstance = chart;
          function setFocusFill(chart, focusLabel) {
            chart.data.datasets.forEach(ds => {
              const isFocus = ds.label === focusLabel;
              // Set fill only for the focused dataset; others transparent
              ds.fill = !!focusLabel && isFocus;
              if (ds.label === 'Detached')      ds.backgroundColor = isFocus ? 'rgba(59,130,246,0.07)' : 'transparent';
              if (ds.label === 'Semi-detached') ds.backgroundColor = isFocus ? 'rgba(249,115,22,0.07)' : 'transparent';
              if (ds.label === 'Terraced')      ds.backgroundColor = isFocus ? 'rgba(34,197,94,0.07)'  : 'transparent';
              if (ds.label === 'Flat')          ds.backgroundColor = isFocus ? 'rgba(239,68,68,0.07)'  : 'transparent';
            });
          }

          // Wire mini-controls to focus series
          const container = el.closest('.border');
          if (container) {
            container.querySelectorAll('button[data-action]')?.forEach(btn => {
              btn.addEventListener('click', () => {
                if (!el._chartInstance) return; // safety
                const chart = el._chartInstance;
                const action = btn.getAttribute('data-action');
                if (action === 'showAll') {
                  chart.data.datasets.forEach(ds => { ds.hidden = false; ds.fill = false; ds.backgroundColor = 'transparent'; });
                  chart.update();
                  return;
                }
                if (action === 'focus') {
                  const t = btn.getAttribute('data-type');
                  const label = (t === 'SemiDetached' ? 'Semi-detached' : t);
                  chart.data.datasets.forEach(ds => { ds.hidden = (ds.label !== label); });
                  setFocusFill(chart, label);
                  chart.update();
                }
              });
            });
          }
        });
      } catch (e) { console.error('Type chart init error', e); }
    })();
  </script>
  @endisset
  </div>

  <div id="section-movers" class="hidden">
    <h2 class="text-xl font-semibold mt-4">Top Movers &amp; Losers (latest month)</h2>
    <p class="mb-4 text-sm text-zinc-700">Top 30 regions that have gained and lost the most over the past 12 months.</p>
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <h3 class="text-lg font-semibold mb-2">Top Movers</h3>
        <table class="w-full text-sm border">
          <thead class="bg-neutral-100">
            <tr>
              <th class="p-2 text-left w-1/2">Region</th>
              <th class="p-2 w-1/4">Price</th>
              <th class="p-2 w-1/4">12m % Change</th>
            </tr>
          </thead>
          <tbody>
            @foreach($movers as $m)
              <tr>
                <td class="p-2">{{ $m->RegionName }}</td>
                <td class="p-2">£{{ number_format($m->AveragePrice,0) }}</td>
                <td class="p-2">
                  @if($m->{'12m%Change'} > 0)
                    <span class="text-green-600">+{{ number_format($m->{'12m%Change'},2) }}%</span>
                  @elseif($m->{'12m%Change'} < 0)
                    <span class="text-red-600">{{ number_format($m->{'12m%Change'},2) }}%</span>
                  @else
                    {{ number_format($m->{'12m%Change'},2) }}%
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-2">Top Losers</h3>
        <table class="w-full text-sm border">
          <thead class="bg-neutral-100">
            <tr>
              <th class="p-2 text-left w-1/2">Region</th>
              <th class="p-2 w-1/4">Price</th>
              <th class="p-2 w-1/4">12m % Change</th>
            </tr>
          </thead>
          <tbody>
            @foreach($losers as $m)
              <tr>
                <td class="p-2">{{ $m->RegionName }}</td>
                <td class="p-2">£{{ number_format($m->AveragePrice,0) }}</td>
                <td class="p-2">
                  @if($m->{'12m%Change'} > 0)
                    <span class="text-green-600">+{{ number_format($m->{'12m%Change'},2) }}%</span>
                  @elseif($m->{'12m%Change'} < 0)
                    <span class="text-red-600">{{ number_format($m->{'12m%Change'},2) }}%</span>
                  @else
                    {{ number_format($m->{'12m%Change'},2) }}%
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
  <script>
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.getAttribute('data-section');
        // hide all
        document.querySelectorAll('#section-change, #section-types, #section-movers').forEach(el => el.classList.add('hidden'));
        // show selected
        const show = document.getElementById('section-' + target);
        if (show) show.classList.remove('hidden');
        // update buttons
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.classList.remove('bg-lime-600','text-white');
          b.classList.add('bg-neutral-200');
        });
        btn.classList.remove('bg-neutral-200');
        btn.classList.add('bg-lime-600','text-white');
      });
    });
  </script>

@endsection