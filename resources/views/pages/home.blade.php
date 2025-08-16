@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-8 shadow-sm">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl">
                {{ config('app.name', 'Property Insights') }}
            </h1>
            <p class="mt-3 text-base leading-7 text-gray-700">
                Independent, data‑led analysis of sales and repossessions across England &amp; Wales.
                Explore clean, well‑sourced figures with county and local‑authority breakdowns.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @php
                    $salesUrl = route('sales.home', [], false);
                @endphp

                <a href="{{ Route::has('sales.home') ? $salesUrl : url('/sales') }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                    Explore Sales
                </a>

                <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                    Repossessions
                </a>
            </div>
        </div>

        {{-- subtle background accent --}}
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-64 w-64 rounded-full bg-gradient-to-br from-lime-100 to-emerald-100 blur-2xl"></div>
    </section>

    {{-- Highlights --}}
    <section class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Scope</div>
            <div class="mt-1 text-sm text-gray-800">
                Land Registry sales (England &amp; Wales) with postcode‑level exploration and county rollups.
                <span class="block pt-1 text-gray-500">Latest data loaded: <strong>June 2025</strong></span>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Repossessions</div>
            <div class="mt-1 text-sm text-gray-800">
                Quarterly counts by local authority and county, with breakdowns by reason (type) and stage (action).
                <span class="block pt-1 text-gray-500">Latest quarter loaded: <strong>2025 Q2</strong></span>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Method</div>
            <div class="mt-1 text-sm text-gray-800">
                Clean imports, transparent transformations, and conservative aggregation.
                Where names differ, I normalise labels and prefer code‑based joins.
            </div>
        </div>
    </section>

    {{-- Explore panels --}}
    <section class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <a href="{{ Route::has('sales.home') ? route('sales.home') : url('/sales') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Sales Explorer</h2>
            <p class="mt-1 text-sm text-gray-700">
                Drill into transactions by postcode, street, or county. Yearly trends and quick summaries for context.
            </p>
            <div class="mt-4 text-sm font-medium text-lime-700 group-hover:underline">Open Sales →</div>
        </a>

        <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Repossessions Dashboard</h2>
            <p class="mt-1 text-sm text-gray-700">
                Compare counties, switch between quarterly and yearly views, and break down by reason or stage.
            </p>
            <div class="mt-4 text-sm font-medium text-lime-700 group-hover:underline">Open Repossessions →</div>
        </a>
    </section>

    {{-- Small print / provenance --}}
    <section class="mt-10 rounded-3xl border border-gray-200 bg-gray-50 p-6">
        <h3 class="text-sm font-semibold text-gray-900">Data provenance</h3>
        <p class="mt-2 text-sm leading-6 text-gray-700">
            Sales data from HM Land Registry. Repossessions derived from official court statistics at local‑authority level.
            County rollups are normalised for naming consistency. For methodology notes or feedback, see the About page.
        </p>
    </section>
</div>
@endsection