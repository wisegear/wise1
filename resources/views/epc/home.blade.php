@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">EPC Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                <span class="font-semibold">Only England &amp; Wales are currently available</span>
            </p>
            <p class="mt-1 text-sm leading-6 text-gray-700">
                Data covers the period from January 2008 to July 2025
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/epc.svg') }}" alt="EPC Dashboard" class="w-64 h-auto">
        </div>
    </section>

    {{-- Summary stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Total Certificates</p>
            <p class="text-xl font-semibold">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Latest Lodgement</p>
            <p class="text-xl font-semibold">{{ \Carbon\Carbon::parse($stats['latest_lodgement'])->format('d M Y') }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Last 30 Days</p>
            <p class="text-xl font-semibold">{{ number_format($stats['last30_count']) }}</p>
        </div>
        <div class="p-4 bg-white border rounded shadow">
            <p class="text-sm text-gray-500">Last 12 Months</p>
            <p class="text-xl font-semibold">{{ number_format($stats['last365_count']) }}</p>
        </div>
    </div>

    {{-- EPCs by Year --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">Certificates by Year</h2>
        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 text-left">Year</th>
                    <th class="border px-2 py-1 text-right">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byYear as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row->yr }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($row->cnt) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Rating Distribution --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">Current Energy Ratings</h2>
        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 text-left">Rating</th>
                    <th class="border px-2 py-1 text-right">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ratingDist as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row->rating }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($row->cnt) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Average Floor Area by Property Type --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">Average Floor Area by Property Type</h2>
        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 text-left">Property Type</th>
                    <th class="border px-2 py-1 text-right">Count</th>
                    <th class="border px-2 py-1 text-right">Avg m²</th>
                </tr>
            </thead>
            <tbody>
                @foreach($avgFloorArea as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row->property_type }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($row->cnt) }}</td>
                    <td class="border px-2 py-1 text-right">{{ $row->avg_m2 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Top Postcodes --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">Top Postcodes (last 12 months)</h2>
        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 text-left">Postcode</th>
                    <th class="border px-2 py-1 text-right">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topPostcodes as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row->postcode }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($row->cnt) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection