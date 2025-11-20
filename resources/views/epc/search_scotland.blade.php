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
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Search EPC records in Scotland</h1>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from 2015 to 2025 (latest available)
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/epc.svg') }}" alt="EPC Search Scotland" class="w-64 h-auto">
        </div>
    </section>

    @isset($byYear)
        <section class="mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Certificates issued by year --}}
                <div class="rounded-lg border border-gray-200 bg-white/80 p-4 md:p-6 shadow-sm">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-1">Certificates issued by year</h2>
                    <p class="text-xs md:text-sm text-gray-600 mb-3">
                        Number of EPC certificates lodged each year in Scotland.
                    </p>
                    <div class="relative h-64">
                        <canvas id="epcCertificatesByYearScotland"></canvas>
                    </div>
                </div>

                {{-- Tenure by year --}}
                @isset($tenureByYear)
                    <div class="rounded-lg border border-gray-200 bg-white/80 p-4 md:p-6 shadow-sm">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-1">Tenure by year</h2>
                        <p class="text-xs md:text-sm text-gray-600 mb-3">
                            Split of EPCs by tenure (owner-occupied, private rented, social rented) each year.
                        </p>
                        <div class="relative h-64">
                            <canvas id="epcTenureByYearScotland"></canvas>
                        </div>
                    </div>
                @endisset
            </div>
        </section>
    @endisset

    {{-- Search form --}}
    <div class="flex justify-center">
        <form method="GET" action="{{ route('epc.search_scotland') }}" class="mb-10 w-full lg:w-1/2 mx-auto p-6 border bg-white/80 rounded">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="postcode" class="block text-sm font-medium mb-1">Enter a postcode below to view EPC certificates.</label>
                    <input
                        id="postcode"
                        name="postcode"
                        type="text"
                        value="{{ old('postcode', request('postcode')) }}"
                        placeholder="e.g. G12 8QQ or EH1 3QR"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-lime-500 bg-white"
                        required
                    />
                    @error('postcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="bg-zinc-700 hover:bg-zinc-500 text-white font-medium px-4 py-2 rounded-md transition cursor-pointer">
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
                                <td class="px-3 py-2 align-top border-b">{{ optional(\Carbon\Carbon::parse($row->lodgement_date))->format('d M Y') }}</td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->address }}</td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->postcode }}</td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->current_energy_rating }}</td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->potential_energy_rating }}</td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->property_type }}</td>
                                <td class="px-3 py-2 align-top border-b text-right">
                                    {{ $row->total_floor_area ? number_format($row->total_floor_area * 10.7639, 0) : '' }}
                                </td>
                                <td class="px-3 py-2 align-top border-b">{{ $row->local_authority_label }}</td>
                                <td class="px-3 py-2 align-top border-b text-center">
                                    <a
                                        href="{{ route('epc.show', ['lmk' => $row->lmk_key, 'r' => base64_encode($cleanReturnUrl)]) }}"
                                        class="inline-flex items-center justify-center gap-1 text-lime-700 hover:text-lime-900"
                                        title="View report"
                                        aria-label="View EPC report for {{ $row->address }}, {{ $row->postcode }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-8 w-8 border p-2 bg-zinc-800 text-white" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                                        </svg>
                                        <span class="sr-only">View</span>
                                    </a>
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

    @isset($byYear)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Certificates issued by year (Scotland)
                const byYearRawScot = @json($byYear ?? []);
                if (byYearRawScot.length && document.getElementById('epcCertificatesByYearScotland')) {
                    const labelsScot = byYearRawScot.map(r => r.yr);
                    const dataScot = byYearRawScot.map(r => r.cnt);

                    const ctxCertScot = document.getElementById('epcCertificatesByYearScotland').getContext('2d');
                    new Chart(ctxCertScot, {
                        type: 'bar',
                        data: {
                            labels: labelsScot,
                            datasets: [{
                                label: 'Certificates',
                                data: dataScot,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return value.toLocaleString();
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                }

                // Tenure by year (Scotland)
                @isset($tenureByYear)
                    const tenureRawScot = @json($tenureByYear ?? []);
                    if (tenureRawScot.length && document.getElementById('epcTenureByYearScotland')) {
                        const tenureCategories = ['owner-occupied', 'rented (private)', 'rented (social)'];
                        const tenureYears = [...new Set(tenureRawScot.map(r => r.yr))].sort();

                        const tenureDatasets = tenureCategories.map((cat, idx) => {
                            return {
                                label: cat,
                                data: tenureYears.map(y => {
                                    const match = tenureRawScot.find(r => r.yr === y && r.tenure === cat);
                                    return match ? match.cnt : 0;
                                }),
                                borderWidth: 1
                            };
                        });

                        const ctxTenureScot = document.getElementById('epcTenureByYearScotland').getContext('2d');
                        new Chart(ctxTenureScot, {
                            type: 'line',
                            data: {
                                labels: tenureYears,
                                datasets: tenureDatasets
                            },
                            options: {
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function (value) {
                                                return value.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                @endisset
            });
        </script>
    @endisset
</div>
@endsection