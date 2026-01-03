@extends('layouts.app')

@section('content')

   {{-- Hero --}}
   <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-center max-w-7xl mx-auto my-10">
       <div class="max-w-5xl relative z-10">
           <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
               <span class="h-2 w-2 rounded-lg bg-lime-500"></span>
               Blog insights and commentary
           </div>
           <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
               Articles, insights &amp; updates
           </h1>
           <p class="mt-3 text-md leading-7 text-zinc-500">
               Read through analysis, commentary, and notes on data sources, corrections, and market behaviour — the same ideas shaping how data is used across the site. 
               A mix of practical research notes and observations from the housing market.
           </p>
       </div>
       <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
           <img src="{{ asset('/assets/images/site/blog.svg') }}" alt="Blog" class="w-64 h-auto">
       </div>
   </section>

    <div class="flex-grow max-w-7xl mx-auto">
        <!-- Split into 2, one for articles and another for whatever -->
        <main class="my-10 md:my-20 flex gap-20">
            <div class="md:w-8/12">
                <div>
                    @foreach($posts as $post)
                        <article class="mb-10 transition-colors duration-200 hover:bg-zinc-100 p-4 rounded-xl">
                            <!-- Post image -->
                            <div class="max-w-3xl mx-auto mb-4">
                                <div class="w-full max-w-[800px] h-[290px] mx-auto overflow-hidden rounded-lg border border-zinc-200 bg-white">
                                    <img
                                        src="{{ asset('assets/images/uploads/medium_' . $post->original_image) }}"
                                        alt="{{ $post->title }}"
                                        class="w-full h-full object-contain"
                                        loading="lazy"
                                    >
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center">
                                    <p class="text-zinc-400 text-sm border-l pl-4">
                                        {{ $post->date->format('F j, Y') }}
                                    </p>
                                    <div class="flex items-center text-zinc-400 text-sm ml-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 mr-1">
                                            <path d="M12 3c-4.97 0-9 3.582-9 8 0 2.104 1.003 4.005 2.652 5.402-.177.94-.712 2.303-2.043 3.61a.75.75 0 0 0 .69 1.268c2.152-.37 3.76-1.274 4.77-2.02A11.97 11.97 0 0 0 12 19c4.97 0 9-3.582 9-8s-4.03-8-9-8z" />
                                        </svg>
                                        <span>{{ $post->comments->count() }} comments</span>
                                    </div>
                                </div>

                                @can('Admin')
                                <div class="flex items-center space-x-4">
                                    <a href="/blog/{{ $post->id }}/edit">
                                        <!-- Edit Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-4 text-orange-800">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </a>

                                    <form method="POST" action="/blog/{{ $post->id }}"
                                        onsubmit="return confirm('Are you sure you want to delete this post?');"
                                        class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursor-pointer">
                                            <!-- Delete Icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="h-4 text-red-500">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21
                                                        c.342.052.682.107 1.022.166m-1.022-.165
                                                        L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084
                                                        a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0
                                                        a48.108 48.108 0 0 0-3.478-.397m-12 .562
                                                        c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1
                                                        3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201
                                                        a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09
                                                        2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </div>

                            <a href="/blog/{{ $post->slug }}">
                                <h2 class="my-4 font-semibold hover:text-lime-600 text-sm hover:underline">{{ $post->title }}</h2>
                                <p class="text-sm text-zinc-500">{{ $post->summary }}</p>
                                <p class="text-sm text-lime-600 mt-4 font-semibold tracking-tight">Read article</p>
                            </a>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="">
                    {{ $posts->links() }}
                </div>
            
            </div>

            <!-- Blog Sidebar -->
            <div class="w-4/12 hidden md:block">
                <!-- Search -->
                <div class="mb-10 w-full">
                    <form method="get" action="/blog" class="mb-5">
                        <h2 class="text-lg font-bold mb-2">Search Blog</h2>
                        <div class="relative">
                            <input type="text" class="border border-slate-300 rounded-md w-full text-sm pl-2 p-2 bg-white" id="search" name="search" placeholder="Enter search term">
                            <button class=" absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>
                </div>
                <!-- Categories -->
                <div class="text-sm">
                    <div class="border-b pb-2 border-slate-300">
                        <h2 class="text-lg font-bold">Categories</h2>
                    </div>
                    <div class="my-4">
                        <ul class="text-zinc-500 space-y-2">
                            @foreach($categories as $category)
                            <li><a class="hover:text-lime-500" href="/blog?category={{ Str::slug($category->name) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <!-- Blog Tags -->
                <div class="hidden md:block my-6">
                    <h2 class="text-lg font-bold border-b mb-4 border-slate-300">Popular Tags</h2>
                    @foreach ($popular_tags as $tag)
                    <a href="/blog?tag={{ $tag->name }}" class="">
                        <button class="mr-2 mb-4 p-1 text-xs uppercase border border-slate-300 bg-zinc-500 text-white hover:bg-zinc-500 rounded cursor-pointer">{{ $tag->name }}</button>
                    </a>
                    @endforeach
                </div>
                @can('Admin')
                    <!-- Admin -->
                    <div class="hidden md:block my-6">
                        <h2 class="text-xl font-bold text-red-500 border-b border-gray-300 mb-4"><i class="fa-solid fa-user-secret text-red-500"></i> Admin Tools</h2>
                        <div class="flex justify-center">
                            <a href="/blog/create" class="standard-button">Create New Post</a>
                        </div>
                        <div class="flex flex-col space-y-2 text-sm mt-4">
                            @foreach ($unpublished as $post)
                                <a href="../blog/{{$post->id}}/edit" class="text-slate-800 hover:text-sky-700">{{ $post->title }}</a>
                            @endforeach
                        </div>
                    </div> 
                @endcan 
            </div>
        </main>
    </div>

@endsection