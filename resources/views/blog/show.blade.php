@extends('layouts.app')

@section('content')

   <div class="mt-10">

            <!-- Post Header -->
            <div class="max-w-4xl mx-auto my-10">
                <p class="text-zinc-400 text-sm border-l pl-4 mb-2">{{ $page->date->format('F j, Y') }}</p>
                <h2 class="text-3xl md:text-5xl font-semibold tracking-tight my-6">{{ $page->title }}</h2>
                <p class="text-zinc-500">{{ $page->summary }}</p>
             </div>

            <!-- Post image -->
            <div class="max-w-4xl mx-auto">
                <img src="{{ '/assets/images/uploads/' . 'large_' . $page->original_image }}" class="rounded-lg border border-zinc-200 mx-auto" alt="blog-post-picture">
            </div>      
            
            <!-- page body -->

            <!-- Gallery Setup if exists -->
            
            <div class="">
                <div class="max-w-4xl mx-auto wise1text mt-10">

            <!-- Table of contents -->
            <div class="w-full">
                @if(count($page->getBodyHeadings('h2')) > 2)
                    <div class="toc my-10">
                        <p id="toc-title" class=" mb-2 border-b border-gray-300 font-bold cursor-pointer">
                            <i class="fa-solid fa-arrow-down-short-wide text-teal-700"></i> 
                                Table of contents
                            <span id="toc-arrow" class="ml-2 transform transition-transform duration-200"></span>
                        </p>
                        <ul id="toc-list" class="hidden">
                            @foreach($page->getBodyHeadings('h2') as $heading)
                                <li class="list-none mb-0"><a href="#{{ Str::slug($heading) }}" class="hover:text-teal-700">{{ $heading }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Post text, separate from other content.  We do this as wise1text is used for formatting -->

            <div class="wise1text mt-10">
                {!! $page->addAnchorLinkstoHeadings() !!}
            </div>

            <!-- Share Buttons -->
            <div class="mt-10 text-center flex justify-center gap-4">
                <a href="#blank"><button class="border border-gray-300 p-1 text-zinc-600 py-1 px-2 mr-2">Share</button></a>

                @php
                    $shareText = urlencode($page->title);
                    $shareUrl  = urlencode(url('/blog/' . $page->slug));
                @endphp
                <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&url={{ $shareUrl }}" target="_blank" rel="noopener">
                    <button id="social-button" aria-label="x-button" class="border border-gray-300 p-1 text-indigo-500 text-xs py-1 px-2 mr-2 hover:border-gray-400 cursor-pointer">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-6 w-6 fill-zinc-500 transition group-hover:fill-zinc-600 dark:fill-zinc-400 dark:group-hover:fill-zinc-300">
                            <path d="M13.3174 10.7749L19.1457 4H17.7646L12.7039 9.88256L8.66193 4H4L10.1122 12.8955L4 20H5.38119L10.7254 13.7878L14.994 20H19.656L13.3171 10.7749H13.3174ZM11.4257 12.9738L10.8064 12.0881L5.87886 5.03974H8.00029L11.9769 10.728L12.5962 11.6137L17.7652 19.0075H15.6438L11.4257 12.9742V12.9738Z"></path>
                        </svg>
                    </button>
                </a>

                <a href="http://www.linkedin.com/shareArticle?mini=true&url=https://wisener.net/blog/{{ $page->slug }}">
                    <button id="social-button" aria-label="linkedin-button" class="border border-gray-300 p-1 text-indigo-500 text-xs py-1 px-2 mr-2 hover:border-gray-400 cursor-pointer">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-6 w-6 fill-zinc-500 transition group-hover:fill-zinc-600 dark:fill-zinc-400 dark:group-hover:fill-zinc-300"><path d="M18.335 18.339H15.67v-4.177c0-.996-.02-2.278-1.39-2.278-1.389 0-1.601 1.084-1.601 2.205v4.25h-2.666V9.75h2.56v1.17h.035c.358-.674 1.228-1.387 2.528-1.387 2.7 0 3.2 1.778 3.2 4.091v4.715zM7.003 8.575a1.546 1.546 
                        0 01-1.548-1.549 1.548 1.548 0 111.547 1.549zm1.336 9.764H5.666V9.75H8.34v8.589zM19.67 3H4.329C3.593 3 3 3.58 3 4.297v15.406C3 20.42 3.594 21 4.328 21h15.338C20.4 21 21 20.42 21 19.703V4.297C21 3.58 20.4 3 19.666 3h.003z"></path></svg>
                </button>
                </a>
            </div>

            @if($page->Users)

                <!-- Author Box -->
                <div class="max-w-4xl mx-auto mt-10">
                    <div class="flex items-center gap-4 p-4 md:p-6 border border-zinc-200 rounded-lg bg-white">
                        <img 
                            src="{{ '/assets/images/avatars/' . $page->Users->avatar }}" 
                            alt="{{ $page->Users->name }} avatar" 
                            class="w-16 h-16 md:w-20 md:h-20 rounded-2xl shadow-lg border border-zinc-300 object-cover">
                        <div class="flex-1">
                            <h3 class="font-semibold text-zinc-800 !text-lg">{{ $page->Users->name }}</h3>
                            <p class="text-zinc-600 text-sm !mt-0">{{ $page->Users->bio }}</p>
                        </div>
                    </div>
                </div>

            @endif

    </div>

    <div class="max-w-4xl mx-auto mt-10">
        @if ($previousPage || $nextPage)
            <div class="grid grid-cols-2 gap-6 items-center rounded-lg mb-10">
                @if ($previousPage)
                    <a href="{{ url('/blog/' . $previousPage->slug) }}" 
                    class="flex items-center space-x-4 group hover:bg-gray-100 p-4 rounded-lg transition">
                        <img src="{{ '/assets/images/uploads/small_' . $previousPage->original_image }}" 
                            alt="{{ $previousPage->title }}" 
                            class="w-20 h-20 object-cover rounded-lg shadow-sm hidden md:block border border-slate-300">
                        <div>
                            <span class="text-sm text-gray-500 font-bold">Previous Post</span>
                            <h3 class="text-gray-800 group-hover:text-teal-500 transition text-xs md:text-sm mt-2">
                                {{ $previousPage->title }}
                            </h3>
                        </div>
                    </a>
                @else
                    <div class="opacity-50 text-center text-gray-400">No Older Posts</div>
                @endif
        
                @if ($nextPage)
                    <a href="{{ url('/blog/' . $nextPage->slug) }}" 
                    class="flex items-center space-x-4 justify-end text-right group hover:bg-gray-100 p-4 rounded-lg transition">
                        <div>
                            <span class="text-sm text-gray-500 font-bold">Next Post</span>
                            <h3 class="text-gray-800 group-hover:text-teal-500 transition text-xs md:text-sm mt-2">
                                {{ $nextPage->title }}
                            </h3>
                        </div>
                        <img src="{{ '/assets/images/uploads/small_' . $nextPage->original_image }}" 
                            alt="{{ $nextPage->title }}" 
                            class="w-20 h-20 object-cover rounded-lg shadow-sm hidden md:block border border-slate-300">
                    </a>
                @else
                    <div class="opacity-50 text-center text-gray-400 ">No Newer Posts</div>
                @endif
            </div>
        @endif
    </div>

    <!-- Comments Section -->
    <div class="max-w-4xl mx-auto mb-10">
        @include('partials.comments',
        [
            'comments' => $page->comments,
            'model' => $page
        ])
    </div>
    

    <script>
    // This handles the dropdown of the table of contents
        document.addEventListener("DOMContentLoaded", function () {
            const tocTitle = document.getElementById('toc-title');
            const tocList = document.getElementById('toc-list');
            const tocArrow = document.getElementById('toc-arrow');

            // Toggle the visibility of the table of contents
            tocTitle.addEventListener('click', function () {
                tocList.classList.toggle('hidden'); // Show or hide the TOC list
                tocArrow.classList.toggle('rotate-90'); // Rotate the arrow indicator
            });
        });
    </script>

@endsection
