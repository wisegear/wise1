@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

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
                    Latest data<datalist></datalist>: <class="">September 2025
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next update: <class="">30th November 2025
                </span>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/home.svg') }}" alt="Property Research" class="w-64 h-auto">
        </div>
    </section>

    {{-- Explore panels --}}
    <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <a href="{{ Route::has('property.search') ? route('property.search') : url('/property/search') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Property Sales</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Drill into transactions by postcode, street or county. Yearly trends &amp; quick summaries.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Property Dashboard
            </div>
        </a>

        <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Repossessions Dashboard</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Compare counties, switch between quarterly &amp; yearly, break down by reason or stage.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Repossessions
            </div>
        </a>

        <a href="{{ Route::has('mortgages.home') ? route('mortgages.home') : url('/approvals') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Mortgage Approvals</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Monthly BoE approvals for house purchases, remortgaging and other secured lending.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Mortgage Approvals
            </div>
        </a>

        <a href="{{ Route::has('epc.home') ? route('epc.home') : url('/epc') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Energy Performance Certificates - EPC</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Basic EPC report details for England, Wales and now Scotland.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open EPC Dashboard
            </div>
        </a>

        <a href="{{ Route::has('hpi.home') ? route('hpi.home') : url('/hpi') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">House Price Index - HPI</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">House price index as UK and individual Nation dating back to 1968 for all nations.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open HPI Dashboard
            </div>
        </a>

        <a href="{{ Route::has('deprivation.index') ? route('deprivation.index') : url('/deprivation') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Deprivation Indexes</h2>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Explore the Index of Multiple Deprivation at LSOA level. Search by postcode and see domain breakdowns.  Scotland, England and Wales.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 group-hover:underline">Open Deprivation Dashboard
            </div>
        </a>

    </section>

    {{-- Economic Dashboard Overview --}}
    <section class="mt-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-zinc-900 mb-2">Economic Indicators Dashboard</h2>
            <p class="text-sm text-zinc-700 leading-6">
                Our new Economic Indicators Dashboard brings together seven key measures that influence the property market:
                interest rates, inflation, wage growth, unemployment, mortgage approvals, repossessions and the UK House Price Index.
                Each indicator is tracked quarterly using a consistent early‑warning system to show when conditions are improving,
                stable or deteriorating.
            </p>
            <p class="mt-3 text-sm text-zinc-700 leading-6">
                One difficult quarter may simply signal noise, but consecutive adverse quarters create meaningful trends. The dashboard
                colours and stress score help highlight when the combined picture is moving into riskier territory. Explore the summary
                or dive into each indicator for deeper charts and context.
            </p>
            <a href="{{ route('economic.dashboard') }}"
               class="mt-4 inline-flex items-center text-sm font-medium text-lime-700 hover:underline">
                Open Economic Dashboard
            </a>
        </div>
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
                        {{ $post->date->DiffForHumans() }}
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