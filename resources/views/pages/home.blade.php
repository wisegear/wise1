@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-10">

    {{-- Hero --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="pointer-events-none absolute -left-20 -top-16 h-64 w-64 rounded-full bg-lime-100/60 blur-3xl -z-10"></div>
        <div class="max-w-5xl relative z-10">
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                Fresh, independent property data
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
                Property Research > clear, credible & fast datasets
            </h1>
            <p class="mt-3 text-md leading-7 text-zinc-500">
                Explore sales, repossessions and market signals across England &amp; Wales (some Scotland & NI). Built for clarity, speed and repeatable 
                analysis.  Best on larger screens, some tables are too wide for mobiles. Best of all? <span class="text-lime-600">All free!  No fees or subscriptions.</span>
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-600">
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                    Latest data: December 2025
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next update: 31st January 2026
                </span>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/home.jpg') }}" alt="Property Research" class="w-88 h-auto">
        </div>
    </section>

    {{-- Live Stats Section --}}
    <section class="mt-8 grid grid-cols-2 md:grid-cols-6 gap-4" x-data="{
        shown: false,
        propertyRecords: 0,
        ukAvgPrice: 0,
        ukAvgRent: 0,
        bankRate: 0,
        inflationRate: 0,
        epcCount: 0,
        animateValue(start, end, key, duration) {
            const range = end - start;
            const startTime = performance.now();
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                this[key] = Math.floor(start + range * easeOut);
                if (progress < 1) requestAnimationFrame(animate);
            };
            requestAnimationFrame(animate);
        }
    }" x-init="
        setTimeout(() => {
            shown = true;
            animateValue(0, {{ $stats['property_records'] ?? 0 }}, 'propertyRecords', 2000);
            animateValue(0, {{ $stats['uk_avg_price'] ?? 0 }}, 'ukAvgPrice', 2000);
            animateValue(0, {{ $stats['uk_avg_rent'] ?? 0 }}, 'ukAvgRent', 2000);
            animateValue(0, {{ ($stats['bank_rate'] ?? 0) * 100 }}, 'bankRate', 1500);
            animateValue(0, {{ ($stats['inflation_rate'] ?? 0) * 100 }}, 'inflationRate', 1500);
            animateValue(0, {{ $stats['epc_count'] ?? 0 }}, 'epcCount', 2000);
        }, 300);
    ">
        {{-- Property Records --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Property Records</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words" x-text="propertyRecords.toLocaleString()">0</p>
                </div>
            </div>
        </div>

        {{-- EPC Records --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500 delay-100"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">EPC Records</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words" x-text="epcCount.toLocaleString()">0</p>
                </div>
            </div>
        </div>

        {{-- UK Average Price --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500 delay-200"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">UK House Price</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words">&pound;<span x-text="ukAvgPrice.toLocaleString()">0</span></p>
                </div>
            </div>
        </div>

        {{-- UK Average Rent --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500 delay-300"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Average Rent</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words">&pound;<span x-text="ukAvgRent.toLocaleString()">0</span></p>
                </div>
            </div>
        </div>

        {{-- Bank Rate --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500 delay-400"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Interest Rate</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words"><span x-text="(bankRate / 100).toFixed(2)">0</span>%</p>
                </div>
            </div>
        </div>

        {{-- Inflation --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all duration-500 delay-500"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center min-w-0">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Inflation (CPIH)</p>
                    <p class="text-base sm:text-base font-bold text-zinc-900 leading-tight tracking-tight tabular-nums break-words"><span x-text="(inflationRate / 100).toFixed(2)">0</span>%</p>
                </div>
            </div>
        </div>

    </section>

    {{-- Property Stress Index --}}
    <div class="mt-8">
        @include('partials.stress-score-panel', ['totalStress' => $totalStress ?? null, 'isSticky' => false, 'showDashboardLink' => true])
    </div>

    {{-- Explore panels --}}
    <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <a href="{{ Route::has('property.home') ? route('property.home') : url('/property') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Property Sales</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Drill into transactions by postcode, street or any area.  Now you can browse properties in a map. Yearly trends &amp; quick summaries.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Property Dashboard
            </div>
        </a>

        <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Repossessions Dashboard</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">An overview of Claims made across England & Wales, see who is making claims and all of the stages.  Search individual local authorities.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Repossessions
            </div>
        </a>

        <a href="{{ Route::has('mortgages.home') ? route('mortgages.home') : url('/approvals') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Mortgage Approvals</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M6.75 2.994v2.25m10.5-2.25v2.25m-14.252 13.5V7.491a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v11.251m-18 0a2.25 2.25 0 0 0 2.25 2.25h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-6.75-6h2.25m-9 2.25h4.5m.002-2.25h.005v.006H12v-.006Zm-.001 4.5h.006v.006h-.006v-.005Zm-2.25.001h.005v.006H9.75v-.006Zm-2.25 0h.005v.005h-.006v-.005Zm6.75-2.247h.005v.005h-.005v-.005Zm0 2.247h.006v.006h-.006v-.006Zm2.25-2.248h.006V15H16.5v-.005Z" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Monthly BoE approvals for house purchases, remortgaging and other secured lending.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Mortgage Approvals
            </div>
        </a>

        <a href="{{ Route::has('epc.home') ? route('epc.home') : url('/epc') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Energy Performance Certificates - EPC</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">EPC report details for England, Wales and Scotland.  Dashboard contains some information not available from the Land Registry</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open EPC Dashboard
            </div>
        </a>

        <a href="{{ Route::has('hpi.home') ? route('hpi.home') : url('/hpi') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">House Price Index - HPI</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">House price index as UK and individual Nation dating back to 1968 for all nations.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open HPI Dashboard
            </div>
        </a>

        <a href="{{ Route::has('deprivation.index') ? route('deprivation.index') : url('/deprivation') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Deprivation Indexes</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Explore the Index of Multiple Deprivation at LSOA level. Search by postcode and see domain breakdowns.  Scotland, England, Wales and Northern Ireland.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Deprivation Dashboard
            </div>
        </a>

    </section>

    {{-- Blog Section --}}
    @if($posts->count() > 0)
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-zinc-900">Latest Insights</h2>
                <p class="text-sm text-zinc-500 mt-1">Analysis and commentary on the UK property market</p>
            </div>
            <a href="{{ url('/blog') }}" class="hidden sm:inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 hover:border-zinc-300">
                View all posts
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- Other posts (smaller, stacked) --}}
            <div class="lg:col-span-12 grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($posts as $post)
                    <a href="/blog/{{ $post->slug }}"
                       class="group flex gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md hover:border-zinc-300">
                        <div class="w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden bg-zinc-100">
                            <img src="{{ '/assets/images/uploads/' . 'small_' . $post->original_image }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 alt="{{ $post->title }}">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-zinc-500 mb-1">
                                {{ $post->date->format('M j, Y') }}
                            </div>
                            <h3 class="font-semibold text-zinc-900 line-clamp-2 group-hover:text-lime-700 transition-colors">
                                {{ $post->title }}
                            </h3>
                            <p class="mt-1 text-sm text-zinc-500 line-clamp-2 sm:block lg:block">
                                {{ $post->summary }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Mobile "View all" link --}}
        <div class="mt-6 sm:hidden">
            <a href="{{ url('/blog') }}" class="block w-full text-center rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50">
                View all posts
            </a>
        </div>
    </section>
    @endif

</div>
@endsection
