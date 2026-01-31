@extends('layouts.app')

@section('content')

   {{-- Hero --}}
   <section class="relative z-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm flex flex-col lg:flex-row justify-between items-center max-w-7xl mx-auto my-10">
       @include('partials.hero-background')
       <div class="max-w-4xl relative z-10">
           <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/80 px-3 py-1 text-xs text-zinc-700 shadow-sm">
               <span class="h-2 w-2 rounded-full bg-lime-500"></span>
               Property research desk
           </div>
           <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
               Market notes, data checks &amp; field commentary
           </h1>
           <p class="mt-3 text-base leading-7 text-zinc-600">
               Some notes on housing-market research, methodology updates, and source notes, built to show how the
               signals are interpreted and where the numbers come from.  Best case, you find it interesting, worst, you killed some time whilst the kettle boils.
           </p>
       </div>
       <div class="mt-6 lg:mt-0 lg:ml-8 flex-shrink-0 relative z-10">
           <div class="">
               <img src="{{ asset('/assets/images/site/blog.jpg') }}" alt="Blog" class="w-82 h-auto">
           </div>
       </div>
   </section>

    <div class="flex-grow max-w-7xl mx-auto px-4 lg:px-0">
        <!-- Split into 2, one for articles and another for whatever -->
        <main class="my-10 md:my-16 flex flex-col lg:flex-row gap-8 lg:gap-10">
            <div class="lg:w-9/12">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    @foreach($posts as $post)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <!-- Post image -->
                            <div class="max-w-3xl mx-auto mb-4">
                                <div class="w-full max-w-[800px] mx-auto overflow-hidden rounded-xl border border-slate-200 bg-slate-50 h-[200px]">
                                    <img
                                        src="{{ asset('assets/images/uploads/small_' . $post->original_image) }}"
                                        alt="{{ $post->title }}"
                                        class="w-full h-full"
                                        loading="lazy"
                                    >
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-2 mx-2">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                                        {{ optional($post->blogCategories)->name ?? 'Research note' }}
                                    </span>
                                    <p class="text-zinc-400 text-sm border-l pl-4 ml-3">
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
                                <h2 class="my-4 text-lg font-semibold text-zinc-900 hover:text-lime-600 hover:underline mx-2">{{ $post->title }}</h2>
                                <p class="text-sm text-zinc-600 leading-6 mx-2">{{ $post->summary }}</p>
                                <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-lime-700 mx-2">
                                    Read article
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </span>
                            </a>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-10">
                    {{ $posts->links() }}
                </div>
            
            </div>

            <!-- Blog Sidebar -->
            <div class="lg:w-4/12 hidden lg:block">
                <!-- Search -->
                <div class="mb-6 w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="get" action="/blog" class="mb-5">
                        <h2 class="text-lg font-bold mb-2">Search the archive</h2>
                        <div class="relative">
                            <input type="text" class="border border-slate-300 rounded-md w-full text-sm pl-3 p-2.5 bg-white" id="search" name="search" placeholder="Enter a topic or keyword">
                            <button class=" absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>
                </div>
                <!-- Categories -->
                <div class="text-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="border-b pb-2 border-slate-200">
                        <h2 class="text-lg font-bold">Topics</h2>
                    </div>
                    <div class="my-4">
                        <ul class="text-zinc-600 space-y-2">
                            @foreach($categories as $category)
                            <li><a class="hover:text-lime-600" href="/blog?category={{ Str::slug($category->name) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <!-- Blog Tags -->
                <div class="hidden md:block my-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold border-b mb-4 border-slate-200">Popular Tags</h2>
                    @foreach ($popular_tags as $tag)
                    <a href="/blog?tag={{ $tag->name }}" class="">
                        <button class="mr-2 mb-3 px-2 py-1 text-xs uppercase border border-zinc-300 bg-zinc-100 text-zinc-700 hover:bg-zinc-200 rounded cursor-pointer">{{ $tag->name }}</button>
                    </a>
                    @endforeach
                </div>
                @can('Admin')
                    <!-- Admin -->
                    <div class="hidden md:block my-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-xl font-bold text-red-500 border-b border-gray-200 mb-4"><i class="fa-solid fa-user-secret text-red-500"></i> Admin Tools</h2>
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
