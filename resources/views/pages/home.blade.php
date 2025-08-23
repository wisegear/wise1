@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-8 shadow-sm">
        <div class="max-w-3xl">
            <h1 class="text-xl font-semibold tracking-tight text-gray-900 md:text-2xl">
                Property Research
            </h1>
            <p class="mt-3 text-base leading-7 text-gray-700">
                Independent, data‑led analysis of sales and repossessions across England &amp; Wales.
                Explore clean, well‑sourced figures with county and local‑authority breakdowns.
            </p>

        </div>

        {{-- subtle background accent --}}
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-64 w-64 rounded-full md:bg-gradient-to-br md:from-lime-100 md:to-emerald-100 md:blur-2xl"></div>
    </section>

    {{-- Usage note: desktop-first layout --}}
    <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-amber-900">Best viewed on larger screens</h2>
        <p class="mt-1 text-sm text-amber-800">
            This is a data‑heavy site with large tables and dashboards. It is designed primarily for desktop or large‑tablet use and is not fully optimised for small screens. On phones, some pages may require horizontal scrolling. For the best experience, use a wider display.
        </p>
    </section>

    {{-- Highlights --}}
    <section class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Sales (HM Land Registry) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Sales (HM Land Registry)</div>
            <div class="mt-1 text-sm text-gray-800">
                Residential transactions recorded by HM Land Registry (England &amp; Wales), with postcode, street and county roll‑ups.
                <span class="block pt-1 text-gray-500">Latest month loaded: <strong>June 2025</strong></span>
            </div>
        </div>

        {{-- Repossessions (MoJ Court Statistics) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Repossessions (MoJ court stats)</div>
            <div class="mt-1 text-sm text-gray-800">
                Claims and orders for possession by local authority and county, with breakdowns by reason (type) and process stage (action).
                <span class="block pt-1 text-gray-500">Latest quarter loaded: <strong>2025 Q2</strong></span>
            </div>
        </div>

        {{-- Methodology & coverage --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500">Methodology &amp; coverage</div>
            <div class="mt-1 text-sm text-gray-800">
                Coverage: England &amp; Wales. Sources: HM Land Registry (sales), MoJ court statistics (repossessions), Bank of England (interest rates &amp; approvals).
                Names are standardised and joins prefer official codes (e.g. GSS) for consistency.
            </div>
        </div>
    </section>

    {{-- Explore panels --}}
    <section class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-4">
        <a href="{{ Route::has('property.search') ? route('property.search') : url('/property/search') }}"
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

        <a href="{{ Route::has('interest.home') 
                    ? route('interest.home') 
                    : (Route::has('rates.index') 
                        ? route('rates.index') 
                        : url('/interest')) }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Interest Rates</h2>
            <p class="mt-1 text-sm text-gray-700">
                Track the Bank of England Bank Rate over time with monthly updates and a clean historical view.
            </p>
            <div class="mt-4 text-sm font-medium text-lime-700 group-hover:underline">Open Interest Rates →</div>
        </a>

        <a href="{{ Route::has('mortgages.home') ? route('mortgages.home') : url('/mortgages') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Mortgage Approvals</h2>
            <p class="mt-1 text-sm text-gray-700">
                Monthly Bank of England approvals for house purchase, remortgaging, and other secured lending.
            </p>
            <div class="mt-4 text-sm font-medium text-lime-700 group-hover:underline">Open Approvals →</div>
        </a>
    </section>

    {{-- Small print / provenance --}}
    <section class="mt-10 rounded-3xl border border-gray-200 bg-gray-50 p-6">
        <h3 class="text-sm font-semibold text-gray-900">Data provenance</h3>
        <p class="mt-2 text-sm leading-6 text-gray-700">
            Sales data from HM Land Registry. Repossessions derived from official court statistics at local‑authority level.
            County rollups are normalised for naming consistency. Interest rates and mortgage approvals from BoE. For methodology notes or feedback, see the about page.
        </p>
    </section>

    <!-- Blog posts -->
    <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-4">
        @foreach ($posts as $post)
            <a href="/blog/{{ $post->slug }}"
               class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <img src="{{ '/assets/images/uploads/' . 'small_' . $post->original_image }}"
                     class="rounded-xl w-full h-40 object-cover border border-zinc-200"
                     alt="blog-post-picture">
                <h2 class="mt-4 text-lg font-semibold text-gray-900 group-hover:text-lime-700">
                    {{ $post->title }}
                </h2>
                <div class="mt-1 text-xs text-gray-500">
                    {{ $post->date instanceof \Illuminate\Support\Carbon ? $post->date->format('d M Y') : $post->date }}
                </div>
            </a>
        @endforeach
    </div>

</div>
@endsection