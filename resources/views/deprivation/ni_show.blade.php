@extends('layouts.app')

@section('title', 'Northern Ireland Deprivation (NIMDM 2017) — ' . ($sa ?? 'Small Area'))

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
@php
  // Ensure a sensible total. NIMDM covers ~4,537 Small Areas.
  $totalDisplay = max((int)($total ?? 0), 4537);

  $rankVal   = isset($row->MDM_rank) ? (int)$row->MDM_rank : 0;
  $pctLocal  = $rankVal
    ? max(0, min(100, (int) round((1 - (($rankVal - 1) / $totalDisplay)) * 100)))
    : null;

  // NI dataset doesn't include a decile, so we'll just show "N/A"
  $dec      = null;
  $badge    = 'bg-zinc-100 text-zinc-800 ring-1 ring-inset ring-zinc-200';
@endphp

<script>
  // Load Leaflet on-demand if not present
  (function() {
    if (typeof L !== 'undefined') return;
    var lcss = document.createElement('link');
    lcss.rel = 'stylesheet';
    lcss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(lcss);
    var ljs = document.createElement('script');
    ljs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    document.head.appendChild(ljs);
  })();
</script>

{{-- Hero / summary card --}}
<section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
  <div class="max-w-3xl">
    <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">
      Northern Ireland Deprivation (NIMDM 2017)
    </h1>
    <p class="mt-2 text-sm leading-6 text-gray-700">
      Small Area <strong>{{ $sa }}</strong>
      @if(!empty($row->SOA2001name) || !empty($row->LGD2014name))
        — {{ $row->SOA2001name ?? '—' }}, {{ $row->LGD2014name ?? '—' }}
      @endif
      .
      Rankings are relative across Northern Ireland
      (1 = most deprived, {{ number_format($totalDisplay) }} = least).
    </p>
  </div>
  {{-- NIMDM doesn't provide a sample postcode, so we skip that block --}}
</section>

{{-- Map full width --}}
<section class="rounded border border-gray-200 bg-white/80 p-4 md:p-6 shadow-sm mb-8">
  <h3 class="text-base font-semibold text-gray-900 mb-3">Map</h3>

  <div id="map" class="h-80 md:h-[480px] w-full rounded border"></div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      function initMap() {
        if (typeof L === 'undefined') { return setTimeout(initMap, 50); }

        // Create an empty map first. We'll fit to polygon bounds once loaded.
        const map = L.map('map').setView([54.6, -6.7], 8); // rough NI centre as fallback

        // Default base layer (OpenStreetMap)
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Satellite layers (Esri imagery + labels)
        const satellite = L.tileLayer(
          'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
          {
            maxZoom: 19,
            attribution: 'Tiles © Esri'
          }
        );

        const labels = L.tileLayer(
          'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
          {
            maxZoom: 19,
            attribution: 'Labels © Esri'
          }
        );

        // Layer control button to switch map type
        const baseMaps = {
          'Standard Map': osm,
          'Satellite View': L.layerGroup([satellite, labels])
        };

        L.control.layers(baseMaps, null, { position: 'topright', collapsed: false }).addTo(map);

        // NI boundary slice for this Small Area (SA2011).
        // Files are written by geo:slice-ni to /public/geo/northern_ireland/sliced/{SA2011}.geojson
        const boundaryUrl = '/geo/northern_ireland/sliced/' + @json($sa) + '.geojson';
        console.log('Fetching boundary from', boundaryUrl);

        fetch(boundaryUrl)
          .then(function(r){
            if (!r.ok) throw new Error('no boundary file');
            return r.json();
          })
          .then(function(geojsonData){
            const boundaryLayer = L.geoJSON(geojsonData, {
              style: function () {
                return {
                  color: '#1e40af',        // dark blue outline for contrast
                  weight: 3,
                  opacity: 1,
                  fillColor: '#1e40af',    // same hue, light fill
                  fillOpacity: 0.08
                };
              }
            }).addTo(map);

            // Fit map to boundary nicely
            map.fitBounds(boundaryLayer.getBounds(), { padding: [20,20] });
          })
          .catch(function(err){
            console.warn('Boundary load failed:', err);
            // If we don't have a slice yet, leave fallback NI-wide view.
            map.setView([54.6, -6.7], 8);
          });
      }
      initMap();
    });
  </script>
</section>

{{-- Headline metrics --}}
<section class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-6">
  <div class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900 mb-2">Overall position</h3>
    <div class="flex flex-wrap items-center gap-3">
      <span class="inline-flex items-center rounded-full px-2 py-1 text-xs {{ $badge }}">
        Decile: N/A
      </span>
      <span class="text-xs text-gray-600">
        Rank:
        {{ $row->MDM_rank ? number_format($row->MDM_rank) : 'N/A' }}
        of {{ number_format($totalDisplay) }}
        @if(!is_null($pctLocal)) · top {{ $pctLocal }}%@endif
      </span>
    </div>
    <p class="mt-2 text-xs text-gray-600">
      NIMDM is an <em>area-based</em>, relative measure. It does not describe individuals.
    </p>
  </div>
</section>

{{-- Domain breakdown --}}
<section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
  <h3 class="text-base font-semibold text-gray-900 mb-4">Domain breakdown (ranks)</h3>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($domains as $d)
      <div class="rounded border p-4">
        @php
          $domainRankVal = isset($d['rank']) ? (int) $d['rank'] : 0;
          $domainDec = $domainRankVal
            ? max(1, min(10, (int) floor((($domainRankVal - 1) / $totalDisplay) * 10) + 1))
            : null;
          $domainBadge = match (true) {
            $domainDec !== null && $domainDec >= 8 => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-200',
            $domainDec !== null && $domainDec >= 4 => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-200',
            $domainDec !== null && $domainDec >= 1 => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-200',
            default                               => 'bg-zinc-100 text-zinc-800 ring-1 ring-inset ring-zinc-200',
          };
        @endphp

        <div class="flex items-center justify-between">
          <div class="font-medium text-gray-900">{{ $d['label'] }}</div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] leading-none {{ $domainBadge }}">
              Decile: {{ $domainDec ?? 'N/A' }}
            </span>
            <div class="text-[11px] text-gray-500">Rank</div>
          </div>
        </div>

        <div class="mt-1 text-sm text-gray-800">
          {{ $d['rank'] ? number_format($d['rank']) : 'N/A' }}
          <span class="text-xs text-gray-500">/ {{ number_format($totalDisplay) }}</span>
        </div>

        @php
          $desc = match($d['label']) {
            'Income'              => 'Low income, reliance on income-related benefits or tax credits.',
            'Employment'          => 'Involuntary exclusion from the labour market.',
            'Health'              => 'Premature mortality, illness, mental health, disability.',
            'Education'           => 'Educational attainment and skills barriers.',
            'Access to Services'  => 'Access to key services and infrastructure.',
            'Living Environment'  => 'Quality of housing and the local environment.',
            'Crime & Disorder'    => 'Recorded crime and anti-social behaviour.',
            default               => null,
          };
        @endphp

        @if($desc)
          <p class="mt-2 text-xs text-gray-600 leading-snug">{{ $desc }}</p>
        @endif
      </div>
    @endforeach
  </div>

  <p class="mt-4 text-xs text-gray-500">
    Note: NIMDM 2017 ranks are relative across Northern Ireland only.
  </p>
</section>

{{-- Back link --}}
<div class="mt-6">
  <a href="{{ route('deprivation.index') }}"
     onclick="if (document.referrer) { history.back(); return false; }"
     class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
    ← Back
  </a>
</div>

</div>
@endsection