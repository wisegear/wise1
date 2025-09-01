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

  <h2 class="text-xl font-semibold mt-4">Nations Snapshot</h2>
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

  <h2 class="text-xl font-semibold mt-8">12‑Month Change by Nation &amp; UK</h2>

  {{-- UK wide chart (index 0) --}}
  @if(isset($seriesByArea[0]))
    <div class="rounded-2xl border bg-white p-4 mb-6">
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
      <div class="rounded-2xl border bg-white p-4">
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

  <h2 class="text-xl font-semibold mt-4">Top Movers (latest month)</h2>
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

  <h2 class="text-xl font-semibold mt-8">Top Losers (latest month)</h2>
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

@endsection