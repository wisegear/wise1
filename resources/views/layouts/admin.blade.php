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

        <!-- Main outer container for the entire site -->
        <div class="max-w-screen-xl px-5 mx-auto">

            <!-- Main Navigation for the site -->
            <div class="flex items-center py-4 border-b justify-center">
                <div class="space-x-4 hidden md:block md:text-center md:w-8/12">
                    <a href="/" class="hover:text-red-500">Site Home</a>
                    <a href="/admin" class="hover:text-red-500">Admin Home</a>
                    <a href="/admin/users" class="hover:text-red-500">Users</a>
                    <a href="/admin/blog" class="hover:text-red-500">Blog</a>
                </div>
            </div>

            <!-- This is where the content for each page is rendered -->
            <div class="my-10">
                @yield('content')
            </div>

            <!-- Footer for entire site -->
            <!-- Footer -->
            <div class="border-t border-gray-300 mt-auto mb-10">
                <p class="text-center py-2">PropertyBlog.Scot, by Lee Wisener</p>
            </div>

        </div>

    </body>

</html>