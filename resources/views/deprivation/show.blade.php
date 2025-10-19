@extends('layouts.app')

@section('title', 'Deprivation details: '.$g->LSOA21NM)

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">

  {{-- Header card --}}
  <section class="relative overflow-hidden rounded border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">
          {{ $g->LSOA21NM }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          LSOA21: <span class="font-mono">{{ $g->LSOA21CD }}</span> · {{ $g->RUC21NM ?? 'Rural/Urban: N/A' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ url()->previous() ?: route('deprivation.index') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 bg-white border rounded-md hover:bg-zinc-50">
          Back
        </a>
      </div>
    </div>
    <p class="mt-3 text-xs text-gray-600">
      Note: Lower decile = more deprived; higher decile = less deprived. IMD 2019 (England).
    </p>
  </section>

  @if($g->LAT && $g->LONG)
    <section class="rounded border border-gray-200 bg-white/80 p-4 md:p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900 mb-3">Location Map</h2>

      {{-- Leaflet CSS --}}
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
      <style>
        /* Ensure Leaflet tiles aren't resized by global img styles and container has proper layout */
        #lsoa-map { height: 18rem; position: relative; }
        .leaflet-container { height: 100%; width: 100%; position: relative; }
        .leaflet-container img { max-width: none !important; }
      </style>

      <div id="lsoa-map" class="w-full rounded border"></div>

      {{-- Leaflet JS --}}
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

      <script>
        (function(){
          var lat = {{ $g->LAT }};
          var lon = {{ $g->LONG }};
          var map = L.map('lsoa-map', { scrollWheelZoom: false }).setView([lat, lon], 13);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
          }).addTo(map);
          L.marker([lat, lon]).addTo(map)
            .bindPopup(@json($g->LSOA21NM))
            .openPopup();
        })();
      </script>
    </section>
  @endif

  {{-- Domain breakdown --}}
  <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Domain Breakdown</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @foreach($ordered as $d)
        @php
          $dec = (int)($d['decile'] ?? 0);
          $badge = match(true) {
            $dec >= 8 => 'bg-emerald-100 text-emerald-800',
            $dec >= 5 => 'bg-amber-100 text-amber-800',
            $dec >= 1 => 'bg-rose-100 text-rose-800',
            default => 'bg-zinc-100 text-zinc-700'
          };
        @endphp
        <div class="rounded border p-4">
          <div class="flex items-center justify-between">
            <div class="font-medium text-gray-900">{{ $d['label'] }}</div>
            @if($d['weight'] && $d['label'] !== 'Overall IMD')
              <div class="text-xs text-gray-500">{{ $d['weight'] }}% weight</div>
            @endif
          </div>
          <div class="mt-2 flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs {{ $badge }}">
              Decile: {{ $d['decile'] ?? 'N/A' }}
            </span>
            <span class="text-xs text-gray-600">Rank: {{ $d['rank'] ?? 'N/A' }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- Weights explainer --}}
  <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900 mb-2">How the overall IMD is weighted</h3>
    <p class="text-sm text-gray-700">The overall IMD score is a weighted combination of seven domains. Each domain reflects a different aspect of area‑level disadvantage:</p>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Income</div>
          <span class="text-xs text-gray-500">22.5%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">People experiencing deprivation due to low income (e.g. income‑related benefits/tax credits), including children and older people affected by low household income.</p>
      </div>

      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Employment</div>
          <span class="text-xs text-gray-500">22.5%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Involuntary exclusion from the labour market: unemployment, long‑term sickness or disability, caring responsibilities, and other barriers to work.</p>
      </div>

      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Education, Skills &amp; Training</div>
          <span class="text-xs text-gray-500">13.5%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Attainment and skills in children and adults: early‑years and school results, absence, entry to higher education, and adult qualification levels.</p>
      </div>

      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Health Deprivation &amp; Disability</div>
          <span class="text-xs text-gray-500">13.5%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Risk of premature death and impaired quality of life: illness and mortality indicators, hospital episodes, limiting long‑term conditions and mental health.</p>
      </div>

      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Crime</div>
          <span class="text-xs text-gray-500">9.3%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Risk of personal and material victimisation: recorded rates of violence, burglary, theft and criminal damage.</p>
      </div>

      <div class="rounded border p-4">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Barriers to Housing &amp; Services</div>
          <span class="text-xs text-gray-500">9.3%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Physical and financial access to suitable housing and essential services: housing affordability/overcrowding and distance to GPs, schools, shops and post offices.</p>
      </div>

      <div class="rounded border p-4 md:col-span-2">
        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">Living Environment</div>
          <span class="text-xs text-gray-500">9.3%</span>
        </div>
        <p class="mt-2 text-sm text-gray-700">Quality of the local environment: housing condition (e.g. non‑decent homes, central heating), air quality/pollution and road traffic collisions.</p>
      </div>
    </div>

    <p class="mt-4 text-xs text-gray-500">Notes: IMD 2019 covers England and is an area‑based, relative measure. Lower decile = more deprived; higher decile = less deprived.</p>
  </section>

</div>
@endsection