@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
<section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
    <div class="max-w-3xl">
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900 mb-2">Welsh Index of Multiple Deprivation (WIMD 2019)</h1>
        <div class="mt-1">
          <span class="inline-flex items-center rounded-full bg-zinc-100 text-zinc-700 text-xs px-2 py-1 ring-1 ring-inset ring-zinc-200">Dataset: WIMD 2019</span>
        </div>
        <p class="text-sm leading-6 text-gray-700">
            This page shows deprivation information for the Welsh Data Zone <strong>{{ $lsoa }}</strong>@if(isset($row->postcode) && $row->postcode) (postcode <strong>{{ $row->postcode }}</strong>)@endif.
            The WIMD ranks 1 (most deprived) to {{ $total }} (least deprived). Data source: <strong>WIMD 2019</strong> (Welsh Government); next update pending the next index release.
        </p>
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
@if(!is_null($row->lat) && !is_null($row->long))
<div id="map" class="w-full h-96 md:h-[32rem] rounded-lg mb-8 border border-gray-300" style="height:28rem"></div>
<script>
  (function(){
    function initMap(){
      if (typeof L === 'undefined') { setTimeout(initMap, 75); return; }
      var map = L.map('map').setView([{{ (float) $row->lat }}, {{ (float) $row->long }}], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      L.marker([{{ (float) $row->lat }}, {{ (float) $row->long }}]).addTo(map).bindPopup('Postcode: {{ $row->postcode ?? 'N/A' }}');
      setTimeout(function(){ map.invalidateSize(); }, 150);
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initMap);
    } else {
      initMap();
    }
  })();
</script>
@endif

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
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Domain Breakdown</h2>
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-gray-50 text-left">
                <th class="py-2 px-3 border-b">Domain</th>
                <th class="py-2 px-3 border-b">Rank</th>
            </tr>
        </thead>
        <tbody>
            @foreach($domains as $d)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 px-3">{{ $d['label'] }}</td>
                    @php
                      $dr = $d['rank'] ?? null;
                      $dDec = $dr ? (int) ceil($dr / max(1, ($total/10))) : null;
                      $dBadge = match(true){
                        $dDec >= 8 => 'bg-emerald-100 text-emerald-800',
                        $dDec >= 4 => 'bg-amber-100 text-amber-800',
                        $dDec >= 1 => 'bg-rose-100 text-rose-800',
                        default => 'bg-zinc-100 text-zinc-700'
                      };
                    @endphp
                    <td class="py-2 px-3">
                      @if($dr)
                        {{ number_format($dr) }} <span class="text-xs text-gray-500">/ {{ number_format($total) }}</span>
                        @if($dDec)
                          <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] {{ $dBadge }}">Decile {{ $dDec }}</span>
                        @endif
                      @else
                        —
                      @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-8">
  <a href="{{ route('deprivation.index') }}" onclick="if (document.referrer) { history.back(); return false; }" class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">&larr; Back</a>
</div>
</div>
@endsection