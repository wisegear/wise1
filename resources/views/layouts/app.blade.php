<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'PropertyResearch') }}</title>
    @vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-zinc-50">
    <div class="min-h-screen flex flex-col">
        {{-- Nav --}}
        <nav class="bg-white border-b border-zinc-200 p-4">
            <div class="max-w-7xl mx-auto flex items-center">
                <a href="{{ url('/') }}" class="font-semibold text-lg">PropertyResearch<span class="text-sm text-lime-600">.uk</span></a>
                <div class="flex items-center gap-2 ml-8 text-sm">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded {{ request()->is('/') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Home</a>
                    <a href="{{ url('/sales') }}" class="px-3 py-2 rounded {{ request()->is('sales') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Sales</a>
                    <a href="{{ url('/repossessions') }}" class="px-3 py-2 rounded {{ request()->is('repossessions') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Repossessions</a>
                    <a href="{{ url('/interest-rates') }}" class="px-3 py-2 rounded {{ request()->is('interest-rates') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Interest Rates</a>
                    <a href="{{ url('/about') }}" class="px-3 py-2 rounded {{ request()->is('about') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">About</a>
                </div>
            </div>
        </nav>

        {{-- Content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-zinc-200 p-4 text-center text-sm text-zinc-500">
             <p>&copy; Lee Wisener, PropertyResearch.uk</p>
        </footer>
    </div>
</body>
</html>
