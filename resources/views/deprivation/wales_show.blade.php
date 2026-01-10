@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
<section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900 mb-2">
                Welsh Index of Multiple Deprivation
            </h1>

            <div class="mt-1">
                <span class="inline-flex items-center rounded-full bg-zinc-200 text-zinc-700 text-xs px-2 py-1 ring-1 ring-inset ring-zinc-200 mb-2">
                    Dataset: WIMD 2019
                </span>
            </div>

            <p class="text-sm leading-6 text-gray-700">
                This page shows deprivation information for the Welsh Data Zone
                <strong>{{ $lsoa }}</strong>
                @if(isset($row->postcode) && $row->postcode)
                    (postcode <strong>{{ $row->postcode }}</strong>)
                @endif.
                The WIMD ranks 1 (most deprived) to {{ $total }} (least deprived).
                Deprivation indexes are infrequent, it may be several years before an update is available.
            </p>

            <p class="text-xs text-zinc-600 mt-4">
                Decile colours:
                <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-rose-300 text-zinc-900">1–3</span>
                higher deprivation ·
                <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-orange-300 text-zinc-900">4–7</span>
                mid ·
                <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-emerald-300 text-zinc-900">8–10</span>
                lower deprivation.
            </p>
        </div>

        <div class="mt-4 md:mt-0 md:ml-8 flex-shrink-0">
            <img
                src="{{ asset('assets/images/site/deprivation2.svg') }}"
                alt="Deprivation illustration"
                class="h-24 md:h-40 w-auto opacity-90"
            >
        </div>
    </div>
</section>

<script>
  (function(){
    if (typeof L !== 'undefined') return; // already loaded globally
    var lcss = document.createElement('link');
    lcss.rel = 'stylesheet';
    lcss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(lcss);
    var ljs = document.createElement('script');
    ljs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    document.head.appendChild(ljs);
  })();
</script>

{{-- Map --}}
<div id="map" class="w-full h-96 md:h-[32rem] rounded-lg mb-8 border border-gray-300" style="height:28rem"></div>
<script>
  (function() {
    function initMap() {
      if (typeof L === 'undefined') { setTimeout(initMap, 75); return; }

      // Create map (we'll fit to polygon bounds later)
      var map = L.map('map');

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

      // Fetch this Welsh LSOA / data zone shape.
      // Wales uses its own sliced folder because some WIMD zones are not
      // present in the England+Wales LSOA export (e.g. W01000396 etc.).
      const boundaryUrl = '/geo/wales/sliced/' + @json($lsoa) + '.geojson?v=' + Date.now();
      console.log('Fetching boundary from', boundaryUrl);
      fetch(boundaryUrl)
        .then(function(resp){ return resp.json(); })
        .then(function(geo){
          var layer = L.geoJSON(geo, {
            style: function () {
              return {
                  color: '#1e3a8a',      // deep blue border
                  weight: 2.5,
                  opacity: 1,
                  fillColor: '#60a5fa',  // light blue fill
                  fillOpacity: 0.4
              };
            }
          }).addTo(map);

          // Zoom map to polygon bounds
          map.fitBounds(layer.getBounds(), { maxZoom: 14 });
          setTimeout(function(){ map.invalidateSize(); }, 150);
        })
        .catch(function(err){
          console.warn('Boundary load failed:', err);
          @if(!is_null($row->lat) && !is_null($row->long))
          // Fallback: just center on lat/long if we have it
          map.setView([{{ (float) $row->lat }}, {{ (float) $row->long }}], 13);
          L.marker([{{ (float) $row->lat }}, {{ (float) $row->long }}])
            .addTo(map)
            .bindPopup('Postcode: {{ $row->postcode ?? 'N/A' }}');
          @else
          // Worst case: center UK-ish
          map.setView([52.5, -3.5], 7);
          @endif
        });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initMap);
    } else {
      initMap();
    }
  })();
</script>

{{-- Overall Position Panel --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
    <div class="border border-gray-200 bg-white/90 rounded-lg p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Overall Position</h2>
        <p class="text-sm text-gray-700 mb-4">Rank {{ number_format($row->rank ?? 0) }} out of {{ number_format($total) }} data zones in Wales.</p>
        @php
          $rankVal = (int) ($row->rank ?? 0);
          $overallDecile = $rankVal ? (int) ceil($rankVal / max(1, ($total/10))) : null;
          $decBadge = match(true){
            $overallDecile >= 8 => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-200',
            $overallDecile >= 4 => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-200',
            $overallDecile >= 1 => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-200',
            default => 'bg-zinc-100 text-zinc-800'
          };
        @endphp
        @if(!is_null($overallDecile))
          <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $decBadge }} mr-2">Decile: {{ $overallDecile }}</span>
        @endif
        @php
          $rankVal = (int) ($row->rank ?? 0);
          $pctMost  = ($rankVal && $total) ? (int) round(($rankVal / $total) * 100) : null; // 1..100 (1 = most deprived)
          $pctLeast = !is_null($pctMost) ? max(0, min(100, 100 - $pctMost)) : null;
        @endphp
        @if(!is_null($pctMost))
          @if($rankVal === 1)
            {{-- absolute most deprived --}}
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-rose-100 text-rose-800">top {{ $pctMost }}% most deprived</span>
          @elseif($rankVal === $total)
            {{-- absolute least deprived --}}
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">top {{ $pctLeast }}% least deprived</span>
          @elseif($pctMost <= 50)
            @php
              // MOST deprived side; lower % is worse (red)
              $color = $pctMost <= 33 ? 'bg-rose-100 text-rose-800'
                     : ($pctMost <= 66 ? 'bg-amber-100 text-amber-800'
                     : 'bg-emerald-100 text-emerald-800');
            @endphp
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $color }}">top {{ $pctMost }}% most deprived</span>
          @else
            @php
              // LEAST deprived side; higher % is better (green). For tiny % show amber/red accordingly.
              $color = $pctLeast >= 67 ? 'bg-emerald-100 text-emerald-800'
                     : ($pctLeast >= 34 ? 'bg-amber-100 text-amber-800'
                     : 'bg-rose-100 text-rose-800');
            @endphp
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $color }}">top {{ $pctLeast }}% least deprived</span>
          @endif
        @endif
    </div>

    <div class="border border-gray-200 bg-white/90 rounded-lg p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Zone Information</h2>
        <p class="text-sm text-gray-700 mb-1"><strong>Local authority:</strong> {{ $row->local_authority_name ?? 'N/A' }}</p>
        <p class="text-sm text-gray-700 mb-1"><strong>LSOA name:</strong> {{ $row->lsoa_name ?? 'N/A' }}</p>
        <p class="text-sm text-gray-700 mb-1"><strong>LSOA code:</strong> {{ $lsoa }}</p>
        @if(isset($row->postcode) && $row->postcode)
            <p class="text-sm text-gray-700 mb-1"><strong>Postcode:</strong> {{ $row->postcode }}</p>
        @endif
    </div>
</div>

{{-- Domain Breakdown --}}
<div class="border border-gray-200 bg-white/90 rounded-lg p-6 shadow-sm mb-12">
<h2 class="text-lg font-semibold text-gray-900 mb-6">Domain Breakdown for                 {{ $lsoa }}
                @if(isset($row->postcode) && $row->postcode)
                    (postcode {{ $row->postcode }})
                @endif</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($domains as $d)
        @php
            $rank = $d['rank'] ?? null;
            $decile = $rank ? (int) ceil($rank / max(1, ($total / 10))) : null;

            $badge = match (true) {
                $decile >= 8 => 'bg-emerald-100 text-emerald-800',
                $decile >= 4 => 'bg-amber-100 text-amber-800',
                $decile >= 1 => 'bg-rose-100 text-rose-800',
                default      => 'bg-zinc-100 text-zinc-700',
            };
        @endphp

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-semibold text-gray-900">
                    {{ $d['label'] }}
                </h3>

                @if(!empty($d['weight']))
                    <span class="text-xs text-zinc-500">
                        {{ $d['weight'] }}% weight
                    </span>
                @endif
            </div>

            @if($decile)
                <span class="inline-flex items-center rounded px-2 py-1 text-xs font-medium {{ $badge }} mb-2">
                    Decile: {{ $decile }}
                </span>
            @endif

            <p class="text-sm text-gray-700">
                @if($rank)
                    Rank: {{ number_format($rank) }} out of {{ number_format($total) }}
                @else
                    Rank: —
                @endif
            </p>

            <p class="mt-3 text-xs text-gray-500 leading-snug">
                @switch($d['key'] ?? $d['label'])
                    @case('Income')
                        Measures deprivation due to low income, including people receiving income-related benefits and tax credits.
                        @break

                    @case('Employment')
                        Captures involuntary exclusion from the labour market, such as unemployment, long‑term sickness, disability, or caring responsibilities.
                        @break

                    @case('Education')
                        Reflects educational attainment and skills in children and adults, including school results, qualifications, and access to higher education.
                        @break

                    @case('Health')
                        Indicates risk of premature death and reduced quality of life due to illness, disability, or mental health conditions.
                        @break

                    @case('Community Safety')
                        Measures risk of personal and material victimisation, including violence, burglary, theft, and criminal damage.
                        @break

                    @case('Housing')
                        Assesses access to suitable housing and essential services, including affordability, overcrowding, and distance to key services.
                        @break

                    @case('Access to Services')
                        Physical and financial access to suitable essential services: distance to GPs, schools, shops and post offices.
                        @break

                    @case('Physical Environment')
                        Covers quality of the local environment, including housing condition, air quality, pollution, and road traffic incidents.
                        @break

                    @default
                        Part of the overall deprivation score.
                @endswitch
            </p>
        </div>
    @endforeach
</div>
</div>

<div class="mt-8">
  <a href="{{ route('deprivation.index') }}" onclick="if (document.referrer) { history.back(); return false; }" class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">&larr; Back</a>
</div>
</div>
@endsection