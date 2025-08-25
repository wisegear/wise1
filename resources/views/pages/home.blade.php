@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm">
        <div class="max-w-5xl relative z-10">
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                Fresh, independent property data
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
                Property Research — clear, credible & fast datasets
            </h1>
            <p class="mt-3 text-base leading-7 text-zinc-700 md:text-lg">
                Explore sales, repossessions and market signals across England &amp; Wales. Built for clarity, speed and repeatable analysis.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-600">
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Latest data<datalist></datalist>: <strong class="font-semibold">June 2025</strong>
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white/70 px-3 py-1">
                    <span class="h-2 w-2 rounded-lg bg-zinc-400"></span>
                    Next data update: <strong class="font-semibold">29th August 2025 subject to public agency release</strong>
                </span>
            </div>
        </div>

    </section>

    {{-- Quick tip --}}
    <section class="mt-6 rounded-lg border border-zinc-300 bg-orange-50 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-orange-400"></i>
            <p class="text-sm text-zinc-900">
                Best on larger screens — some tables are wide. Everything still works on mobile.
            </p>
        </div>
    </section>

    {{-- Explore panels --}}
    <section class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <a href="{{ Route::has('property.search') ? route('property.search') : url('/property/search') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Property Sales</h2>
                <span class="rounded-lg bg-lime-300 px-2 py-1 text-xs text-zinc-700">Live</span>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Drill into transactions by postcode, street or county. Yearly trends &amp; quick summaries.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-cyan-700 group-hover:underline">Open Property Dashboard
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('repossessions.index') ? route('repossessions.index') : url('/repossessions') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Repossessions Dashboard</h2>
                <span class="rounded-lg bg-orange-200 px-2 py-1 text-xs text-zinc-700">Beta</span>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Compare counties, switch between quarterly &amp; yearly, break down by reason or stage.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-cyan-700 group-hover:underline">Open Repossessions
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('interest.home') ? route('interest.home') : url('/interest-rates') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Interest Rates</h2>
                <span class="rounded-full bg-lime-300 px-2 py-1 text-xs text-zinc-700">Live</span>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Track the Bank Rate with nonthly updates and a clean historical view.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-cyan-700 group-hover:underline">Open Interest Rates
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>

        <a href="{{ Route::has('mortgages.home') ? route('mortgages.home') : url('/approvals') }}"
           class="group block rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900">Mortgage Approvals</h2>
                <span class="rounded-lg bg-lime-300 px-2 py-1 text-xs text-zinc-700">Live</span>
            </div>
            <p class="mt-2 text-sm text-zinc-700">Monthly BoE approvals for house purchases, remortgaging and other secured lending.</p>
            <div class="mt-4 inline-flex items-center text-sm font-medium text-cyan-700 group-hover:underline">Open Interest Rates
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-4 w-4"><path d="M13 5l7 7-7 7v-4H4v-6h9V5z"/></svg>
            </div>
        </a>
    </section>

    {{-- Small print / provenance --}}
    <section class="mt-10 rounded-lg border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">Data provenance</h3>
        <p class="mt-2 text-sm leading-6 text-zinc-700">
            Sales from HM Land Registry; repossessions from official MoJ statistics; interest rates &amp; approvals from the Bank of England. Names are standardised and official area codes are used for reliable joins.
        </p>
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