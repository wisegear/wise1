<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-zinc-50">
    <div class="min-h-screen flex flex-col">
        {{-- Nav --}}
        <nav class="bg-white border-b border-zinc-200 p-4">
            <div class="max-w-7xl mx-auto">
                <a href="{{ url('/') }}" class="font-semibold text-lg">{{ config('app.name') }}</a>
            </div>
        </nav>

        {{-- Content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-zinc-200 p-4 text-center text-sm text-zinc-500">
             <p>&copy; Lee Wisener, Wise1.uk</p>
        </footer>
    </div>
</body>
</html>
