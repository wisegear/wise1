@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-10">

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center mb-10">
        <div class="max-w-5xl relative z-10">
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
                About Property Research
            </h1>
            <p class="mt-3 text-md leading-7 text-zinc-500">
                I’m Lee Wisener, Glasgow-based mortgage veteran, data enthusiast, and repeat offender in the art of overthinking. I work for a bank, but this site is entirely a personal project and 
                not connected with my employer. It’s my tidy corner for clean, independent, data-led insight on UK property: sales from HM Land Registry, repossession activity from official court 
                statistics, and Bank of England interest rates & approvals, presented without spin.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('/assets/images/site/about.svg') }}" alt="About Property Research" class="w-64 h-auto">
        </div>
    </section>

    {{-- What this is --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">What you’ll find</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            On this site you’ll find clear and concise analysis of the UK housing market, built entirely around official data. You can explore sales trends through the Sales Explorer, track 
            repossession activity across counties and local authorities, and follow interest rate movements and mortgage approvals using Bank of England data. Every chart, table, and dashboard 
            is designed to be simple, honest, and data-led — no gimmicks or spin, just clean insight into how the property market moves.
        </p>
    </section>

    {{-- Sources --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Data sources</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li><span class="font-medium">HM Land Registry (England &amp; Wales)</span> — price paid / sales records.</li>
            <li><span class="font-medium">Official court stats</span> — possession claims, orders, warrants and repossessions at local‑authority level.</li>
            <li><span class="font-medium">Bank of England</span> — Bank Rate history and mortgage approvals (lending secured on dwellings).</li>
            <li><span class="font-medium">Gov.uk</span> — For various datasets.</li>
        </ul>
        <p class="mt-3 text-sm text-gray-600">Scotland uses a separate system and is handled differently; I'll find data on Scotland soon.</p>
    </section>

    {{-- Method --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Method, in short</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li><span class="font-medium">Normalise names</span> — county labels vary (“UA”, “City of …”). I clean the labels; I prefer official codes.</li>
            <li><span class="font-medium">Aggregate sensibly</span> — quarterly data can be rolled up to yearly; 2025 is clearly marked as year‑to‑date.</li>
            <li><span class="font-medium">Minimal transformation</span> — keep imports faithful; document any adjustments.</li>
            <li><span class="font-medium">Reproducible</span> — built with Laravel 12 + MySQL and Tailwind; queries are plain and auditable.</li>
        </ul>
    </section>

    {{-- Caveats --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Caveats &amp; common sense</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li>Names aren’t keys. Where things must join, I use codes or a small alias map.</li>
            <li>Data lags exist. Official releases aren’t real‑time, and YTD isn’t a full year.</li>
            <li class="text-rose-700">This is analysis, not advice. Please don’t base a life decision on a single chart.</li>
        </ul>
    </section>

    {{-- Contact --}}
    <section class="mb-10 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Contact</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Feedback, corrections, or heckling welcome. The tone here is deliberately plain and occasionally dry,
            if you prefer more personality check out my <a href="/blog"><span class="text-lime-600 hover:text-lime-500 hover:underline">blog</span></a>.  Or, you can email me on lee@wisener.net
        </p>
    </section>

    {{-- Thanks --}}
    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Thanks</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">Special thanks to Bob for his help testing the site and suggesting improvements.</p>
        <p class="mt-3 text-sm leading-6 text-gray-800">A big thanks to Jeffrey for pointing out all my query errors! And also to, Cache, Cache more and Cache again!</p>
    </section>

</div>
@endsection