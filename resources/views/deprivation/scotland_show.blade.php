@extends('layouts.app')

@section('title', 'Scottish Deprivation (SIMD 2020) — ' . ($dz ?? 'Data Zone'))

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
@php
  // Ensure a sensible total (SIMD 2020 has 6,976 data zones)
  $totalDisplay = max((int)($total ?? 0), 6976);
  $rankVal = isset($row->rank) ? (int)$row->rank : 0;
  $pctLocal = $rankVal ? max(0, min(100, (int) round((1 - (($rankVal - 1) / $totalDisplay)) * 100))) : null;
@endphp
@php
  $dec = (int) ($row->decile ?? 0);
  $badge = match (true) {
    $dec >= 8 => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-200',
    $dec >= 4 => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-200',
    $dec >= 1 => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-200',
    default   => 'bg-zinc-100 text-zinc-800',
  };
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
    <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Scottish Deprivation (SIMD 2020)</h1>
    <p class="mt-2 text-sm leading-6 text-gray-700">
      Data Zone <strong>{{ $dz }}</strong>
      @if(!empty($row->Intermediate_Zone) || !empty($row->Council_area))
        — {{ $row->Intermediate_Zone ?? '—' }}, {{ $row->Council_area ?? '—' }}
      @endif
      . Rankings are relative across Scotland (1 = most deprived, {{ number_format($totalDisplay) }} = least).
    </p>
  </div>
  @if(!empty($row->postcode))
  <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0 text-sm text-gray-600">
    Example postcode: <span class="font-medium text-gray-900">{{ $row->postcode }}</span>
  </div>
  @endif
</section>

{{-- Map full width --}}
<section class="rounded border border-gray-200 bg-white/80 p-4 md:p-6 shadow-sm mb-8">
  <h3 class="text-base font-semibold text-gray-900 mb-3">Map</h3>
  @if(!is_null($row->lat) && !is_null($row->long))
    <div id="map" class="h-80 md:h-[480px] w-full rounded border"></div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        function initMap() {
          if (typeof L === 'undefined') { return setTimeout(initMap, 50); }

          // Create map
          const map = L.map('map').setView([{{ $row->lat }}, {{ $row->long }}], 14);

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

          // Marker for reference
          const marker = L.marker([{{ $row->lat }}, {{ $row->long }}]).addTo(map)
            .bindPopup(@json($dz));

          // Attempt to load the SIMD Data Zone boundary polygon.
          // We assume a sliced GeoJSON per Data Zone at: /geo/scotland/sliced/{DataZone}.geojson
          const boundaryUrl = '/geo/scotland/sliced/' + @json($dz) + '.geojson';

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

              // zoom to boundary nicely
              map.fitBounds(boundaryLayer.getBounds(), { padding: [20,20] });

              // keep marker visible on top
              marker.bringToFront();
            })
            .catch(function(err){
              // if we don't have a polygon slice yet, just leave the point/zoom
              // and slightly bump in so user still sees context
              map.setView([{{ $row->lat }}, {{ $row->long }}], 14);
            });
        }
        initMap();
      });
    </script>
  @else
    <p class="text-sm text-gray-700">No coordinates available for this Data Zone.</p>
  @endif
</section>

{{-- Headline metrics --}}
<section class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-6">
  <div class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900 mb-2">Overall position</h3>
    <div class="flex flex-wrap items-center gap-3">
      <span class="inline-flex items-center rounded-full px-2 py-1 text-xs {{ $badge }}">
        Decile: {{ $row->decile ?? 'N/A' }}
      </span>
      <span class="text-xs text-gray-600">
        Rank: {{ $row->rank ? number_format($row->rank) : 'N/A' }} of {{ number_format($totalDisplay) }}
        @if(!is_null($pctLocal)) · top {{ $pctLocal }}%@endif
      </span>
    </div>
    <p class="mt-2 text-xs text-gray-600">SIMD is an <em>area‑based</em>, relative measure; it does not describe individuals.</p>
  </div>
</section>

{{-- Domain breakdown --}}
<section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
  <h3 class="text-base font-semibold text-gray-900 mb-4">Domain breakdown (ranks)</h3>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($domains as $d)
      <div class="rounded border p-4">
        <div class="flex items-center justify-between gap-2">
          <div class="font-medium text-gray-900">{{ $d['label'] }}</div>

          <div class="flex items-center gap-2">
            @php
              $domainDecile = match ($d['label']) {
                'Income'     => $row->income_decile ?? null,
                'Employment' => $row->employment_decile ?? null,
                'Health'     => $row->health_decile ?? null,
                'Education'  => $row->education_decile ?? null,
                'Access'     => $row->access_decile ?? null,
                'Crime'      => $row->crime_decile ?? null,
                'Housing'    => $row->housing_decile ?? null,
                default      => null,
              };
            @endphp

            @if(!is_null($domainDecile))
              <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                Decile {{ $domainDecile }}
              </span>
            @endif

            <span class="text-xs text-gray-500">Rank</span>
          </div>
        </div>
        <div class="mt-1 text-sm text-gray-800">
          {{ $d['rank'] ? number_format($d['rank']) : 'N/A' }} <span class="text-xs text-gray-500">/ {{ number_format($totalDisplay) }}</span>
        </div>
        @php
          $desc = match($d['label']) {
            'Income'     => 'Low income and reliance on income-related benefits/tax credits.',
            'Employment' => 'Involuntary exclusion from work (unemployment, sickness, caring).',
            'Health'     => 'Morbidity, mortality and mental health indicators.',
            'Education'  => 'Attainment and participation for children and adults (qualifications).',
            'Access'     => 'Physical access to key services (GPs, schools, shops, post offices).',
            'Crime'      => 'Recorded victimisation: violence, burglary, theft, damage.',
            'Housing'    => 'Housing condition/tenure pressures and related indicators.',
            default      => null,
          };
        @endphp
        @if($desc)
          <p class="mt-2 text-xs text-gray-600 leading-snug">{{ $desc }}</p>
        @endif
      </div>
    @endforeach
  </div>

  <p class="mt-4 text-xs text-gray-500">Note: SIMD methodology differs from IMD; rankings are relative across Scotland only.</p>
</section>

{{-- Back link --}}
<div class="mt-6">
  <a href="{{ route('deprivation.index') }}" onclick="if (document.referrer) { history.back(); return false; }" class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">← Back</a>
</div>
</div>
@endsection