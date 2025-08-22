@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Property Dashboard</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                The dashboard allows you to create search patters by address variables.  Be aware though, with 35.3m records these searches can be slow.  Caching is in use but there is only
                so much that can be cached to speed up the searches.
            </p>
            <div class="mt-6">
                <a href="/property/search" class="border text-sm p-2 rounded-sm bg-zinc-50">Property Search</a>
            </div>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gradient-to-br from-lime-100 to-lime-400 blur-2xl"></div>
    </section>

@endsection