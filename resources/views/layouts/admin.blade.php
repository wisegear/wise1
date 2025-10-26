<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Blog') }}</title>

        <!-- TinyMCE -->
        <script src="https://cdn.tiny.cloud/1/a1rn9rzvnlulpzdgoe14w7kqi1qpfsx7cx9am2kbgg226dqz/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- FontAwesome -->
        <script src="https://kit.fontawesome.com/0ff5084395.js" crossorigin="anonymous"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                        <a href="/admin/articles" class="hover:text-lime-600">Articles</a>
                        <a href="/admin/support" class="hover:text-lime-600">Support</a>
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
    </body>

</html>