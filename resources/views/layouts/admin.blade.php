<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Blog') }}</title>


        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- FontAwesome -->
        <script src="https://kit.fontawesome.com/0ff5084395.js" crossorigin="anonymous"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- TinyMCE (open CDN for now) -->
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
        
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col bg-zinc-50 text-zinc-800">

            <header class="sticky top-0 z-40 bg-white border-b border-zinc-200 shadow-sm">
                <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
                    <a href="/" class="font-semibold text-zinc-900 hover:text-lime-600">PropertyResearch.uk</a>
                    <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-zinc-700">
                        <a href="/admin" class="hover:text-lime-600">Dashboard</a>
                        <a href="/admin/users" class="hover:text-lime-600">Users</a>
                        <a href="/admin/blog" class="hover:text-lime-600">Blog</a>
                        <a href="/admin/support" class="hover:text-lime-600">Support</a>

                        <div class="relative">
                            <button
                                type="button"
                                id="dataDropdownButton"
                                class="inline-flex items-center gap-1 hover:text-lime-600 focus:outline-none"
                            >
                                <span>Data</span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                id="dataDropdownMenu"
                                class="absolute right-0 mt-2 w-40 bg-white border border-zinc-200 rounded shadow-lg hidden z-20"
                            >
                                <a href="/admin/inflation" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Inflation
                                </a>
                                <a href="/admin/unemployment" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Unemployment
                                </a>
                                <a href="/admin/wage-growth" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Wage Growth
                                </a>
                                <a href="/admin/interest-rates" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Interest Rates
                                </a>
                                <a href="/admin/arrears" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Arrears
                                </a>
                                <a href="/admin/approvals" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-lime-600">
                                    Mortgage Approvals
                                </a>
                                <!-- Future data items go here -->
                            </div>
                        </div>

                        <a href="/admin/updates" class="hover:text-lime-600">Updates</a>
                    </nav>
                </div>
            </header>

            <main class="flex-grow py-10 px-4 md:px-8">
                @yield('content')
            </main>

            <footer class="border-t border-zinc-200 bg-white py-6 text-center text-sm text-zinc-500">
                <p>© {{ date('Y') }} PropertyResearch.uk — Admin Console by Lee Wisener</p>
            </footer>

        </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const btn = document.getElementById('dataDropdownButton');
                    const menu = document.getElementById('dataDropdownMenu');
            
                    if (!btn || !menu) return;
            
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        menu.classList.toggle('hidden');
                    });
            
                    document.addEventListener('click', function (e) {
                        if (menu.classList.contains('hidden')) return;
            
                        if (!menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                });
            </script>
            @stack('scripts')
    </body>

</html>