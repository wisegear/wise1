@extends('layouts.app')

@section('title', 'Deprivation (IMD 2019)')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
{{-- Hero / summary card --}}
<section class="relative overflow-hidden rounded border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
    <div class="max-w-3xl">
        <div>
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Deprivation Index</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">Explore the English Indices of Deprivation (2019) by LSOA. View overall decile and rank, alongside Rural/Urban classification. England-only for now (Scotland/Wales coming in Nov 2025).
              The index is not updated frequently hence dated 2019.  However, there is an update expected in 2026.
            </p>
            <p class="mt-3 text-xs text-gray-600">
              <strong>Note:</strong> “Deprivation” is used here only in its statistical sense, measuring relative access to income, education, housing, and essential services. It does not imply anything negative about individuals or communities.
            </p>
        </div>
    </div>
    <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
        <img src="{{ asset('assets/images/site/deprivation.svg') }}" alt="Deprivation Dashboard" class="w-64 h-auto">
    </div>
</section>

{{-- Info panel: Decile, Rank & LSOA explanation (collapsed by default) --}}
<details class="rounded border border-gray-200 bg-white/80 shadow-sm mb-6">
    <summary class="flex items-center justify-between cursor-pointer px-4 py-3 md:px-6 md:py-4 select-none">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-zinc-600">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.894.553l5 10A1 1 0 0115 15H5a1 1 0 01-.894-1.447l5-10A1 1 0 0110 3zm0 3.618L6.382 13h7.236L10 6.618z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm md:text-base font-medium text-zinc-900">What do Decile and Rank mean? Why are there duplicate area names?</span>
        </div>
        <svg aria-hidden="true" class="w-4 h-4 text-zinc-500 group-open:rotate-180 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </summary>
    <div class="px-4 pb-4 md:px-6 md:pb-6 text-sm text-gray-700 space-y-4">
        <div>
            <span class="font-semibold">LSOA (Lower Layer Super Output Area)</span> is a small statistical geography used by the Office for National Statistics to report local data. Each LSOA represents a neighbourhood of around 1,000–3,000 residents and is designed to remain stable over time for consistent comparison. These codes (like E01000001) identify very specific local areas within larger districts.
        </div>
        <div>
            <span class="font-semibold">Decile</span> shows where an area sits relative to all LSOAs in England: <span class="font-medium">1 = most deprived</span>, <span class="font-medium">10 = least deprived</span>.
        </div>
        <div>
            <span class="font-semibold">Rank</span> orders every LSOA from most to least deprived (1 to ~32,844). Multiple areas can share similar characteristics, but ranks are unique; deciles group ranks into ten equal‑sized bands.
        </div>
        <div>
            <span class="font-semibold">Why do some areas share the same name?</span>As LSOAs are <em>small statistical zones</em> (~1,000–3,000 people). Names often follow a pattern like <em>“District 023A”</em>. Different districts can have similarly numbered LSOAs, and large places contain many LSOAs, so names may appear repeated across the country.
        </div>
        <div class="text-xs text-gray-600">
            Note: IMD 2019 is the latest full release for England. Wales/Scotland use separate indices (WIMD/SIMD) and are not included here yet.
        </div>
    </div>
</details>
<div class="mx-auto max-w-7xl px-4 py-8">

  <!-- Filters -->
  @if(session('status'))
    <div class="mb-3 rounded border border-amber-200 bg-amber-50 text-amber-800 px-4 py-2 text-sm">{{ session('status') }}</div>
  @endif
  <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-6 items-center">
    <input name="postcode" value="{{ request('postcode') }}" placeholder="Postcode"
           class="col-span-1 rounded border px-2 py-1.5 text-sm" />
    <input name="q" value="{{ $q }}" placeholder="Search LSOA"
           class="col-span-1 rounded border px-2 py-1.5 text-sm" />
    <select name="decile" class="col-span-1 rounded border px-2 py-1.5 text-sm">
      <option value="">Decile (all)</option>
      @for($i=1;$i<=10;$i++)
        <option value="{{ $i }}" {{ (string)$decile==="$i"?'selected':'' }}>Decile {{ $i }}</option>
      @endfor
    </select>
    <select name="sort" class="col-span-2 rounded border px-2 py-1.5 text-sm">
      <option value="imd_decile" {{ $sort==='imd_decile'?'selected':'' }}>Sort: Decile</option>
      <option value="imd_rank" {{ $sort==='imd_rank'?'selected':'' }}>Sort: Rank</option>
      <option value="lsoa_name" {{ $sort==='lsoa_name'?'selected':'' }}>Sort: Name</option>
    </select>
    <select name="dir" class="col-span-1 rounded border px-2 py-1.5 text-sm">
      <option value="asc"  {{ $dir==='asc'?'selected':'' }}>Asc</option>
      <option value="desc" {{ $dir==='desc'?'selected':'' }}>Desc</option>
    </select>
    <div class="mt-3">
      <button class="rounded bg-lime-600 text-white px-6 py-2 cursor-pointer hover:bg-lime-700 transition-colors duration-150">Apply / Search</button>
    </div>
  </form>

  <!-- Legend -->
  <div class="mb-3 text-sm text-zinc-600">
    <span class="font-medium">Decile</span>: 1 = most deprived … 10 = least deprived
  </div>

  <!-- Table -->
  <div class="overflow-x-auto rounded border">
    <table class="w-full text-sm">
      <thead class="bg-zinc-50 text-zinc-700">
        <tr>
          <th class="text-left px-4 py-3">LSOA</th>
          <th class="text-left px-4 py-3">Decile</th>
          <th class="text-left px-4 py-3">Rank</th>
          <th class="text-left px-4 py-3">Rural/Urban</th>
          <th class="text-left px-4 py-3">Map</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @foreach($data as $row)
          <tr class="hover:bg-zinc-50">
            <td class="px-4 py-3">
              <div class="font-medium">{{ $row->lsoa_name ?? '—' }}</div>
              <div class="text-xs text-zinc-500">{{ $row->lsoa21cd }}</div>
            </td>
            <td class="px-4 py-3">
              @php
                $d = (int)($row->imd_decile ?? 0);
                $badge = match(true) {
                  $d >= 8 => 'bg-emerald-100 text-emerald-800',
                  $d >= 5 => 'bg-amber-100 text-amber-800',
                  $d >= 1 => 'bg-rose-100 text-rose-800',
                  default => 'bg-zinc-100 text-zinc-700'
                };
              @endphp
              <span class="inline-flex items-center rounded-full px-2 py-1 text-xs {{ $badge }}">
                {{ $row->imd_decile ?? 'N/A' }}
              </span>
            </td>
            <td class="px-4 py-3">
              @if(isset($row->imd_rank) && $row->imd_rank)
                @php
                  $rank = (int)$row->imd_rank;
                  $N    = (int)($totalRank ?? 0);
                  $pct  = ($N > 0) ? round((1 - (($rank - 1) / $N)) * 100) : null; // rank 1 => 100%, rank N => ~0%
                @endphp
                <div class="font-medium">{{ number_format($rank) }}</div>
                @if($pct !== null)
                  <div class="text-xs text-zinc-500" title="Percentile is relative to most deprived (rank 1 = most deprived)">
                    {{ number_format($N) }} total · top {{ $pct }}% most deprived
                  </div>
                @endif
              @else
                N/A
              @endif
            </td>
            <td class="px-4 py-3">{{ $row->RUC21NM ?? '—' }}</td>
            <td class="px-4 py-3">
              @if($row->LAT && $row->LONG)
                <div class="flex justify-center">
                  <a href="https://www.google.com/maps?q={{ $row->LAT }},{{ $row->LONG }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium text-white bg-lime-600 rounded-md hover:bg-lime-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                      <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 014.473 8.709l3.159 3.159a.75.75 0 11-1.06 1.06l-3.159-3.159A5.5 5.5 0 119 3.5zm0 1.5a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd" />
                    </svg>
                    Map View
                  </a>
                  <a href="{{ route('deprivation.show', $row->lsoa21cd) }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium text-lime-700 bg-lime-50 rounded-md hover:bg-lime-100 border border-lime-200 ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                      <path fill-rule="evenodd" d="M10.75 3a.75.75 0 00-1.5 0v7.25H3a.75.75 0 000 1.5h6.25V17a.75.75 0 001.5 0v-5.25H17a.75.75 0 000-1.5h-6.25V3z" clip-rule="evenodd" />
                    </svg>
                    Details
                  </a>
                </div>
              @else
                <span class="text-zinc-400">—</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="mt-4">
    {{ $data->links() }}
  </div>

  <p class="text-xs text-zinc-500 mt-6">
    Source: English Indices of Deprivation (2019). LSOA names & Rural/Urban are 2021 geography.
  </p>
</div>
</div>
@endsection