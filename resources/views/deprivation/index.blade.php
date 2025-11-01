@extends('layouts.app')

@section('title', 'Deprivation — IMD (England) · SIMD (Scotland) · WIMD (Wales) · NIMDM (N. Ireland)')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
  {{-- Hero / summary card --}}
  <section class="relative overflow-hidden rounded border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-4 flex flex-col md:flex-row justify-between items-center">
    <div class="max-w-3xl">
      <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Deprivation Index</h1>
      <p class="mt-2 text-sm leading-6 text-gray-700">
        Quick view of the most and least deprived areas using <strong>IMD 2025</strong> (England), <strong>SIMD 2020</strong> (Scotland), <strong>WIMD 2019</strong> (Wales) and <strong>NIMDM 2017</strong> (Northern Ireland). Use the postcode box to jump straight to a specific place (England / Scotland / Wales).
      </p>
      @php
        $imd25LastWarm  = Cache::get('imd25:last_warm');
        $simdLastWarm = Cache::get('simd:last_warm');
        $wimdLastWarm = Cache::get('wimd:last_warm');
        $nimdmLastWarm = Cache::get('nimdm:last_warm');
      @endphp
      @if($imd25LastWarm || $simdLastWarm || $wimdLastWarm || $nimdmLastWarm)
        <p class="mt-1 text-xs text-gray-500">
          Cached:
          @if($imd25LastWarm)
            England (IMD 2025) {{ \Carbon\Carbon::parse($imd25LastWarm)->format('d M Y, H:i') }}
          @endif
          @if(($imd25LastWarm && $simdLastWarm) || ($imd25LastWarm && $wimdLastWarm) || ($imd25LastWarm && $nimdmLastWarm && !$simdLastWarm && !$wimdLastWarm)) · @endif
          @if($simdLastWarm)
            Scotland {{ \Carbon\Carbon::parse($simdLastWarm)->format('d M Y, H:i') }}
          @endif
          @if(($simdLastWarm && $wimdLastWarm) || ($imd25LastWarm && $wimdLastWarm && !$simdLastWarm) || ($simdLastWarm && $nimdmLastWarm && !$wimdLastWarm)) · @endif
          @if($wimdLastWarm)
            Wales {{ \Carbon\Carbon::parse($wimdLastWarm)->format('d M Y, H:i') }}
          @endif
          @if(($wimdLastWarm && $nimdmLastWarm) || ($imd25LastWarm && $nimdmLastWarm && !$wimdLastWarm && !$simdLastWarm) || ($simdLastWarm && $nimdmLastWarm && !$wimdLastWarm)) · @endif
          @if($nimdmLastWarm)
            N. Ireland {{ \Carbon\Carbon::parse($nimdmLastWarm)->format('d M Y, H:i') }}
          @endif
        </p>
      @endif
      <p class="mt-2 text-xs text-gray-600">
        Decile colours: <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-rose-300 text-zinc-900">1–3</span> higher deprivation ·
        <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-orange-300 text-zinc-900">4–7</span> mid ·
        <span class="inline-block align-middle rounded px-1.5 py-0.5 text-[11px] bg-emerald-300 text-zinc-900">8–10</span> lower deprivation.
      </p>
    </div>
    <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
      <img src="{{ asset('assets/images/site/deprivation.svg') }}" alt="Deprivation Dashboard" class="w-64 h-auto">
    </div>
  </section>

  {{-- Postcode search --}}
  @if(session('status'))
    <div class="rounded border border-amber-200 bg-amber-50 text-amber-800 px-4 py-2 text-sm">{{ session('status') }}</div>
  @endif
  <form method="get" class="flex flex-col items-center justify-center my-6">
    <div class="w-full max-w-md border p-6 bg-white rounded">
      <p class="text-xs flex justify-center sm:text-left text-gray-600 -mt-2 mb-2 w-full">
        Postcode can be in England, Scotland or Wales (not NI).
      </p>
      <div class="flex flex-col sm:flex-row items-center gap-2 w-full">
        <input name="postcode" value="{{ request('postcode') }}" placeholder="Enter postcode (e.g. SW1A 1AA)"
               class="flex-grow rounded border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500" />
        <button class="rounded bg-zinc-700 text-white px-4 py-1.5 text-sm hover:bg-zinc-500 transition-colors duration-150 whitespace-nowrap">
          Search
        </button>
      </div>
    </div>
  </form>

  @php
    // Guard totals to avoid odd cached/DB values (e.g. 999)
    $totalIMDLocal  = (int) ($totalIMD  ?? 33755);
    if ($totalIMDLocal < 30000) { $totalIMDLocal = 33755; }

    $totalSIMDLocal = (int) ($totalSIMD ?? 6976);
    if ($totalSIMDLocal < 6000) { $totalSIMDLocal = 6976; }

    $totalWIMDLocal = (int) ($totalWIMD ?? 1909);
    if ($totalWIMDLocal < 1500) { $totalWIMDLocal = 1909; }

    $totalNILocal = (int) ($totalNI ?? 4537);
    if ($totalNILocal < 3000) { $totalNILocal = 4537; }
  @endphp

  {{-- England + Scotland Top/Bottom lists --}}
  <div class="space-y-8">

    {{-- England (IMD) --}}
    <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">England — IMD 2025</h2>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Least deprived (Top 10 by rank desc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Least deprived (Top 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($engTop10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.show', $r->lsoa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->lsoa_name }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->lsoa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- Most deprived (Bottom 10 by rank asc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Most deprived (Bottom 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($engBottom10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.show', $r->lsoa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->lsoa_name }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->lsoa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <p class="mt-3 text-xs text-zinc-500">Source: English Indices of Deprivation (2025).</p>
    </section>

    {{-- Scotland (SIMD) --}}
    <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">Scotland — SIMD 2020</h2>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Least deprived --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Least deprived (Top 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($scoTop10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.scot.show', $r->data_zone) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->Intermediate_Zone ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->data_zone }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalSIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- Most deprived --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Most deprived (Bottom 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($scoBottom10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.scot.show', $r->data_zone) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->Intermediate_Zone ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->data_zone }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalSIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <p class="mt-3 text-xs text-zinc-500">Source: Scottish Index of Multiple Deprivation (2020).</p>
    </section>

    {{-- Wales (WIMD) --}}
    <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">Wales — WIMD 2019</h2>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Least deprived (Top 10 by rank desc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Least deprived (Top 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($walTop10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.wales.show', $r->lsoa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->lsoa_name ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->lsoa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalWIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- Most deprived (Bottom 10 by rank asc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Most deprived (Bottom 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($walBottom10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.wales.show', $r->lsoa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->lsoa_name ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->lsoa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalWIMDLocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <p class="mt-3 text-xs text-zinc-500">Source: Welsh Index of Multiple Deprivation (2019).</p>
    </section>

    {{-- Northern Ireland (NIMDM) --}}
    <section class="rounded border border-gray-200 bg-white/80 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">Northern Ireland — NIMDM 2017</h2>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Least deprived (Top 10 by rank desc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Least deprived (Top 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($niTop10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.ni.show', $r->sa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->sa_name ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->sa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalNILocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- Most deprived (Bottom 10 by rank asc) --}}
        <div>
          <h3 class="text-sm font-medium text-gray-800">Most deprived (Bottom 10)</h3>
          <div class="mt-2 overflow-hidden rounded border">
            <table class="w-full text-sm">
              <colgroup>
                <col style="width: 52%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 18%">
              </colgroup>
              <thead class="bg-zinc-50 text-zinc-700">
                <tr>
                  <th class="text-left px-3 py-2">Area</th>
                  <th class="text-left px-3 py-2">Code</th>
                  <th class="text-left px-3 py-2">Decile</th>
                  <th class="text-left px-3 py-2">Rank</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach(($niBottom10 ?? []) as $r)
                  @php
                    $d = (int)($r->decile ?? 0);
                    $badge = match(true){
                      $d >= 7 => 'bg-emerald-300 text-zinc-900',
                      $d >= 4 => 'bg-orange-300 text-zinc-900',
                      $d >= 1 => 'bg-rose-300 text-zinc-900',
                      default => 'bg-zinc-300 text-zinc-900'};
                  @endphp
                  <tr class="odd:bg-zinc-50/50 hover:bg-zinc-50 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-900"><a href="{{ route('deprivation.ni.show', $r->sa_code) }}" class="text-lime-700 hover:text-lime-900 font-medium transition-colors duration-150">{{ $r->sa_name ?? '—' }}</a></div>
                    </td>
                    <td class="px-3 py-2 text-xs text-zinc-600">{{ $r->sa_code }}</td>
                    <td class="px-3 py-2">
                      <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs {{ $badge }}">{{ $r->decile ?? 'N/A' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium">{{ number_format((int)$r->rank) }}</div>
                      <div class="text-xs text-zinc-500">
                        @if(!is_null($r->rank))
                          top {{ max(0, min(100, (int) round((1 - (((int)$r->rank - 1) / $totalNILocal)) * 100, 1))) }}%
                        @else
                          —
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <p class="mt-3 text-xs text-zinc-500">Source: Northern Ireland Multiple Deprivation Measure (2017).</p>
    </section>
  </div>

  <p class="text-xs text-zinc-500">Postcode search covers England (IMD), Scotland (SIMD) and Wales (WIMD).</p>
</div>
@endsection