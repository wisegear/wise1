@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 md:py-12">
    <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-gray-900">About this site</h1>
        <p class="mt-3 text-base leading-7 text-gray-700">
            I’m Lee Wisener, Glasgow-based mortgage veteran, data enthusiast, and repeat offender in the art of overthinking.
            This site is my tidy corner for <span class="font-medium">clean, independent, data‑led</span> insight on UK property:
            sales from HM Land Registry, repossession activity from official court statistics, and Bank of England interest rates &amp; approvals, presented without spin.
        </p>
    </header>

    {{-- What this is --}}
    <section class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">What you’ll find</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li><span class="font-medium">Sales Explorer</span> — search by postcode, street, or county; roll up to yearly trends.</li>
            <li><span class="font-medium">Repossessions Dashboard</span> — counts by local authority &amp; county by reason (type) and stage (action).</li>
            <li><span class="font-medium">Interest Rates</span> — Bank Rate history with latest change, streaks, and a clean monthly chart.</li>
            <li><span class="font-medium">Mortgage Approvals</span> — BoE approvals for house purchase, remortgaging, and other secured lending.</li>
            <li><span class="font-medium">Simple visuals</span> — clear tables and charts, no fireworks, no fiddly legends.</li>
        </ul>
    </section>

    {{-- Sources --}}
    <section class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Data sources</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li><span class="font-medium">HM Land Registry (England &amp; Wales)</span> — price paid / sales records.</li>
            <li><span class="font-medium">Official court stats</span> — possession claims, orders, warrants and repossessions at local‑authority level.</li>
            <li><span class="font-medium">Bank of England</span> — Bank Rate history and mortgage approvals (lending secured on dwellings).</li>
        </ul>
        <p class="mt-3 text-sm text-gray-600">Scotland uses a separate system and is handled differently; I'll find data on Scotland soon.</p>
    </section>

    {{-- Method --}}
    <section class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Method, in short</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li><span class="font-medium">Normalise names</span> — county labels vary (“UA”, “City of …”). I clean the labels; I prefer official codes.</li>
            <li><span class="font-medium">Aggregate sensibly</span> — quarterly data can be rolled up to yearly; 2025 is clearly marked as year‑to‑date.</li>
            <li><span class="font-medium">Minimal transformation</span> — keep imports faithful; document any adjustments.</li>
            <li><span class="font-medium">Reproducible</span> — built with Laravel 11 + MySQL and Tailwind; queries are plain and auditable.</li>
        </ul>
    </section>

    {{-- Caveats --}}
    <section class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Caveats &amp; common sense</h2>
        <ul class="mt-3 list-inside list-disc text-sm leading-6 text-gray-800">
            <li>Names aren’t keys. Where things must join, I use codes or a small alias map.</li>
            <li>Data lags exist. Official releases aren’t real‑time, and YTD isn’t a full year.</li>
            <li class="text-red-500">This is analysis, not advice. Please don’t base a life decision on a single chart.</li>
        </ul>
    </section>

    {{-- Contact --}}
    <section class="mb-10 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Contact</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Feedback, corrections, or heckling welcome. The tone here is deliberately plain and occasionally dry,
            if you prefer more personality, you’ll probably enjoy my blog.
        </p>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <a href="mailto:lee@wisener.net" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-800 hover:bg-gray-50">Email</a>
            <a href="https://wisener.net/about" target="_blank" rel="noopener" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-800 hover:bg-gray-50">About me (wisener.net)</a>
        </div>
    </section>

    {{-- Thanks --}}
    <section class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Thanks</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Special thanks to Bob for his help testing the site and suggesting improvements.
        </p>
    </section>

    <p class="flex justify-center text-xs leading-6 text-gray-500">
        Built with Laravel 11, mySQL and Tailwind CSS. Hosted on <a href="https://www.hetzner.com/cloud" target="_blank" rel="noopener">Hetzner Cloud</a>. No dark patterns, minimal cookies, and absolutely no “accept all” confetti.
    </p>
</div>
@endsection