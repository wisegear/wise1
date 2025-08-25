@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-8 shadow-sm">
        <div class="max-w-3xl relative z-10">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/70 px-3 py-1 text-xs text-emerald-700 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Fresh, independent property data
            </div>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl">
                Property Research — clear, credible & fast
            </h1>
            <p class="mt-3 text-base leading-7 text-gray-700 md:text-lg">
                Explore sales, repossessions and market signals across England &amp; Wales. Built for clarity, speed and repeatable analysis.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                    Latest <datalist></datalist>: <strong class="font-semibold">June 2025</strong>
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                    Next data update: <strong class="font-semibold">29th August 2025</strong>
                </span>
            </div>
        </div>

        {{-- decorative accents --}}
        <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-emerald-100 blur-2xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -left-10 h-72 w-72 rounded-full bg-lime-100 blur-2xl"></div>
    </section>

    {{-- Quick tip --}}
    <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/70 p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mt-0.5 h-5 w-5 text-amber-500"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 14h-2v-6h2v6zm0 4h-2v-2h2v2z"/></svg>
            <p class="text-sm text-emerald-900">
                Best on larger screens — some tables are wide. Everything still works on mobile.
            </p>
        </div>
    </section>

    {{-- Highlights --}}
    <section class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                Sales (HM Land Registry)
            </div>
            <div class="mt-2 text-sm text-gray-800">
                Clean, de-duplicated transactions with postcode, street and county roll‑ups.
                <span class="block pt-1 text-gray-500">Latest month: <strong>June 2025</strong></span>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                Repossessions (MoJ)
            </div>
            <div class="mt-2 text-sm text-gray-800">
                Claims &amp; orders by local authority, with reasons &amp; process stages.
                <span class="block pt-1 text-gray-500">Latest quarter: <strong>2025 Q2</strong></span>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                Methodology &amp; coverage
            </div>
            <div class="mt-2 text-sm text-gray-800">
                England &amp; Wales coverage. Sources: HMLR, MoJ, Bank of England. Names &amp; codes consistently normalised.
            </div>
        </div>
    </section>

    {{-- Explore panels --}}
    <section class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-4">
        <a href="{{ Route::has('property.search') ? route('property.search') : url('/property/search') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Sales Explorer</h2>
                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">Live</span>
            </div>
            <p class="mt-2 text-sm text-gray-700">Drill into transactions by postcode, street or county. Yearly trends &amp; quick summaries.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-emerald-700 group-hover:underline">Open Sales
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Repossessions Dashboard</h2>
                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">Beta</span>
            </div>
            <p class="mt-2 text-sm text-gray-700">Compare counties, switch between quarterly &amp; yearly, break down by reason or stage.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-emerald-700 group-hover:underline">Open Repossessions
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('interest.home') 
                    ? route('interest.home') 
                    : (Route::has('rates.index') 
                        ? route('rates.index') 
                        : url('/interest')) }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Interest Rates</h2>
            <p class="mt-2 text-sm text-gray-700">Track the Bank Rate with monthly updates and a clean historical view.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-emerald-700 group-hover:underline">Open Interest Rates
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('mortgages.home') ? route('mortgages.home') : url('/mortgages') }}"
           class="group block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <h2 class="text-lg font-semibold text-gray-900">Mortgage Approvals</h2>
            <p class="mt-2 text-sm text-gray-700">Monthly BoE approvals for house purchase, remortgaging and other secured lending.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-emerald-700 group-hover:underline">Open Approvals
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>
    </section>

    {{-- Small print / provenance --}}
    <section class="mt-10 rounded-3xl border border-gray-200 bg-gray-50 p-6">
        <h3 class="text-sm font-semibold text-gray-900">Data provenance</h3>
        <p class="mt-2 text-sm leading-6 text-gray-700">
            Sales from HM Land Registry; repossessions from official MoJ statistics; interest rates &amp; approvals from the Bank of England. Names are standardised and official area codes are used for reliable joins.
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
                <div class="mt-1 text-xs text-emerald-700">Insight</div>
                <div class="mt-1 text-xs text-gray-500">
                    {{ $post->date instanceof \Illuminate\Support\Carbon ? $post->date->format('d M Y') : $post->date }}
                </div>
            </a>
        @endforeach
    </div>

</div>
@endsection