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
                You can either search directly on the map, for a specific postcode to see every sale in that postcode, or search for a
                locality, town/city, district or county to view a wider area summary. Data covers the period from
                January 1995 to October 2025.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/property_search.svg') }}" alt="Property search" class="w-64 h-auto">
        </div>
    </section>

    {{-- England & Wales heatmap --}}
    <section class="mb-12">
        <div class="rounded border border-zinc-200 bg-white/80 p-6">
            <div class="flex items-start justify-between gap-6 flex-col md:flex-row">
                <div>
                    <h2 class="text-base font-semibold mb-2">
                        <i class="fa-solid fa-map-location-dot text-lime-600"></i> Land Registry points (England &amp; Wales)
                    </h2>
                    <p class="text-xs text-zinc-600">
                        View all Land Registry properties on the map below.  The more you zoom in, the more points will load, right down to street level.  At the lowest level, green dots show
                        property sales.  England and Wales only.
                    </p>
                </div>
                <p class="text-xs text-zinc-400">Data source: Land Registry + ONSPD</p>
            </div>
            <div id="property-points-map" class="mt-4 h-96 md:h-[32rem] w-full rounded border border-zinc-200 bg-zinc-50"></div>
            <p id="points-status" class="mt-2 text-xs text-zinc-500">Zoom in to load property points.</p>
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
                    want to look at a specific street or a small cluster of properties.  Property results are show in a table below once you click 'Search postcode'.
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
                        class="inner-button">
                        Search Postcode
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
                                        class="bg-lime-600 hover:bg-lime-700 text-white p-2 rounded inline-flex items-center"
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

{{-- Bulk postcode coordinates (from controller) to avoid per-row ONSPD lookups --}}
<script>
    window.propertyCoordsByPostcode = @json($coordsByPostcode ?? []);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pointsEl = document.getElementById('property-points-map');
        const pointsStatus = document.getElementById('points-status');
        if (pointsEl && typeof L !== 'undefined') {
            const bounds = L.latLngBounds([49.8, -6.6], [55.9, 2.2]);
            const map = L.map('property-points-map', { scrollWheelZoom: false, maxBounds: bounds }).setView([52.7, -1.6], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18,
            }).addTo(map);

            const cluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 40,
                disableClusteringAtZoom: 15,
                removeOutsideVisibleBounds: true,
            });
            map.addLayer(cluster);

            let activeController = null;
            let loadTimer = null;

            const fmtGBP = function (value) {
                if (value == null) return 'N/A';
                return '£' + Number(value).toLocaleString('en-GB');
            };

            const fmtDate = function (value) {
                if (!value) return 'Unknown date';
                const d = new Date(value);
                if (Number.isNaN(d.getTime())) return String(value);
                return d.toLocaleDateString('en-GB');
            };

            const loadPoints = function () {
                if (loadTimer) window.clearTimeout(loadTimer);
                loadTimer = window.setTimeout(function () {
                    const zoom = map.getZoom();
                    const b = map.getBounds();
                    const limit = zoom >= 16 ? 12000 : (zoom >= 14 ? 8000 : 3000);

                    if (activeController) activeController.abort();
                    activeController = new AbortController();

                    if (pointsStatus) pointsStatus.textContent = 'Loading property points…';

                    const url = new URL('{{ route('property.points', [], false) }}', window.location.origin);
                    url.searchParams.set('south', b.getSouthWest().lat.toFixed(6));
                    url.searchParams.set('west', b.getSouthWest().lng.toFixed(6));
                    url.searchParams.set('north', b.getNorthEast().lat.toFixed(6));
                    url.searchParams.set('east', b.getNorthEast().lng.toFixed(6));
                    url.searchParams.set('zoom', String(zoom));
                    url.searchParams.set('limit', String(limit));

                    fetch(url.toString(), { signal: activeController.signal })
                        .then(function (response) {
                            if (response.status === 202) {
                                return response.json().then(function (payload) {
                                    if (pointsStatus) pointsStatus.textContent = payload.message || 'Zoom in to load property points.';
                                    return null;
                                });
                            }
                            if (!response.ok) throw new Error('Points response was not ok');
                            return response.json();
                        })
                        .then(function (payload) {
                            if (!payload) return;
                            const points = Array.isArray(payload.points) ? payload.points : [];
                            cluster.clearLayers();

                            points.forEach(function (pt) {
                                const marker = L.circleMarker([pt.lat, pt.lng], {
                                    radius: 6,
                                    color: '#0f172a',
                                    weight: 1.5,
                                    fillColor: '#22c55e',
                                    fillOpacity: 0.9,
                                });

                                const label = pt.address ? pt.address + ', ' + pt.postcode : pt.postcode;
                                const popup = '<div class="text-xs">' +
                                    '<div class="font-semibold">' + (label || 'Property') + '</div>' +
                                    '<div class="mt-1">Date: ' + fmtDate(pt.date) + '</div>' +
                                    '<div>Price: ' + fmtGBP(pt.price) + '</div>' +
                                    '<div class="mt-2"><a class="text-lime-700 hover:underline" href="' + pt.url + '">View property</a></div>' +
                                    '</div>';

                                marker.bindPopup(popup);
                                cluster.addLayer(marker);
                            });

                            if (pointsStatus) {
                                pointsStatus.textContent = payload.truncated
                                    ? 'Showing a sample of points in view. Zoom in for more detail.'
                                    : 'Showing ' + points.length.toLocaleString('en-GB') + ' points in view.';
                            }
                        })
                        .catch(function (err) {
                            if (err && err.name === 'AbortError') return;
                            if (pointsStatus) pointsStatus.textContent = 'Property points could not be loaded right now.';
                        });
                }, 200);
            };

            map.on('moveend zoomend', loadPoints);
            loadPoints();
        }

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
