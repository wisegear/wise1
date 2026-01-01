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

    {{-- No fees --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">No fees, no catch</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            This site is completely free to use. There are no subscriptions, paywalls, hidden features, premium tiers,
            referral links or upsells, and no plans to add them. I don’t sell data, collect payments, or gate access
            behind accounts. Everything you see here is available to everyone, in full, at no cost.
        </p>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            The project is funded entirely out of my own pocket and built because I wanted a clean, independent place
            to explore UK property data without noise or incentives. If it’s useful, great. If not, you haven’t lost
            anything except a few minutes.
        </p>
    </section>

    {{-- Sources --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Data sources</h2>
        <ul class="mt-3 list-outside list-disc pl-5 text-sm leading-6 text-gray-800">
            <li><span class="font-medium">HM Land Registry, England &amp; Wales</span> — price paid data for every residential sale back to the mid‑1990s, including price, property type, tenure and new build status.</li>
            <li><span class="font-medium">Registers of Scotland</span> — Scottish residential sales, handled separately under different licensing.</li>
            <li><span class="font-medium">ONS Postcode Directory</span> — quarterly postcode lookup that links every UK postcode to local authority, LSOA or Data Zone, region and coordinates. Used to attach geography to sales, EPCs and deprivation.</li>
            <li><span class="font-medium">Indices of Multiple Deprivation</span> — IMD for England, WIMD for Wales, SIMD for Scotland, NIMDM for Northern Ireland. Used for deprivation maps and top / bottom rankings.</li>
            <li><span class="font-medium">EPC certificates</span> — Domestic Energy Performance Certificates for England, Wales and Scotland. Used to understand efficiency, heating type and build age by area and property style.</li>
            <li><span class="font-medium">Official court statistics</span> — possession claims, orders, warrants and repossessions at local authority and county court circuit level.</li>
            <li><span class="font-medium">Bank of England</span> — Bank Rate history, mortgage approvals and secured lending volumes.</li>
            <li><span class="font-medium">ONS and central government releases</span> — household counts, population, housing supply and completions, affordability ratios and planning data.</li>
        </ul>
        <p class="mt-3 text-sm text-gray-600">Northern Ireland data appears in deprivation and map views, but postcode level search is limited by licensing, so I cannot publish a full postcode lookup for Northern Ireland.</p>
    </section>

    {{-- Method --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">Method, in short</h2>
        <ul class="mt-3 list-outside list-disc pl-5 text-sm leading-6 text-gray-800">
            <li><span class="font-medium">Use codes, not guesses</span> — joins are done on official identifiers like local authority codes, LSOA codes, Data Zone codes and EPC property IDs, not just names like “Bristol”. Where names differ, I keep a small alias map and present the cleaned label on screen.</li>
            <li><span class="font-medium">Keep it faithful</span> — I ingest the raw government CSVs and import them almost as‑is into MySQL. The site shows the real series, not a model or forecast.</li>
            <li><span class="font-medium">Aggregate clearly</span> — monthly data can be rolled up to quarterly or yearly where that makes trends easier to read. Anything that is year‑to‑date is marked as year‑to‑date.</li>
            <li><span class="font-medium">Warm and cache heavy work</span> — large queries, for example Land Registry history by postcode district or deprivation lookups, get pre‑computed and cached so pages load fast even on big datasets.</li>
            <li><span class="font-medium">Reproducible</span> — built with Laravel 12, MySQL and Tailwind. Queries are plain and auditable and can be re‑run locally.</li>
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

    {{-- Liceenses --}}
    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900">License Notes</h2>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Unless explicitly stated below, all data used on this site is published under the Open Government Licence v3.0.
        </p>
        <p class="mt-3 text-sm leading-6 text-gray-800">Contains HM Land Registry data © Crown copyright and database right 2020. Licensed under the Open Government Licence v3.0.</p>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Energy Performance Certificate (EPC) data for England and Wales is sourced from the official Energy Performance of Buildings Register. EPC information is displayed on a property‑by‑property, user‑requested basis and reflects the same records that are publicly available via the official register. This site does not publish or redistribute bulk address‑level EPC datasets. EPC records may be removed or unavailable where they are no longer publicly disclosed on the register.
        </p>
        <p class="mt-3 text-sm leading-6 text-gray-800">
            Scottish EPC data is sourced from the Scottish Government via statistics.gov.scot. Non‑address data fields within the Scottish Domestic Energy Performance Certificates dataset (all fields other than address and postcode information) are licensed under the Open Government Licence v3.0. Scottish EPC information on this site is displayed on a property‑by‑property, user‑requested basis and reflects records available via the official Scottish EPC Register.
        </p>
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
