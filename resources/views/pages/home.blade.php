@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-10">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-5xl relative z-10">
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                Fresh, independent property data
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
                Property Research — clear, credible & fast datasets
            </h1>
            <p class="mt-3 text-md leading-7 text-zinc-500">
                Explore sales, repossessions and market signals across England &amp; Wales (some Scotland & NI). Built for clarity, speed and repeatable analysis.  Best on larger screens, some tables are too wide for mobiles. 
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-600">
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
                    Latest data<datalist></datalist>: <class="">November 2025
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next update: <class="">31st December 2025
                </span>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/home.svg') }}" alt="Property Research" class="w-64 h-auto">
        </div>
    </section>

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
            <p class="mt-2 text-sm text-zinc-700">Drill into transactions by postcode, street or any area. Yearly trends &amp; quick summaries.</p>
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

    {{-- Economic Dashboard Overview --}}
    <section class="mt-6">
        <a href="{{ route('economic.dashboard') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xl font-semibold text-zinc-900">Economic Indicators Dashboard</h2>
                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.5"
                     stroke="currentColor"
                     class="h-6 w-6 text-zinc-500 group-hover:text-lime-600 transition">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                </svg>
            </div>
            <p class="text-sm text-zinc-700 leading-6">
                Our new Economic Indicators Dashboard brings together eight key measures that influence the property market:
                interest rates, inflation, wage growth, unemployment, mortgage approvals, repossessions and the UK House Price Index.
                Each indicator is tracked quarterly using a consistent early‑warning system to show when conditions are improving,
                stable or deteriorating.
            </p>
            <p class="mt-3 text-sm text-zinc-700 leading-6">
                One difficult quarter may simply signal noise, but consecutive adverse quarters create meaningful trends. The dashboard
                colours and stress score help highlight when the combined picture is moving into riskier territory. Explore the summary
                or dive into each indicator for deeper charts and context.
            </p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">
                Open Economic Dashboard
            </div>
        </a>
    </section>

    <!-- Blog posts -->
    <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-4">
        @foreach ($posts as $post)
            <a href="/blog/{{ $post->slug }}"
               class="group block rounded-lg border border-zinc-200 bg-white p-2 shadow-sm transition hover:shadow-md">
                <img src="{{ '/assets/images/uploads/' . 'small_' . $post->original_image }}"
                     class="rounded-lg w-lg h-40 object-cover border border-zinc-200"
                     alt="blog-post-picture">
                <div class="p-2">
                    <h2 class="mt-2 font-semibold text-zinc-900">
                        {{ $post->title }}
                    </h2>
                    <div class="mt-1 text-xs text-zinc-500">
                        {{ $post->date->startOfDay()->diffForHumans(now()->startOfDay()) }}
                    </div>
                    <div class="mt-2 text-sm">
                        {{ $post->summary }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>



</div>
@endsection