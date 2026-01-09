@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Search</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                Use this page to explore <span class="font-bold">Land Registry</span> sale records for England &amp; Wales.
                There are currently in excess of<span class="text-lime-700"> 30m</span> records in this table.
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                You can either search for a specific postcode to see every sale in that postcode, or search for a
                locality, town/city, district or county to view a wider area summary. Data covers the period from
                January 1995 to October 2025.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/property_search.svg') }}" alt="Property search" class="w-64 h-auto">
        </div>
    </section>

    {{-- Search tools --}}
    <section class="mb-10">
        <div class="grid gap-6 md:grid-cols-2">

            {{-- Search by postcode (specific properties) --}}
            <div class="rounded border border-zinc-200 bg-white/80 p-6">
                <h2 class="text-base font-semibold mb-2"><i class="fa-solid fa-magnifying-glass-chart text-lime-600"></i> Search by postcode</h2>
                <p class="text-xs text-zinc-600 mb-4">
                    Enter a full postcode to see every sale in that postcode from 1995 onwards. This is best when you
                    want to look at a specific street or a small cluster of properties.
                </p>

                <form method="GET" action="{{ route('property.search') }}" class="space-y-3">
                    <div>
                        <input
                            id="postcode"
                            name="postcode"
                            type="text"
                            value="{{ old('postcode', $postcode ?? '') }}"
                            placeholder="e.g. WR5 3EU or SW7 5PH"
                            class="w-full border border-zinc-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-lime-500 bg-white"
                        />
                        @error('postcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="standard-button">
                        Search postcode
                    </button>
                </form>
            </div>

            {{-- Search by area (summary dashboards) --}}
            <div class="rounded border border-zinc-200 bg-white/80 p-6">
                <h2 class="text-base font-semibold mb-2"><i class="fa-solid fa-magnifying-glass-location text-lime-600"></i> Search by area</h2>
                <p class="text-xs text-zinc-600 mb-4">
                    Start typing the name of a locality, town/city, district or county in England &amp; Wales. Choose one
                    of the suggestions to go straight to an area dashboard showing prices, sales and property types.
                </p>

                <div class="relative">
                    <input
                        id="district-search"
                        type="text"
                        autocomplete="off"
                        placeholder="e.g. Worcester, Kensington, Gloucestershire"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-lime-500 bg-white"
                    />
                    <div
                        id="district-suggestions"
                        class="absolute z-20 mt-1 w-full bg-white border border-zinc-200 rounded-md shadow-lg max-h-64 overflow-y-auto text-sm hidden">
                        {{-- Suggestions will be injected here by JavaScript --}}
                    </div>
                </div>
                <p class="mt-2 text-xs text-zinc-500">Click on one of the suggestions to open the dashboard for that area.</p>
            </div>

        </div>
    </section>

    {{-- Results --}}
    @if(isset($results))
        @if($results->count() === 0)
            <div class="border border-zinc-200 rounded-md p-4 text-zinc-600 bg-white">
                No results for <span class="font-semibold text-orange-400">{{ $postcode }}</span>.
            </div>
        @else
            <div class="flex justify-center mb-4 text-sm text-zinc-500">
                <p>Click on the magnifying glass on the right hand side to get more detail about a specific property</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50">
                        @php
                            $currentSort = $sort ?? request('sort', 'Date');
                            $currentDir  = $dir ?? request('dir', 'desc');
                            $base = [];
                            if (!empty($postcode)) { $base['postcode'] = $postcode; }
                            $dirBadge = function($key) use ($currentSort, $currentDir) {
                                return $currentSort === $key ? ' ('.strtoupper($currentDir).')' : '';
                            };
                            $thClass = function($key) use ($currentSort) {
                                return $currentSort === $key ? 'bg-zinc-300 font-bold' : '';
                            };
                        @endphp
                        <tr class="text-left">
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Date') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Date{!! $dirBadge('Date') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Date', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Date', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Price') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Price{!! $dirBadge('Price') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Price', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Price', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PropertyType') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Type{!! $dirBadge('PropertyType') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PropertyType', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PropertyType', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('NewBuild') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">New Build?{!! $dirBadge('NewBuild') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'NewBuild', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'NewBuild', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Duration') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Tenure{!! $dirBadge('Duration') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Duration', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Duration', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PAON') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Primary{!! $dirBadge('PAON') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PAON', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PAON', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('SAON') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Secondary{!! $dirBadge('SAON') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'SAON', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'SAON', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('Street') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Street{!! $dirBadge('Street') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Street', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'Street', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('County') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">County{!! $dirBadge('County') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'County', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'County', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PPDCategoryType') }}">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium">Category{!! $dirBadge('PPDCategoryType') !!}</span>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PPDCategoryType', 'dir' => 'asc'])) }}" class="text-xs" title="Sort ascending">▲</a>
                                    <a href="{{ route('property.search', array_merge($base, ['sort' => 'PPDCategoryType', 'dir' => 'desc'])) }}" class="text-xs" title="Sort descending">▼</a>
                                </div>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap {{ $thClass('PPDCategoryType') }}">
                                <div class="flex items-center gap-1">
                                    View
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $row)
                            <tr class="border-t {{ ($row->PPDCategoryType ?? null) === 'B' ? 'bg-rose-50' : '' }}">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ optional($row->Date)->format('d-m-Y') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    £{{ number_format($row->Price) }}
                                </td>
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
                                    @if($row->NewBuild === 'Y')
                                        Yes
                                    @elseif($row->NewBuild === 'N')
                                        No
                                    @else
                                        {{ $row->NewBuild }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row->Duration === 'F')
                                        Freehold
                                    @elseif($row->Duration === 'L')
                                        Leasehold
                                    @else
                                        {{ $row->Duration }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->PAON }}
                                </td>
                                <td class="px-3 py-2">
                                    @if(empty($row->SAON))
                                        N/A
                                    @else
                                        {{ $row->SAON }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->Street }}
                                </td> 
                                <td class="px-3 py-2">
                                    {{ $row->County }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $row->PPDCategoryType }}
                                </td>
                                <td class="px-3 py-2">
                                    <a
                                        href="{{ route('property.show', [
                                            'postcode' => $row->Postcode ?? '',
                                            'paon'     => $row->PAON,
                                            'street'   => $row->Street ?? '',
                                            'saon'     => $row->SAON ?? ''
                                        ]) }}"
                                        class="bg-lime-700 hover:bg-zinc-500 text-white p-2 rounded inline-flex items-center"
                                        title="View property details"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentcolor" viewBox="0 0 20 20" width="16" height="16">
                                            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- was: <div class="mt-4 flex justify-center"> --}}
            <div class="mt-4">
                <div class="w-full">
                    {{ $results->links() }}
                </div>
            </div>

            <div class="mt-10 text-zinc-500 text-sm">
                <h2 class="font-bold mb-2">Notes:</h2>
                <p class="mb-2">The status column indicates a clean sale at market value on an arms legnth basis if set to A.</p>
                <p>If set to B.  repossessions / power-of-sale, buy-to-let where identifiable by a mortgage, sales to companies or social landlords, transfers of part, 
                    transactions not clearly at full market value, or where the property type is unknown.</p>
            </div>
        @endif
    @endif
</div>

{{-- Bulk postcode coordinates (from controller) to avoid per-row ONSPD lookups --}}
<script>
    window.propertyCoordsByPostcode = @json($coordsByPostcode ?? []);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('district-search');
        const suggestionsBox = document.getElementById('district-suggestions');
        if (!input || !suggestionsBox) return;

        const typeLabels = {
            locality: 'Locality',
            town: 'Town',
            district: 'District',
            county: 'County',
        };

        let allDistricts = [];

        fetch('{{ asset('data/property_districts.json') }}')
            .then(function (response) {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(function (data) {
                if (!Array.isArray(data)) return;
                allDistricts = data;
            })
            .catch(function () {
                // Fail silently if the JSON file is missing or invalid
            });

        function renderSuggestions(query) {
            suggestionsBox.innerHTML = '';

            const q = query.trim().toLowerCase();
            if (!q) {
                suggestionsBox.classList.add('hidden');
                return;
            }

            const matches = allDistricts
                .filter(function (item) {
                    const name = (item && item.name) ? String(item.name) : (item && item.label ? String(item.label) : '');
                    if (!name) return false;
                    return name.toLowerCase().includes(q);
                })
                .slice(0, 15);

            if (matches.length === 0) {
                suggestionsBox.classList.add('hidden');
                return;
            }

            matches.forEach(function (item) {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'w-full text-left px-3 py-2 hover:bg-zinc-100 cursor-pointer flex flex-col';

                const main = document.createElement('span');
                main.className = 'font-medium text-zinc-800 flex items-baseline';

                const nameSpan = document.createElement('span');
                nameSpan.textContent = item.name || item.label || '';
                main.appendChild(nameSpan);

                if (item.type) {
                    const typePretty = typeLabels[item.type] || item.type;
                    const typeSpan = document.createElement('span');
                    typeSpan.className = 'ml-1 text-xs text-zinc-400';
                    typeSpan.textContent = ' (' + typePretty + ')';
                    main.appendChild(typeSpan);
                }

                option.appendChild(main);

                option.addEventListener('click', function () {
                    if (item.path) {
                        window.location.href = item.path;
                    } else {
                        input.value = item.label;
                        suggestionsBox.classList.add('hidden');
                    }
                });

                suggestionsBox.appendChild(option);
            });

            suggestionsBox.classList.remove('hidden');
        }

        input.addEventListener('input', function () {
            renderSuggestions(this.value);
        });

        document.addEventListener('click', function (event) {
            if (!suggestionsBox.contains(event.target) && event.target !== input) {
                suggestionsBox.classList.add('hidden');
            }
        });
    });
</script>

@endsection