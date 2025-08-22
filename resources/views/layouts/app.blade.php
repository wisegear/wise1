<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'PropertyResearch') }}</title>
    <!-- moved this to the top due to FOUC -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('/assets/images/site/logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FontAwesome -->
    <script async src="https://kit.fontawesome.com/0ff5084395.js" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Venobox css -->
    <link rel="stylesheet" href="{{ asset('assets/css/venobox.min.css') }}" type="text/css" media="screen" />
    <script type="text/javascript" src="{{ asset('assets/js/venobox.min.js') }}"></script>
</head>
<body class="bg-zinc-50">
    <div class="min-h-screen flex flex-col">
        {{-- Nav --}}
        <nav class="bg-white border-b border-zinc-200 p-4">
            <div class="max-w-7xl mx-auto flex items-center">
                <a href="{{ url('/') }}" class="font-semibold text-lg">PropertyResearch<span class="text-sm text-lime-600">.uk</span></a>
                <button id="navToggle" aria-controls="primaryNav" aria-expanded="false" class="md:hidden ml-auto inline-flex items-center justify-center p-2 rounded text-zinc-700 hover:text-lime-600 focus:outline-none" type="button">
                  <span class="sr-only">Open main menu</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                </button>
                <div id="primaryNav" class="hidden md:flex flex-1 gap-2 text-sm md:ml-8 flex-col md:flex-row mt-3 md:mt-0">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded {{ request()->is('/') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Home</a>
                    <a href="{{ url('/blog') }}" class="px-3 py-2 rounded {{ request()->is('blog') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Blog</a>
                    <a href="{{ url('/approvals') }}" class="px-3 py-2 rounded {{ request()->is('approvals') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Mortgage Approvals</a>

                    <div class="relative">
                        <button id="propertyMenuButton" aria-haspopup="true" aria-controls="propertyDropdown" aria-expanded="false" class="px-3 py-2 rounded flex items-center gap-1 text-zinc-700 hover:text-lime-600 focus:outline-none cursor-pointer">
                            Property
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="propertyDropdown" role="menu" aria-labelledby="propertyMenuButton" class="absolute left-0 mt-4 w-48 bg-white border border-zinc-200 rounded shadow-lg z-50 transform transition duration-150 ease-out origin-top opacity-0 scale-95 pointer-events-none hidden">
                            <a href="{{ url('/property') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Dashboard</a>
                            <a href="{{ url('/property/search') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Property Search</a>
                        </div>
                    </div>

                    <a href="{{ url('/repossessions') }}" class="px-3 py-2 rounded {{ request()->is('repossessions') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Repossessions</a>
                    <a href="{{ url('/interest-rates') }}" class="px-3 py-2 rounded {{ request()->is('interest-rates') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Interest Rates</a>
                    <a href="{{ url('/about') }}" class="px-3 py-2 rounded {{ request()->is('about') ? 'bg-lime-100 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">About</a>
                </div>
                <ul class="flex items-center gap-2 text-sm">
                    @if(Auth::check())
                        <!-- Dropdown Trigger -->
                        <li class="relative">
                            <button id="userMenuButton" class="flex items-center gap-1 focus:outline-none cursor-pointer">
                                {{ Auth::user()->name }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="userDropdown" class="absolute right-0 mt-4 w-30 bg-white border border-slate-200 translate-x-4 rounded-xl shadow-lg z-50 hidden">
                                <div class="">
                                    <a href="/profile/{{ Auth::user()->name_slug }}" class="block px-4 py-2 hover:bg-zinc-100">Profile</a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-zinc-100 hover:text-teal-500 cursor-pointer">Logout</button>
                                        </form>
                                    @can('Admin')
                                        <a href="/admin" class="block px-4 py-2 hover:bg-zinc-100 text-orange-800 font-bold">Admin</a>
                                    @endcan
                                </div>
                            </div>
                        </li>
                    @else
                        <li class="flex items-center gap-2 text-xs">
                            <a href="/login" class="px-4 py-2 rounded bg-lime-600 text-white hover:bg-lime-700 transition">Login</a>
                            <a href="/register" class="px-4 py-2 rounded bg-zinc-200 text-zinc-700 hover:bg-zinc-300 transition hover:text-white">Register</a>
                        </li>
                    @endif
                </ul>
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
