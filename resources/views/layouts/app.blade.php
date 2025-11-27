<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'PropertyResearch') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/favicon/favicon-180.png') }}">
    <link rel="manifest" href="{{ asset('assets/favicon/site.webmanifest') }}">

    @isset($page)
    <!-- Twitter Meta -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@ukprores" />
    <meta name="twitter:title" content="{{ $page->title }}" />
    <meta name="twitter:description" content="{{ $page->summary }}" />
    <meta name="twitter:image" content="{{ url('assets/images/uploads/' . 'medium_' . $page->original_image) }}" />

    <!-- Open Graph Meta -->
    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="PropertyResearch.uk" />
    <meta property="og:title" content="{{ $page->title }}" />
    <meta property="og:description" content="{{ $page->summary }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:image" content="{{ url('assets/images/uploads/' . 'medium_' . $page->original_image) }}" />
    <meta property="og:image:width" content="800" />
    <meta property="og:image:height" content="300" />
    @endisset

    <!-- moved this to the top due to FOUC -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- FontAwesome -->
    <script async src="https://kit.fontawesome.com/0ff5084395.js" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Venobox css 
    <link rel="stylesheet" href="{{ asset('assets/css/venobox.min.css') }}" type="text/css" media="screen" />
    <script type="text/javascript" src="{{ asset('assets/js/venobox.min.js') }}"></script>
    -->

    <!-- TinyMCE -->
<!-- TinyMCE (open CDN for now) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

</head>
<body class="bg-zinc-50">
    <div class="min-h-screen flex flex-col">
        {{-- Desktop Logo Header --}}
        <div class="hidden xl:block bg-white border-b border-zinc-200">
            <div class="max-w-7xl mx-auto flex items-center">
                {{-- Left search button --}}
                <div class="flex-1 flex items-center">
                    <a href="/property/search"
                       class="inline-flex items-center shadow gap-2 rounded bg-zinc-800 text-zinc-100 px-4 py-2 text-xs hover:bg-zinc-500 transition shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                        </svg>
                        Search property sales
                    </a>
                </div>

                {{-- Centered logo --}}
                <div class="flex-1 flex items-center justify-center gap-1">
                    <img src="{{ asset('assets/images/site/research-logo-4.png') }}" alt="PropertyResearch.uk logo" class="h-20 w-auto">
                    <span class="text-xl font-semibold tracking-tight text-slate-800">
                        PropertyResearch<span class="text-lime-600 text-sm">.uk</span>
                    </span>
                </div>

                {{-- Right-side auth (login/register or user menu) --}}
                <div class="flex-1 flex items-center justify-end text-sm">
                    @if(Auth::check())
                        {{-- User dropdown (desktop) --}}
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center gap-1 focus:outline-none cursor-pointer">
                                {{ Auth::user()->name }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div id="userDropdown" class="absolute right-0 mt-4 w-30 bg-white border border-slate-200 translate-x-4 rounded-xl shadow-lg z-50 hidden">
                                <div>
                                    <a href="/profile/{{ Auth::user()->name_slug }}" class="block px-4 py-2 hover:bg-zinc-100">Profile</a>
                                    <a href="/support" class="block px-4 py-2 hover:bg-zinc-100">Support</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-zinc-100 hover:text-teal-500 cursor-pointer">Logout</button>
                                    </form>
                                    @can('Admin')
                                        <a href="/admin" class="block px-4 py-2 hover:bg-zinc-100 text-orange-800 font-bold">Admin</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-xs">
                            <a href="/login" class="px-4 py-2 rounded bg-zinc-700 text-white hover:bg-zinc-500 transition">Login</a>
                            <a href="/register" class="px-4 py-2 rounded bg-zinc-200 text-zinc-700 hover:bg-zinc-300 transition hover:text-black">Register</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Desktop Nav --}}
        <nav class="hidden xl:block bg-white border-b border-zinc-200 px-4 py-2">
            <div class="max-w-7xl mx-auto flex items-center justify-center">
                <button id="navToggle" aria-controls="primaryNav" aria-expanded="false" class="md:hidden ml-auto inline-flex items-center justify-center p-2 rounded text-zinc-700 hover:text-lime-600 focus:outline-none" type="button">
                  <span class="sr-only">Open main menu</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                </button>
                <div id="primaryNav" class="hidden md:flex gap-2 text-sm flex-col md:flex-row mt-3 md:mt-0 justify-center">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded {{ request()->is('/') ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Home</a>
                    <a href="{{ url('/blog') }}" class="px-3 py-2 rounded {{ request()->is('blog') ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Blog</a>
                    
                    <div class="relative">
                        <button id="propertyMenuButton" aria-haspopup="true" aria-controls="propertyDropdown" aria-expanded="false" class="px-3 py-2 rounded flex items-center gap-1 text-zinc-700 hover:text-lime-600 focus:outline-none cursor-pointer">
                            Property
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="propertyDropdown" role="menu" aria-labelledby="propertyMenuButton" class="absolute left-0 mt-4 w-[32rem] bg-white border border-zinc-200 rounded shadow-lg z-50 transform transition duration-150 ease-out origin-top opacity-0 scale-95 pointer-events-none hidden">
                            <div class="flex">
                                <!-- Left column -->
                                <div class="py-2 flex-1">
                                    <a href="{{ url('/property') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Dashboard</a>
                                    <a href="{{ url('/property/search') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Property Search</a>
                                    <a href="{{ url('/property/outer-prime-london') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Outer Prime London</a>
                                    <a href="{{ url('/property/prime-central-london') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Prime Central London</a>
                                    <a href="{{ url('/property/ultra-prime-central-london') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Ultra Prime Central London</a>
                                </div>

                                <!-- Vertical divider with top/bottom space -->
                                <div class="w-px bg-zinc-200 my-2"></div>

                                <!-- Right column -->
                                <div class="py-2 flex-1">
                                    <a href="{{ url('/hpi') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">House Price Index</a>
                                    <a href="{{ url('/new-old') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">New Build Comparison</a>
                                    <a href="{{ url('/epc') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">EPC Dashboard</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="relative">
                        <button id="calculatorsMenuButton" aria-haspopup="true" aria-controls="calculatorsDropdown" aria-expanded="false" class="px-3 py-2 rounded flex items-center gap-1 text-zinc-700 hover:text-lime-600 focus:outline-none cursor-pointer">
                            Calculators
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="calculatorsDropdown" role="menu" aria-labelledby="calculatorsMenuButton" class="absolute left-0 mt-4 w-56 bg-white border border-zinc-200 rounded shadow-lg z-50 transform transition duration-150 ease-out origin-top opacity-0 scale-95 pointer-events-none hidden">
                            <a href="{{ url('/affordability') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Affordability Calculator</a>
                            <a href="{{ url('/mortgage-calculator') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Mortgage Calculator</a>
                            <a href="{{ url('/stamp-duty') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Stamp Duty Calculator</a>
                        </div>
                    </div>
                    <a href="{{ url('/repossessions') }}" class="px-3 py-2 rounded {{ request()->is('repossessions') ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Repossessions</a>
                    <a href="{{ url('/deprivation') }}" class="px-3 py-2 rounded {{ request()->is('deprivation') ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">Deprivation</a>
                    <div class="relative">
                        <button id="economicsMenuButton" aria-haspopup="true" aria-controls="economicsDropdown" aria-expanded="false" class="px-3 py-2 rounded flex items-center gap-1 text-zinc-700 hover:text-lime-600 focus:outline-none cursor-pointer">
                            Market Stress Indicators
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="economicsDropdown" role="menu" aria-labelledby="economicsMenuButton" class="absolute left-0 mt-4 w-[32rem] bg-white border border-zinc-200 rounded shadow-lg z-50 transform transition duration-150 ease-out origin-top opacity-0 scale-95 pointer-events-none hidden">
                            <div class="flex">
                                <!-- Left column -->
                                <div class="py-2 flex-1">
                                    <a href="{{ url('/economic-dashboard') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 font-semibold hover:bg-zinc-100 text-zinc-800">Market Stress Dashboard</a>
                                    <a href="{{ url('/interest-rates') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Interest Rates</a>
                                    <a href="{{ url('/inflation') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Inflation (CPIH)</a>
                                    <a href="{{ url('/wage-growth') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Wage Growth</a>
                                </div>

                                <!-- Vertical divider -->
                                <div class="w-px bg-zinc-200 my-2"></div>

                                <!-- Right column -->
                                <div class="py-2 flex-1">
                                    <a href="{{ url('/hpi-overview') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">House Price Index (HPI)</a>
                                    <a href="{{ url('/unemployment') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Unemployment</a>
                                    <a href="{{ url('/approvals') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Mortgage Approvals</a>
                                    <a href="{{ url('/arrears') }}" role="menuitem" tabindex="-1" class="block px-4 py-2 hover:bg-zinc-100 text-zinc-700">Mortgage Arrears (MLAR)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ url('/about') }}" class="px-3 py-2 rounded {{ request()->is('about') ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-700 hover:text-lime-600' }}">About</a>
                </div>
            </div>
        </nav>
    {{-- Mobile Nav --}}
    <nav class="bg-white border-b border-zinc-200 p-4 xl:hidden">
        <div class="w-full flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-1">
                <img src="{{ asset('assets/images/site/research-logo-4.png') }}" alt="PropertyResearch.uk logo" class="h-10 w-auto">
                <span class="text-lg font-semibold tracking-tight text-slate-800">
                    PropertyResearch<span class="text-lime-600 text-sm">.uk</span>
                </span>
            </a>
            <button id="mobileNavToggle" aria-controls="mobileNav" aria-expanded="false"
                class="inline-flex items-center justify-center p-2 rounded text-zinc-700 hover:text-lime-600 focus:outline-none"
                type="button">
                <span class="sr-only">Open main menu</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Collapsible menu --}}
        <div id="mobileNav" class="hidden flex-col mt-3 space-y-1 w-full text-sm">
            <a href="{{ url('/') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Home</a>
            <a href="{{ url('/blog') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Blog</a>

            {{-- Property dropdown for mobile --}}
            <div class="">
                <button id="mobilePropertyBtn"
                    class="w-full flex justify-between items-center px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100 focus:outline-none">
                    Property
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="mobilePropertyMenu" class="hidden flex-col pl-2 space-y-1 mt-1">
                    <a href="{{ url('/property') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Dashboard</a>
                    <a href="{{ url('/property/search') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Property Search</a>
                    <a href="{{ url('/property/outer-prime-london') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Outer Prime London</a>
                    <a href="{{ url('/property/prime-central-london') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Prime Central London</a>
                    <a href="{{ url('/property/ultra-prime-central-london') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Ultra Prime Central London</a>
                    <a href="{{ url('/hpi') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">House Price Index</a>                    
                    <a href="{{ url('/new-old') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">New Build Comparison</a>
                    <a href="{{ url('/epc') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">EPC Dashboard</a>
                </div>
            </div>



            <div class="">
                <button id="mobileCalculatorsBtn" class="w-full flex justify-between items-center px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100 focus:outline-none">
                    Calculators
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="mobileCalculatorsMenu" class="hidden flex-col pl-2 space-y-1 mt-1">
                    <a href="{{ url('/affordability') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Affordability Calculator</a>
                    <a href="{{ url('/mortgage-calculator') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Mortgage Calculator</a>
                    <a href="{{ url('/stamp-duty') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Stamp Duty Calculator</a>
                </div>
            </div>
            <a href="{{ url('/repossessions') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Repossessions</a>
            <a href="{{ url('/deprivation') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Deprivation</a>
            <div class="">
                <button id="mobileIndicatorsBtn" class="w-full flex justify-between items-center px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100 focus:outline-none">
                    Market Stress Indicators
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="mobileIndicatorsMenu" class="hidden flex-col pl-2 space-y-1 mt-1">
                    <a href="{{ url('/economic-dashboard') }}" class="block px-3 py-2 rounded font-semibold text-zinc-800 hover:bg-zinc-100">Market Stress Dashboard</a>
                    <div class="border-t border-zinc-100 my-1"></div>
                    <a href="{{ url('/interest-rates') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Interest Rates</a>
                    <a href="{{ url('/inflation') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Inflation (CPIH)</a>
                    <a href="{{ url('/wage-growth') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Wage Growth</a>
                    <a href="{{ url('/hpi-overview') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">House Price Index (HPI)</a>
                    <a href="{{ url('/unemployment') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Unemployment</a>
                    <a href="{{ url('/approvals') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Mortgage Approvals</a>
                    <a href="{{ url('/arrears') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Mortgage Arrears (MLAR)</a>
                </div>
            </div>
            <a href="{{ url('/about') }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">About</a>

            {{-- Auth links --}}
            @auth
                <a href="/profile/{{ Auth::user()->name_slug }}" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Profile</a>
                <a href="/support" class="block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Support</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded text-zinc-700 hover:bg-zinc-100">Logout</button>
                </form>
            @else
                <div class="flex justify-between space-x-2 px-3">
                    <a href="/login" class="flex-1 text-center py-1 px-2 rounded bg-zinc-700 text-white text-sm hover:bg-zinc-500 transition">Login</a>
                    <a href="/register" class="flex-1 text-center py-1 px-2 rounded bg-zinc-200 text-zinc-700 text-sm hover:bg-zinc-300 transition">Register</a>
                </div>
            @endauth
        </div>
    </nav>

        {{-- Content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-zinc-200 p-4 text-center text-sm text-zinc-500">
             <p>&copy; Lee Wisener - PropertyResearch.uk. Built with&nbsp;<a href="https://laravel.com" class="text-rose-500 hover:text-rose-700">Laravel</a>.  Hosted on&nbsp;<a href="https://hetzner.cloud/?ref=rfLEdCP3iIfx" class="text-rose-500 hover:text-rose-700">Hetzner Cloud.</a></p>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
