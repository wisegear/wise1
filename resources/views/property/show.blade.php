@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Property History</h1>
    @php
        $firstRow = $results->first();
        $parts = [];
        $norm = function($s) { return strtolower(trim((string) $s)); };
        $seen = [];

        // Core address lines
        if (!empty(trim($firstRow->PAON ?? ''))) { $parts[] = trim($firstRow->PAON); }
        if (!empty(trim($firstRow->SAON ?? ''))) { $parts[] = trim($firstRow->SAON); }
        if (!empty(trim($firstRow->Street ?? ''))) { $parts[] = trim($firstRow->Street); }

        // Location hierarchy with de-duplication
        $locality = trim((string) ($firstRow->Locality ?? ''));
        $town     = trim((string) ($firstRow->TownCity ?? ''));
        $district = trim((string) ($firstRow->District ?? ''));
        $county   = trim((string) ($firstRow->County ?? ''));
        $postcode = trim((string) ($firstRow->Postcode ?? ''));

        if ($locality !== '') { $parts[] = $locality; $seen[] = $norm($locality); }
        if ($town !== '' && !in_array($norm($town), $seen, true)) { $parts[] = $town; $seen[] = $norm($town); }
        if ($district !== '' && !in_array($norm($district), $seen, true)) { $parts[] = $district; $seen[] = $norm($district); }
        if ($county !== '' && !in_array($norm($county), $seen, true)) { $parts[] = $county; $seen[] = $norm($county); }

        // Postcode always last if present
        if ($postcode !== '') { $parts[] = $postcode; }

        $displayAddress = implode(', ', $parts);
        // Determine if locality charts should be shown (locality must be non-empty and distinct from TownCity, District, County)
        $showLocalityCharts = ($locality !== '')
            && ($norm($locality) !== $norm($town))
            && ($norm($locality) !== $norm($district))
            && ($norm($locality) !== $norm($county));
        // Determine if town charts should be shown (town must be non-empty and distinct from District, County)
        $showTownCharts = ($town !== '')
            && ($norm($town) !== $norm($district))
            && ($norm($town) !== $norm($county));
        // Determine if district charts should be shown (district must be non-empty and distinct from County)
        $showDistrictCharts = ($district !== '')
            && ($norm($district) !== $norm($county));
    @endphp
    <p class="text-zinc-500 font-semibold mb-1">{{ $displayAddress }}</p>
    
    {{-- PPD Category note --}}
    @php
        $ppdSet = $results->pluck('PPDCategoryType')->filter()->unique();
        $hasA = $ppdSet->contains('A');
        $hasB = $ppdSet->contains('B');
    @endphp
    @if($ppdSet->isNotEmpty())
        @if($hasA && !$hasB)
            <div class="mb-6 text-sm text-zinc-600">
                All transactions shown for this property are <span class="font-semibold text-rose-500">Category A</span> sales.  This means all sales were at market value in an arms length transaction.
            </div>
        @elseif($hasB && !$hasA)
            <div class="mb-6 text-sm text-zinc-600">
                All transactions shown for this property are <span class="font-bold text-rose-500">Category B</span> sales.  It may have been a repossession, power of sale, sale to a company or social landlord, a part transfer, sale of a parking space or simply where the property type is not known. This transaction
                may not be representative of a true sale at market value in an arms length transaction.  Where the transaction is not reflective of general trends in the immediate vicinity it could skew the data below.
            </div>
        @elseif($hasA && $hasB)
            <div class="mb-6 text-sm text-zinc-600">
                This property has a <span class="font-semibold text-rose-500">mix of Category A and Category B</span> sales.  Category A means all sales were at market value in an arms length transaction.  Category B may have been a repossession, power of sale, sale to a company or social landlord, a part transfer, sale of a parking space or simply where the property type is not known. This transaction
                may not be representative of a true sale at market value in an arms length transaction.  Where the transaction is not reflective of general trends in the immediate vicinity it could skew the data below.
            </div>
        @else
            <div class="mb-6 text-sm text-zinc-600">
                Note: Transactions include categories: {{ $ppdSet->join(', ') }}.
            </div>
        @endif
    @endif
    

    <!-- Links: Google Maps & Zoopla -->
    @php
        $postcode = trim(optional($results->first())->Postcode ?? '');
        $town = trim(optional($results->first())->TownCity ?? '');
        $street = trim(optional($results->first())->Street ?? '');
        $county = trim(optional($results->first())->County ?? '');
        $district = trim(optional($results->first())->District ?? '');
        // Build slugs for path when possible (e.g. worcester/barneshall-avenue/wr5-3eu)
        $pcLower = strtolower($postcode);
        $pcSlug = str_replace(' ', '-', $pcLower);
        $townSlug = \Illuminate\Support\Str::slug($town);
        $streetSlug = \Illuminate\Support\Str::slug($street);

        $zooplaPath = ($town && $street)
            ? "/for-sale/property/{$townSlug}/{$streetSlug}/{$pcSlug}/"
            : "/for-sale/property/"; // fallback to generic search path

        $zooplaUrl = "https://www.zoopla.co.uk{$zooplaPath}?q=" . urlencode($postcode) . "&search_source=home";
    @endphp
    <div class="mb-6 flex flex-wrap items-center justify-end gap-2 text-sm">
        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($displayAddress) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-md bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2a7 7 0 00-7 7c0 5.25 7 12 7 12s7-6.75 7-12a7 7 0 00-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/>
            </svg>
            <span>View in Google Maps</span>
        </a>
        <a href="https://www.google.com/search?q={{ urlencode($displayAddress) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-md bg-zinc-700 hover:bg-zinc-500 text-white px-4 py-2 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M10.25 3.5a6.75 6.75 0 105.22 11.2l3.4 3.4a1 1 0 001.42-1.42l-3.4-3.4A6.75 6.75 0 0010.25 3.5zm0 2a4.75 4.75 0 110 9.5 4.75 4.75 0 010-9.5z"/>
            </svg>
            <span>Google search address</span>
        </a>

        @if($postcode !== '')
        <a href="{{ $zooplaUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-md bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M10.25 3.5a6.75 6.75 0 105.22 11.2l3.4 3.4a1 1 0 001.42-1.42l-3.4-3.4A6.75 6.75 0 0010.25 3.5zm0 2a4.75 4.75 0 110 9.5 4.75 4.75 0 010-9.5z"/>
            </svg>
            <span>For sale on Zoopla</span>
        </a>

        @endif
    </div>


    @if($results->isEmpty())
        <p>No transactions found for this property.</p>
    @else
        <table class="min-w-full text-sm border border-zinc-200 rounded-md">
            <thead class="bg-zinc-100">
                <tr class="text-left">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Price</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Tenure</th>
                    <th class="px-3 py-2">New Build?</th>
                    <th class="px-3 py-2">Category</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $row)
                <tr class="border-t bg-white">
                    <td class="px-3 py-2">{{ \Carbon\Carbon::parse($row->Date)->format('d-m-Y') }}</td>
                    <td class="px-3 py-2">£{{ number_format($row->Price) }}</td>
                    <td class="px-3 py-2">
                        @if($row->PropertyType === 'D')
                            Detached
                        @elseif($row->PropertyType === 'T')
                            Terraced
                        @elseif($row->PropertyType === 'S')
                            Semi-Detached
                        @elseif($row->PropertyType === 'F')
                            Flat
                        @elseif($row->PropertyType === 'O')
                            Other
                        @else
                            {{ $row->PropertyType }}
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($row->Duration === 'F')
                            Freehold
                        @elseif($row->Duration === 'L')
                            Leasehold
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-3 py-2">
                       @if($row->NewBuild === 'N')
                        No 
                       @elseif($row->NewBuild === 'Y')
                       Yes
                       @endif
                    </td>
                    <td class="px-3 py-2">
                        {{ $row->PPDCategoryType }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <!-- Cat A note on charts -->
        @if(isset($hasB) ? $hasB : ($results->pluck('PPDCategoryType')->contains('B')))
        <div class="text-center mt-2 text-sm">
            <p>The Property history above contains Category B sales, as these are not sales at an arm's length and can skew the charts below; no Category B sales are included.
                Category B sales are included in the table above for information only. In the event there are only Category B sales, the price history of the property chart will 
                show no data.
            </p>
        </div>
        @endif
    @endif

    <!-- EPC Matches (collapsible) -->
    @php
        $epcCount = is_countable($epcMatches ?? []) ? count($epcMatches ?? []) : 0;
    @endphp
    <details class="mt-6 group">
        <summary class="list-none select-none cursor-pointer flex items-center justify-between gap-3 rounded-md border border-zinc-200 bg-white px-4 py-3 shadow-sm hover:border-lime-400 hover:bg-lime-50">
            <div>
                <h2 class="text-lg font-semibold m-0">EPC Certificates (matched by postcode & address)</h2>
                <p class="text-xs text-zinc-600 mt-1">
                    {{ $epcCount }} match{{ $epcCount === 1 ? '' : 'es' }} found. Click to {{ 'view' }}.
                </p>
            </div>
            <span class="ml-4 inline-flex h-6 w-6 items-center justify-center rounded-full border border-zinc-300 text-zinc-600 group-open:rotate-180 transition-transform">▼</span>
        </summary>

        <div class="mt-4">
            <p class="text-sm text-zinc-600 mb-4">Due to inconsistency between the Land Registry & EPC dataset, address matching is not perfect mostly due to the EPC dataset. As a result I am using a fuzzy matching approach Based on the Levenshtein ratio with scoring. The higher the Match score the more likely it relates to this property.</p>

            @if(empty($epcMatches))
                <div class="border rounded bg-white p-4 text-sm text-zinc-600">No EPC certificates found for this property.</div>
            @else
                <div class="overflow-x-auto rounded border border-zinc-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-100">
                            <tr class="text-left">
                                <th class="px-3 py-2 border-b">Lodgement Date</th>
                                <th class="px-3 py-2 border-b">Rating</th>
                                <th class="px-3 py-2 border-b">Potential</th>
                                <th class="px-3 py-2 border-b">Address</th>
                                <th class="px-3 py-2 border-b text-right">floor space (sq ft)</th>
                                <th class="px-3 py-2 border-b">Match Score</th>
                                <th class="px-3 py-2 border-b text-center">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($epcMatches as $m)
                                @php
                                    $row = $m['row'];
                                @endphp
                                <tr class="odd:bg-white even:bg-zinc-50">
                                    <td class="px-3 py-2 border-b">{{ optional(\Carbon\Carbon::parse($row->lodgement_date))->format('d M Y') }}</td>
                                    <td class="px-3 py-2 border-b">{{ $row->current_energy_rating }}</td>
                                    <td class="px-3 py-2 border-b">{{ $row->potential_energy_rating }}</td>
                                    <td class="px-3 py-2 border-b">{{ $row->address }}</td>
                                    <td class="px-3 py-2 border-b text-right">
                                        {{ $row->total_floor_area ? number_format($row->total_floor_area * 10.7639, 0) : '' }}
                                    </td>
                                    <td class="px-3 py-2 border-b text-center">
                                        @php
                                            $s = (int) round($m['score'] ?? 0);
                                        @endphp
                                        @php
                                            if ($s >= 80) {
                                                $badge = ['High','bg-green-100 text-green-800 border-green-200'];
                                            } elseif ($s >= 65) {
                                                $badge = ['Medium','bg-amber-100 text-amber-800 border-amber-200'];
                                            } else {
                                                $badge = ['Low','bg-zinc-100 text-zinc-800 border-zinc-200'];
                                            }
                                        @endphp
                                        <span class="inline-flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $badge[1] }}">
                                                {{ $badge[0] }}
                                            </span>
                                            <span class="text-xs text-zinc-500">({{ $s }})</span>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 border-b text-center">
                                        @if(function_exists('route') && Route::has('epc.show'))
                                            <a
                                                href="{{ route('epc.show', ['lmk' => $row->lmk_key]) }}"
                                                class="inline-flex items-center justify-center gap-1 text-lime-700 hover:text-lime-900"
                                                title="View EPC report"
                                                aria-label="View EPC report for {{ $row->address ?? 'this property' }}{{ !empty($row->postcode) ? ', '.$row->postcode : '' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8 bg-zinc-700 hover:bg-zinc-500 text-white p-2 rounded" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                                                </svg>
                                                <span class="sr-only">View</span>
                                            </a>
                                        @else
                                            <span class="text-zinc-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </details>

    {{-- Deprivation (IMD 2019) — collapsible panel resolved by postcode via ONSPD --}}
    @php
        $depr = null; $deprMsg = null; $lsoaLink = null;
        $pcInput = trim((string)($postcode ?? ''));

        // Helper to standardize UK postcode to ONS PCDS format (e.g., "WR5 3EU")
        $stdPostcode = function($s) {
            $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$s));
            if ($s === '') return '';
            if (strlen($s) <= 3) return $s; // not a real pc, but avoid crash
            return substr($s, 0, -3) . ' ' . substr($s, -3);
        };

        if ($pcInput !== '') {
            $pcStd = $stdPostcode($pcInput);           // e.g. "WR5 3EU"
            $pcKey = strtoupper(str_replace(' ', '', $pcInput)); // legacy key
            $cacheKey = 'imd:pcstd:' . ($pcStd ?: $pcKey);

            $depr = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function () use ($pcStd, $pcKey) {
                // Resolve postcode → LSOA21/LSOA11 using indexed PCDS first
                $pcRow = null;
                if (!empty($pcStd)) {
                    $pcRow = DB::table('onspd')
                        ->select(['lsoa21','lsoa11','lat','long'])
                        ->where('pcds', $pcStd)
                        ->where(function($q){ $q->whereNull('doterm')->orWhere('doterm', ''); })
                        ->orderByDesc('dointr')
                        ->first();
                }
                if (!$pcRow) {
                    // Fallback to normalized matching if oddly formatted
                    $pcRow = DB::table('onspd')
                        ->select(['lsoa21','lsoa11','lat','long'])
                        ->whereRaw("REPLACE(UPPER(pcds),' ','') = ?", [$pcKey])
                        ->orWhereRaw("REPLACE(UPPER(pcd2),' ','') = ?", [$pcKey])
                        ->orWhereRaw("REPLACE(UPPER(pcd),' ','')  = ?", [$pcKey])
                        ->orderByDesc('dointr')
                        ->first();
                }

                if (!$pcRow) return ['error' => 'Postcode not found in ONSPD.'];

                $lsoa11 = $pcRow->lsoa11; $lsoa21 = $pcRow->lsoa21;

                // Bridge 2011→2021 if needed
                if (!$lsoa21 && $lsoa11) {
                    $map = DB::table('lsoa_2011_to_2021')->select('LSOA21CD')->where('LSOA11CD', $lsoa11)->first();
                    $lsoa21 = $map->LSOA21CD ?? null;
                }

                if (!$lsoa21) return ['error' => 'No LSOA mapping found for this postcode.'];
                if (substr($lsoa21, 0, 1) !== 'E') return ['error' => 'This postcode resolves to a non‑English LSOA (IMD is England‑only).'];

                $geo = DB::table('lsoa21_ruc_geo')->select('LSOA21NM','LAT','LONG')->where('LSOA21CD', $lsoa21)->first();

                // Single query: get overall decile & rank via conditional aggregation on normalized cols
                $imd = DB::table('imd2019 as t')
                    ->selectRaw(
                        "MAX(CASE WHEN t.measurement_norm='decile' AND t.iod_norm LIKE 'a. index of multiple deprivation%' THEN t.Value END) AS decile_val,\n" .
                        "MAX(CASE WHEN t.measurement_norm='rank'   AND t.iod_norm LIKE 'a. index of multiple deprivation%' THEN t.Value END) AS rank_val"
                    )
                    ->whereRaw('t.FeatureCode = ?', [$lsoa11])
                    ->first();

                // Cached total LSOAs (max rank)
                $totalRank = \Illuminate\Support\Facades\Cache::rememberForever('imd.total_rank', function () {
                    $n = (int) (DB::table('imd2019')
                        ->where('measurement_norm', 'rank')
                        ->where('iod_norm', 'like', 'a. index of multiple deprivation%')
                        ->max('Value') ?? 0);
                    return $n ?: 32844;
                });

                $rank = (int)($imd->rank_val ?? 0);
                $pct  = $rank ? max(0, min(100, (int) round((1 - (($rank - 1) / $totalRank)) * 100))) : null;

                return [
                    'lsoa21' => $lsoa21,
                    'lsoa11' => $lsoa11,
                    'name'   => $geo->LSOA21NM ?? $lsoa21,
                    'decile' => $imd->decile_val ?? null,
                    'rank'   => $rank ?: null,
                    'pct'    => $pct,
                    'total'  => $totalRank,
                    'lat'    => $geo->LAT ?? $pcRow->lat,
                    'long'   => $geo->LONG ?? $pcRow->long,
                ];
            });

            if (isset($depr['error'])) { $deprMsg = $depr['error']; $depr = null; }
            if ($depr) { $lsoaLink = route('deprivation.show', $depr['lsoa21']); }
        }

        // Badge style for decile
        $badgeClass = function($d){
            $d = (int)($d ?? 0);
            if ($d >= 8) return 'bg-emerald-100 text-emerald-800';
            if ($d >= 5) return 'bg-amber-100 text-amber-800';
            if ($d >= 1) return 'bg-rose-100 text-rose-800';
            return 'bg-zinc-100 text-zinc-700';
        };
    @endphp

    <details class="my-8 group">
        <summary class="list-none select-none cursor-pointer flex items-center justify-between gap-3 rounded-md border border-zinc-200 bg-white px-4 py-3 shadow-sm hover:border-lime-400 hover:bg-lime-50">
            <div>
                <h2 class="text-lg font-semibold m-0">Local Deprivation Index</h2>
                <p class="text-xs text-zinc-600 mt-1">Derived from postcode via ONSPD → LSOA (England only).</p>
            </div>
            <span class="ml-4 inline-flex h-6 w-6 items-center justify-center rounded-full border border-zinc-300 text-zinc-600 group-open:rotate-180 transition-transform">▼</span>
        </summary>

        <div class="mt-4">
            @if($depr)
                <div class="rounded border border-zinc-200 bg-white p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left: everything except Decile/Rank -->
                        <div>
                            <div class="text-sm text-zinc-600">LSOA21</div>
                            <div class="font-medium">{{ $depr['name'] }} <span class="text-xs text-zinc-500">({{ $depr['lsoa21'] }})</span></div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                @if($lsoaLink)
                                    <a href="{{ $lsoaLink }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-zinc-700 rounded-md hover:bg-zinc-500 border border-lime-200">Full details</a>
                                @endif
                                @if(($depr['lat'] ?? null) && ($depr['long'] ?? null))
                                    <a href="https://www.google.com/maps?q={{ $depr['lat'] }},{{ $depr['long'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-white rounded-md hover:bg-zinc-100 border">View on map</a>
                                @endif
                            </div>

                            <p class="mt-3 text-xs text-zinc-500">Note: “Deprivation” is a statistical term about access to resources and services; it is not a label on people or places. IMD 2019 is the latest full release for England.</p>
                        </div>

                        <!-- Right: Decile & Rank presentation -->
                        <div class="md:flex md:items-stretch md:justify-end">
                            <div class="w-full md:w-auto rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-center">
                                <div class="grid grid-cols-2 gap-4 md:flex md:flex-col md:gap-4 md:text-left">
                                    <div class="md:order-1">
                                        <div class="text-xs text-zinc-600 mb-1">Decile</div>
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-sm font-medium {{ $badgeClass($depr['decile']) }}">
                                            {{ $depr['decile'] ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="md:order-2">
                                        <div class="text-xs text-zinc-600 mb-1">Rank</div>
                                        <div class="text-2xl font-semibold leading-none">{{ $depr['rank'] ? number_format($depr['rank']) : 'N/A' }}</div>
                                        @if(!is_null($depr['pct']))
                                            <div class="text-xs text-zinc-500 mt-1">
                                                {{ number_format($depr['total'] ?? 32844) }} total · top {{ $depr['pct'] }}%
                                                {{ ($depr['pct'] ?? 0) >= 50 ? 'most deprived' : 'least deprived' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
                    {{ $deprMsg ?? 'Unable to resolve this postcode to an English LSOA.' }}
                </div>
            @endif
        </div>
    </details>

    <div class="my-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Price History of this property</h2>
            <canvas id="priceHistoryChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Average Price of property in {{ $postcode }}</h2>
            <canvas id="postcodePriceChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Number of Sales in {{ $postcode }}</h2>
            <canvas id="postcodeSalesChart" class="block w-full"></canvas>
        </div>
        <!-- Locality Charts (moved up) -->
        @if($showLocalityCharts)
        <!-- Locality Charts (shown only when locality is present and distinct) -->
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($locality)) }}</h2>
            <canvas id="localityPropertyTypesChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($locality)) }}</h2>
            <canvas id="localityPriceChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($locality)) }}</h2>
            <canvas id="localitySalesChart" class="block w-full"></canvas>
        </div>
        @endif
        @if($showTownCharts)
        <!-- Town/City Charts -->
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($town)) }}</h2>
            <canvas id="townPropertyTypesChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($town)) }}</h2>
            <canvas id="townPriceChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($town)) }}</h2>
            <canvas id="townSalesChart" class="block w-full"></canvas>
        </div>
        @endif
        <!-- District Charts -->
        @if($showDistrictCharts)
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Property Types in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
            <canvas id="districtPropertyTypesChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Average Price of property in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
            <canvas id="districtPriceChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Number of Sales in {{ $district !== '' ? ucfirst(strtolower($district)) : ucfirst(strtolower($county)) }}</h2>
            <canvas id="districtSalesChart" class="block w-full"></canvas>
        </div>
        @endif
        @if(!empty($county))
        <!-- County Charts -->
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Property Types in {{ ucfirst(strtolower($county)) }}</h2>
            <canvas id="countyPropertyTypesChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Average Price of property in {{ ucfirst(strtolower($county)) }}</h2>
            <canvas id="countyPriceChart" class="block w-full"></canvas>
        </div>
        <div class="border border-zinc-200 rounded-md p-2">
            <h2 class="text-lg font-bold mb-4">Number of Sales in {{ ucfirst(strtolower($county)) }}</h2>
            <canvas id="countySalesChart" class="block w-full"></canvas>
        </div>
        @endif
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('priceHistoryChart').getContext('2d');
const priceData = @json($priceHistory->pluck('avg_price'));
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($priceHistory->pluck('year')),
        datasets: [{
            label: 'Sale Price (£)',
            data: priceData,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'blue';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Format as currency with commas
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

const ctxPostcode = document.getElementById('postcodePriceChart').getContext('2d');
const postcodePriceData = @json(($postcodePriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxPostcode, {
    type: 'line',
    data: {
        labels: @json(($postcodePriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: postcodePriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Format as currency with commas
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// District Price Chart with canvas guard
(function(){
  const el = document.getElementById('districtPriceChart');
  if (!el) return;
  const ctxDistrict = el.getContext('2d');
  const districtPriceData = @json(($districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('avg_price'));
  new Chart(ctxDistrict, {
    type: 'line',
    data: {
        labels: @json(($districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: districtPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90 }
            },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
})();
@if(!empty($county))
// County Price Chart
const ctxCountyPrice = document.getElementById('countyPriceChart').getContext('2d');
const countyPriceData = @json(($countyPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxCountyPrice, {
    type: 'line',
    data: {
        labels: @json(($countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: countyPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: { beginAtZero: false, ticks: { callback: function(value) { return '£' + value.toLocaleString(); } } }
        }
    }
});
@endif
@if($showTownCharts)
// Town/City Price Chart
const ctxTownPrice = document.getElementById('townPriceChart').getContext('2d');
const townPriceData = @json(($townPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxTownPrice, {
    type: 'line',
    data: {
        labels: @json(($townPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: townPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
@endif
@if($showLocalityCharts)
// Locality Price Chart
const ctxLocality = document.getElementById('localityPriceChart').getContext('2d');
const localityPriceData = @json(($localityPriceHistory ?? $districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('avg_price'));
new Chart(ctxLocality, {
    type: 'line',
    data: {
        labels: @json(($localityPriceHistory ?? $districtPriceHistory ?? $countyPriceHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Average Price (£)',
            data: localityPriceData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + '£' + value.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90 } },
            y: {
                beginAtZero: false,
                ticks: { callback: function(value) { return '£' + value.toLocaleString(); } }
            }
        }
    }
});
@endif
</script>
<script>
// === Property Types chart helpers (uniform look & order) ===
function normalizePropertyTypes(rawLabels, rawCounts) {
    // Map any incoming label to a canonical bucket
    function canonical(lab) {
        const s = String(lab || '').trim().toLowerCase();
        if (!s) return 'Other';
        if (s.startsWith('flat')) return 'Flat';
        if (s.startsWith('det')) return 'Detached';
        if (s.startsWith('semi')) return 'Semi'; // handles "Semi-Detached", "Semi Detached", "Semi"
        if (s.startsWith('terr')) return 'Terraced';
        if (s === 'other') return 'Other';
        return 'Other';
    }

    // Tally counts into canonical buckets (always include all five buckets)
    const counts = { Flat: 0, Detached: 0, Semi: 0, Terraced: 0, Other: 0 };
    (rawLabels || []).forEach((lab, i) => {
        const key = canonical(lab);
        const val = (rawCounts || [])[i] ?? 0;
        counts[key] = (counts[key] || 0) + (Number(val) || 0);
    });

    // Sort buckets by value (desc) and build arrays
    const sorted = Object.entries(counts).sort((a, b) => (b[1] || 0) - (a[1] || 0));
    const labels = sorted.map(([k]) => k);
    const data = sorted.map(([, v]) => v || 0);
    return { labels, data };
}

const typeColor = {
    'Flat': 'rgba(54, 162, 235, 0.7)',
    'Detached': 'rgba(153, 102, 255, 0.7)',
    'Semi': 'rgba(255, 99, 132, 0.7)',
    'Terraced': 'rgba(255, 159, 64, 0.7)',
    'Other': 'rgba(75, 192, 192, 0.7)'
};
function colorsFor(labels) { return labels.map(l => typeColor[l] || 'rgba(160,160,160,0.7)'); }
function solidize(cols) { return cols.map(c => c.replace('0.7', '1')); }
const commonBarOptions = {
    responsive: true,
    layout: { padding: { bottom: 0 } },
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { maxRotation: 0, minRotation: 0, padding: 4 } },
        y: { beginAtZero: true, title: { display: true, text: 'Count' }, ticks: { precision: 0 } }
    }
};
// Property Types charts (uniform styling)
// District Property Types Chart with canvas guard
(function(){
  const el = document.getElementById('districtPropertyTypesChart');
  if (!el) return;
  const ctxDistrictTypes = el.getContext('2d');
  const districtRawLabels = @json(($districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('label'));
  const districtRawCounts = @json(($districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('value'));
  const districtNorm = normalizePropertyTypes(districtRawLabels, districtRawCounts);
  const districtCols = colorsFor(districtNorm.labels);
  new Chart(ctxDistrictTypes, {
      type: 'bar',
      data: {
          labels: districtNorm.labels,
          datasets: [{
              label: 'Count',
              data: districtNorm.data,
              backgroundColor: districtCols,
              borderColor: solidize(districtCols),
              borderWidth: 1
          }]
      },
      options: commonBarOptions
  });
})();
@if($showLocalityCharts)
// Locality Property Types Chart
const ctxLocalityTypes = document.getElementById('localityPropertyTypesChart').getContext('2d');
const localityRawLabels = @json(($localityPropertyTypes ?? $districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('label'));
const localityRawCounts = @json(($localityPropertyTypes ?? $districtPropertyTypes ?? $countyPropertyTypes ?? collect())->pluck('value'));
const localityNorm = normalizePropertyTypes(localityRawLabels, localityRawCounts);
const localityCols = colorsFor(localityNorm.labels);
new Chart(ctxLocalityTypes, {
    type: 'bar',
    data: {
        labels: localityNorm.labels,
        datasets: [{
            label: 'Count',
            data: localityNorm.data,
            backgroundColor: localityCols,
            borderColor: solidize(localityCols),
            borderWidth: 1
        }]
    },
    options: commonBarOptions
});
@endif
@if($showTownCharts)
// Town/City Property Types Chart
const ctxTownTypes = document.getElementById('townPropertyTypesChart').getContext('2d');
const townRawLabels = @json(($townPropertyTypes ?? collect())->pluck('label'));
const townRawCounts = @json(($townPropertyTypes ?? collect())->pluck('value'));
const townNorm = normalizePropertyTypes(townRawLabels, townRawCounts);
const townCols = colorsFor(townNorm.labels);
new Chart(ctxTownTypes, {
    type: 'bar',
    data: {
        labels: townNorm.labels,
        datasets: [{
            label: 'Count',
            data: townNorm.data,
            backgroundColor: townCols,
            borderColor: solidize(townCols),
            borderWidth: 1
        }]
    },
    options: commonBarOptions
});
@endif
@if(!empty($county))
// County Property Types Chart
const ctxCountyTypes = document.getElementById('countyPropertyTypesChart').getContext('2d');
const countyRawLabels = @json(($countyPropertyTypes ?? collect())->pluck('label'));
const countyRawCounts = @json(($countyPropertyTypes ?? collect())->pluck('value'));
const countyNorm = normalizePropertyTypes(countyRawLabels, countyRawCounts);
const countyCols = colorsFor(countyNorm.labels);
new Chart(ctxCountyTypes, {
    type: 'bar',
    data: {
        labels: countyNorm.labels,
        datasets: [{
            label: 'Count',
            data: countyNorm.data,
            backgroundColor: countyCols,
            borderColor: solidize(countyCols),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { maxRotation: 0, minRotation: 0, padding: 4 } },
            y: { beginAtZero: true, title: { display: true, text: 'Count' }, ticks: { precision: 0 } }
        }
    }
});
@endif
const ctxPostcodeSales = document.getElementById('postcodeSalesChart').getContext('2d');
const postcodeSalesData = @json(($postcodeSalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxPostcodeSales, {
    type: 'line',
    data: {
        labels: @json(($postcodeSalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: postcodeSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: {
            legend: {
                display: true
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: {
                display: false
            }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90, padding: 4 }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
// District Sales Chart with canvas guard
(function(){
  const el = document.getElementById('districtSalesChart');
  if (!el) return;
  const ctxDistrictSales = el.getContext('2d');
  const districtSalesData = @json(($districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('total_sales'));
  new Chart(ctxDistrictSales, {
    type: 'line',
    data: {
        labels: @json(($districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: districtSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: {
                ticks: { minRotation: 90, maxRotation: 90, padding: 4 }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
  });
})();
@if(!empty($county))
// County Sales Chart
const ctxCountySales = document.getElementById('countySalesChart').getContext('2d');
const countySalesData = @json(($countySalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxCountySales, {
    type: 'line',
    data: {
        labels: @json(($countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: countySalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90, padding: 4 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
@if($showTownCharts)
// Town/City Sales Chart
const ctxTownSales = document.getElementById('townSalesChart').getContext('2d');
const townSalesData = @json(($townSalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxTownSales, {
    type: 'line',
    data: {
        labels: @json(($townSalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: townSalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90, padding: 4 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
@if($showLocalityCharts)
// Locality Sales Chart
const ctxLocalitySales = document.getElementById('localitySalesChart').getContext('2d');
const localitySalesData = @json(($localitySalesHistory ?? $districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('total_sales'));
new Chart(ctxLocalitySales, {
    type: 'line',
    data: {
        labels: @json(($localitySalesHistory ?? $districtSalesHistory ?? $countySalesHistory ?? collect())->pluck('year')),
        datasets: [{
            label: 'Sales Count',
            data: localitySalesData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: function(context) {
                const index = context.dataIndex;
                const value = context.dataset.data[index];
                const prev = index > 0 ? context.dataset.data[index - 1] : value;
                return value < prev ? 'red' : 'rgb(54, 162, 235)';
            }
        }]
    },
    options: {
        responsive: true,
        layout: { padding: { bottom: 0 } },
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        let value = context.parsed.y !== undefined ? context.parsed.y : context.formattedValue;
                        return label + value.toLocaleString();
                    }
                }
            },
            title: { display: false }
        },
        scales: {
            x: { ticks: { minRotation: 90, maxRotation: 90, padding: 4 } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
@endif
</script>

<!-- Notes -->
<div class="text-sm text-zinc-500">
    <h2 class="font-bold mb-4">Notes:</h2>
    <ol class="list-decimal list-inside space-y-1 pl-4">
        <li>Other Property Type relates to properties such as mixed-use, converted barns, lighthouses or unusual property that does not fit in standard Detached, Semi, Terraced or Flat.</li>
    </ol>    
</div>

</div>

@endsection