@extends('layouts.admin')

@section('content')

<div class="max-w-7xl mx-auto px-4">
    {{-- Header / Hero --}}
    <section class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                Admin Console
            </div>
            <h1 class="mt-4 text-2xl md:text-3xl font-bold tracking-tight text-zinc-900">Blog Management</h1>
            <p class="mt-3 text-sm md:text-base leading-7 text-zinc-500 max-w-2xl">Create new blog categories and manage existing categories and posts.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin" class="rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">Back to Dashboard</a>
        </div>
    </section>

    {{-- Flash messages --}}
    @if (session('created'))
        <div class="rounded-lg bg-lime-50 text-lime-800 ring-1 ring-lime-600/20 px-4 py-3 mb-4">{{ session('created') }}</div>
    @endif
    @if (session('updated'))
        <div class="rounded-lg bg-cyan-50 text-cyan-800 ring-1 ring-cyan-600/20 px-4 py-3 mb-4">{{ session('updated') }}</div>
    @endif
    @if (session('deleted'))
        <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-600/20 px-4 py-3 mb-4">{{ session('deleted') }}</div>
    @endif

    {{-- Create new Articles Category --}}
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm mb-8">
        <form action="/admin/blog" method="post" class="max-w-xl">
            @csrf
            <input type="hidden" name="form_type" value="create">
            <label for="new_category_name" class="font-semibold text-zinc-800">Enter name of blog category</label>
            @error('create_name')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
            <input type="text" id="new_category_name" name="new_category_name" class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-lime-500" value="{{ old('create_name') }}">
            <button type="submit" class="mt-4 inline-flex items-center gap-2 rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black">Create</button>
        </form>
    </section>

    {{-- Existing Categories --}}
    <section class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden mb-10">
        <div class="px-6 pt-6">
            <h2 class="text-lg font-semibold text-zinc-800">Existing Categories</h2>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 border-y border-zinc-200 text-zinc-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Has Articles?</th>
                        <th class="px-4 py-3 font-medium">Update</th>
                        <th class="px-4 py-3 font-medium">Delete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($categories as $category)
                        <tr class="hover:bg-zinc-50 align-top">
                            <td class="px-4 py-3">{{ $category->id }}</td>

                            <!-- Editable Name Input with Update Form -->
                            <form action="/admin/blog/{{ $category->id }}" method="POST" class="contents">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="form_type" value="update">
                                <td class="px-4 py-3">
                                    @error('update_name')
                                        <div class="text-red-600 text-xs mb-1">{{ $message }}</div>
                                    @enderror
                                    <input 
                                        type="text" 
                                        name="category_name" 
                                        value="{{ $category->name }}" 
                                        class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        required
                                    />
                                </td>

                                <td class="px-4 py-3 text-center">{{ $category->blogPosts->count() }}</td>
        
                                <td class="px-4 py-3 text-center">
                                    <!-- Update Button -->
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">
                                        Update
                                    </button>
                                </td>
                            </form>

                            <!-- Delete Form -->
                            <form action="/admin/blog/{{ $category->id }}" method="POST" class="contents">
                                @csrf
                                @method('DELETE')
                                <td class="px-4 py-3 text-center">
                                    <button 
                                        type="submit" 
                                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold text-white shadow-sm {{ $category->blogPosts->count() > 0 ? 'bg-zinc-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }}" 
                                        {{ $category->blogPosts->count() > 0 ? 'disabled' : '' }}
                                        onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.');"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Posts Table --}}
    <section class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 pt-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-800">Posts</h2>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 border-y border-zinc-200 text-zinc-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Author</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Update</th>
                        <th class="px-4 py-3 font-medium">Delete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($posts as $post)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-3">{{ $post->id }}</td>
                        <td class="px-4 py-3"><a class="text-lime-700 hover:underline" href="../blog/{{ $post->slug }}">{{ $post->title }}</a></td>
                        <td class="px-4 py-3">{{ $post->users->name }}</td>
                        <td class="px-4 py-3">{{ $post->date->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 text-center"><a href="../blog/{{ $post->id }}/edit"><button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">Update</button></a></td>
                        <td class="px-4 py-3 text-center">
                            <form action="../blog/{{ $post->id}}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-zinc-200">
            <div class="max-w-lg mx-auto">
                {{ $posts->links() }}
            </div>
        </div>
    </section>
</div>

@endsection