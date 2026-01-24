@extends('layouts.app')

@section('content')
@php
    $pcRaw = request('postcode');
    $pcClean = null;
    if (!empty($pcRaw)) {
        $pcClean = strtoupper(preg_replace('/\s+/', '', $pcRaw));
        if (strlen($pcClean) >= 5) {
            $pcClean = substr($pcClean, 0, -3) . ' ' . substr($pcClean, -3);
        }
    }
    // Build a clean return URL where spaces are encoded as '+' (no %20)
    $current = request()->fullUrl();
    $parts = parse_url($current);
    $qs = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qs);
    }
    if ($pcClean) {
        $qs['postcode'] = $pcClean; // keep a readable space here; we'll render as '+' below
    }
    $scheme = $parts['scheme'] ?? (request()->isSecure() ? 'https' : 'http');
    $host   = $parts['host']   ?? request()->getHost();
    $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path   = $parts['path']   ?? request()->getPathInfo();
    $query  = http_build_query($qs, '', '&', PHP_QUERY_RFC1738); // RFC1738 encodes spaces as '+'
    $cleanReturnUrl = $scheme . '://' . $host . $port . $path . ($query ? ('?' . $query) : '');
@endphp
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">
    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Search EPC records in England & Wales</h1>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from January 2008 to July 2025.  Search by zooming in on the map below or enter a postcode to see a list of EPC certificates.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/epc.svg') }}" alt="EPC Search" class="w-64 h-auto">
        </div>
    </section>

    {{-- EPC map --}}
    <section class="mb-10 rounded border border-zinc-200 bg-white/80 p-6">
        <div class="flex items-start justify-between gap-6 flex-col md:flex-row">
            <div>
                <h2 class="text-base font-semibold mb-2">
                    <i class="fa-solid fa-map-location-dot text-lime-600"></i> EPC locations (England & Wales)
                </h2>
                <p class="text-xs text-zinc-600">
                    Zoom in to see EPC certificates as pins. Click a pin to open the EPC report.  Note that this data is only as good as the EPC Register's UPRN matching.  It's ok, but not
                    perfect.  I see a lot of quality issues in some areas.
                </p>
            </div>
        </div>
        <div id="epc-map" class="mt-4 h-96 md:h-[36rem] w-full rounded border border-zinc-200 bg-zinc-50"></div>
        <p id="epc-map-status" class="mt-2 text-xs text-zinc-500">Zoom in to load EPC points.</p>
    </section>

    {{-- Search form --}}
    <div class="flex justify-center">
        <form method="GET" action="{{ route('epc.search') }}" class="mb-10 w-full lg:w-1/2 mx-auto border bg-white/80 p-6 rounded">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="postcode" class="block text-sm font-medium mb-1">Enter a postcode below to view EPC certificates.</label>
                    <input
                        id="postcode"
                        name="postcode"
                        type="text"
                        value="{{ old('postcode', request('postcode')) }}"
                        placeholder="e.g. WR5 3EU or SW7 5PH"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-lime-500 bg-white"
                        required
                    />
                    @error('postcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="standard-button mt-0 h-[42px] inline-flex items-center justify-center">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Results --}}
    @isset($results)
        @if($results instanceof \Illuminate\Contracts\Pagination\Paginator || $results instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            @php($count = $results->total() ?? $results->count())
        @else
            @php($count = is_countable($results ?? []) ? count($results) : 0)
        @endif

        @if($count === 0)
            <div class="rounded border border-zinc-200 bg-white p-6">
                <p>No EPCs found for <span class="font-semibold">{{ request('postcode') }}</span>.</p>
            </div>
        @else
            <div class="mb-3 text-sm text-zinc-600">
                Showing <span class="font-semibold">{{ number_format($count) }}</span> result{{ $count === 1 ? '' : 's' }} for postcode <span class="font-semibold">{{ $pcClean ?? request('postcode') }}</span>.
            </div>

            <div class="overflow-x-auto rounded border border-zinc-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-100">
    <tr>
        {{-- Date (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort','lodgement_date')==='lodgement_date'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'lodgement_date', 'dir' => (request('sort','lodgement_date')==='lodgement_date' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                Date
                <span>
                    @if(request('sort','lodgement_date')==='lodgement_date')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- Address (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort')==='address'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'address', 'dir' => (request('sort')==='address' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                Address
                <span>
                    @if(request('sort')==='address')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- Postcode (not sortable) --}}
        <th class="px-3 py-2 text-left border-b">Postcode</th>

        {{-- Rating (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort')==='current_energy_rating'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'current_energy_rating', 'dir' => (request('sort')==='current_energy_rating' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                Rating
                <span>
                    @if(request('sort')==='current_energy_rating')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- Potential (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort')==='potential_energy_rating'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'potential_energy_rating', 'dir' => (request('sort')==='potential_energy_rating' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                Potential
                <span>
                    @if(request('sort')==='potential_energy_rating')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- Property Type (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort')==='property_type'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'property_type', 'dir' => (request('sort')==='property_type' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                Property Type
                <span>
                    @if(request('sort')==='property_type')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- sq ft (sortable) --}}
        <th class="@class(['px-3 py-2 text-left border-b', 'bg-lime-100 font-bold' => request('sort')==='total_floor_area'])">
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_floor_area', 'dir' => (request('sort')==='total_floor_area' && request('dir','desc')==='asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1">
                sq ft
                <span>
                    @if(request('sort')==='total_floor_area')
                        {{ request('dir','desc')==='asc' ? '▲' : '▼' }}
                    @else
                        ↕
                    @endif
                </span>
            </a>
        </th>

        {{-- Local Authority (not sortable) --}}
        <th class="px-3 py-2 text-left border-b">Local Authority</th>
        {{-- View (not sortable) --}}
        <th class="px-3 py-2 text-left border-b">View</th>
    </tr>
</thead>
                    <tbody>
                        @foreach($results as $row)
                            <tr class="odd:bg-white even:bg-zinc-50">
                                <td class="px-3 py-2 align-middle border-b">{{ optional(\Carbon\Carbon::parse($row->lodgement_date))->format('d M Y') }}</td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->address }}</td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->postcode }}</td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->current_energy_rating }}</td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->potential_energy_rating }}</td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->property_type }}</td>
                                <td class="px-3 py-2 align-middle border-b text-right">
                                    {{ $row->total_floor_area ? number_format($row->total_floor_area * 10.7639, 0) : '' }}
                                </td>
                                <td class="px-3 py-2 align-middle border-b">{{ $row->local_authority_label }}</td>
                                <td class="px-3 py-2 align-middle border-b text-center">
                                    @if(function_exists('route') && Route::has('epc.show'))
                                        <a
                                            href="{{ route('epc.show', ['lmk' => $row->lmk_key, 'r' => base64_encode($cleanReturnUrl)]) }}"
                                            class="inline-flex items-center justify-center gap-1 text-lime-700 hover:text-lime-900"
                                            title="View report"
                                            aria-label="View EPC report for {{ $row->address }}, {{ $row->postcode }}"
                                        >
                                            <i class="fa-solid fa-magnifying-glass-arrow-right fa-xl leading-none align-middle pt-3"></i>
                                            <span class="sr-only">View</span>
                                        </a>
                                    @else
                                        <a class="">N/A</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(method_exists($results, 'links'))
                <div class="mt-4">{{ $results->withQueryString()->links() }}</div>
            @endif
        @endif
    @endisset

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://cdn.jsdelivr.net/npm/proj4@2.9.1/dist/proj4.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mapEl = document.getElementById('epc-map');
        const statusEl = document.getElementById('epc-map-status');
        if (!mapEl || typeof L === 'undefined' || typeof proj4 === 'undefined') return;

        proj4.defs('EPSG:27700',
            '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.9996012717 ' +
            '+x_0=400000 +y_0=-100000 +ellps=airy +datum=OSGB36 +units=m +no_defs'
        );

        const map = L.map('epc-map', { scrollWheelZoom: true, maxZoom: 19 }).setView([52.7, -1.6], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
            maxNativeZoom: 19,
        }).addTo(map);

        const cluster = L.markerClusterGroup({
            chunkedLoading: true,
            maxClusterRadius: 40,
            spiderfyOnMaxZoom: true,
            spiderfyDistanceMultiplier: 1.6,
            removeOutsideVisibleBounds: true,
        });
        map.addLayer(cluster);

        let activeController = null;
        let loadTimer = null;

        function loadPoints() {
            if (loadTimer) window.clearTimeout(loadTimer);
            loadTimer = window.setTimeout(function () {
                const zoom = map.getZoom();
                const bounds = map.getBounds();
                const sw = bounds.getSouthWest();
                const ne = bounds.getNorthEast();

                const swOs = proj4('EPSG:4326', 'EPSG:27700', [sw.lng, sw.lat]);
                const neOs = proj4('EPSG:4326', 'EPSG:27700', [ne.lng, ne.lat]);

                const eMin = Math.min(swOs[0], neOs[0]);
                const eMax = Math.max(swOs[0], neOs[0]);
                const nMin = Math.min(swOs[1], neOs[1]);
                const nMax = Math.max(swOs[1], neOs[1]);

                const limit = zoom >= 16 ? 12000 : (zoom >= 14 ? 8000 : 3000);

                if (activeController) activeController.abort();
                activeController = new AbortController();

                const url = new URL(@json(route('epc.points', [], false)), window.location.origin);
                url.searchParams.set('e_min', Math.floor(eMin));
                url.searchParams.set('e_max', Math.ceil(eMax));
                url.searchParams.set('n_min', Math.floor(nMin));
                url.searchParams.set('n_max', Math.ceil(nMax));
                url.searchParams.set('zoom', String(zoom));
                url.searchParams.set('limit', String(limit));

                if (statusEl) statusEl.textContent = 'Loading EPC points…';

                fetch(url.toString(), { signal: activeController.signal })
                    .then(function (response) {
                        if (response.status === 202) {
                            return response.json().then(function (payload) {
                                if (statusEl) statusEl.textContent = payload.message || 'Zoom in to load EPC points.';
                                return null;
                            });
                        }
                        if (!response.ok) throw new Error('Map response was not ok');
                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload) return;
                        const points = Array.isArray(payload.points) ? payload.points : [];
                        cluster.clearLayers();

                        points.forEach(function (pt) {
                            if (!pt.easting || !pt.northing) return;
                            const coords = proj4('EPSG:27700', 'EPSG:4326', [pt.easting, pt.northing]);
                            const lat = coords[1];
                            const lng = coords[0];

                            const label = pt.address || 'EPC record';
                            const popup = '<div class="text-xs">' +
                                '<div class="font-semibold">' + label + '</div>' +
                                (pt.postcode ? '<div class="mt-1">' + pt.postcode + '</div>' : '') +
                                (pt.rating ? '<div class="mt-1">Rating: ' + pt.rating + '</div>' : '') +
                                (pt.lodgement_date ? '<div>Date: ' + pt.lodgement_date + '</div>' : '') +
                                (pt.url ? '<div class="mt-2"><a class="text-lime-700 hover:underline" href="' + pt.url + '">View EPC report</a></div>' : '') +
                                '</div>';

                            const marker = L.circleMarker([lat, lng], {
                                radius: 6,
                                color: '#ffffff',
                                fillColor: '#22c55e',
                                fillOpacity: 0.9,
                                weight: 2,
                            }).bindPopup(popup);

                            cluster.addLayer(marker);
                        });

                        if (statusEl) {
                            statusEl.textContent = payload.truncated
                                ? 'Showing a sample of EPC points in view. Zoom in for more detail.'
                                : 'Showing ' + points.length.toLocaleString('en-GB') + ' EPC points in view.';
                        }
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        if (statusEl) statusEl.textContent = 'EPC points could not be loaded right now.';
                    });
            }, 200);
        }

        map.on('moveend zoomend', loadPoints);
        loadPoints();
    });
</script>
@endsection
