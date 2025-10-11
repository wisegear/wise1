@extends('layouts.admin')

@section('content')

<div class="flex flex-col rounded items-center bg-lime-300 p-4">
    <h2 class="font-bold text-2xl">Blog Management</h2>
    <p>Create new blog categories and manage existing categories here.</p>
</div>

<!-- Display category creation success message -->
@if (session('created'))
    <div class="bg-green-500 text-white p-4 rounded mb-4 my-10 mx-auto">
        {{ session('created') }}
    </div>
@endif

<!-- Display category creation success message -->
@if (session('updated'))
    <div class="bg-green-500 text-white p-4 rounded mb-4 my-10 mx-auto">
        {{ session('updated') }}
    </div>
@endif

<!-- Display category creation success message -->
@if (session('deleted'))
    <div class="bg-red-500 text-white p-4 rounded mb-4 my-10 mx-auto">
        {{ session('deleted') }}
    </div>
@endif

<!-- Create new Articles Category --> 
<div>
    <form action="/admin/blog" method="post">
        @csrf
        <input type="hidden" name="form_type" value="create">
        <div class="flex flex-col border rounded shadow-lg my-10 w-1/2 mx-auto p-6">

            <label for="new_category_name" class="font-bold mb-2">Enter name of blog category</label>
            @error('create_name')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
            <input type="text" id="new_category_name" name="new_category_name" class="rounded border p-1 mb-4" value="{{ old('create_name') }}">

            <button type="submit" class="inline-block self-start mx-auto mt-4 border rounded p-2 bg-lime-400 hover:bg-lime-300">Create</button>
        </div>
    </form>
</div>

<!-- View existing Articles and allow them to be amended --> 
<div class="my-10 flex flex-col w-2/3 mx-auto">
    <h2 class="font-bold text-xl mb-4">Existing Categories:</h2>
    <table class="border-collapse w-full">
        <thead>
            <tr class="bg-lime-400">
                <th>ID</th>
                <th>Name</th>
                <th>Has Articles?</th>
                <th>Update</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    
                    <!-- Editable Name Input with Update Form -->
                    <form action="/admin/blog/{{ $category->id }}" method="POST" class="flex items-center">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="form_type" value="update">
                        <td>
                            @error('update_name')
                                <span class="text-red-500">{{ $message }}</span>
                            @enderror
                            <input 
                                type="text" 
                                name="category_name" 
                                value="{{ $category->name }}" 
                                class="border rounded p-1 w-full"
                                required
                            />
                        </td>

                        <td class="text-center">{{ $category->blogPosts->count() }}</td>
  
                        <td class="space-x-4 text-center">
                            <!-- Update Button -->
                            <button type="submit" class="border rounded p-2 bg-lime-500 hover:bg-lime-400 font-bold text-xs uppercase">
                                Update
                            </button>
                        </td>
                    </form>

                    <!-- Delete Form -->
                    <form action="/admin/blog/{{ $category->id }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <td class="space-x-4 text-center">
                            <button 
                                type="submit" 
                                class="border rounded p-2 font-bold text-xs text-white uppercase 
                                {{ $category->blogPosts->count() > 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-500 hover:bg-red-400' }}" 
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

<div>
    <table class="w-full">
        <tr class="bg-lime-200">
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Date</th>
            <th>Update</th>
            <th>Delete</th>
        </tr>
        @foreach ($posts as $post)
        <tr>
            <td>{{ $post->id }}</td>
            <td><a href="../blog/{{ $post->slug }}">{{ $post->title }}</a></td>
            <td>{{ $post->users->name }}</td>
            <td>{{ $post->date->format('d-m-Y') }}</td>
            <td class="text-center"><a href="../blog/{{ $post->id }}/edit"><button class="border rounded bg-lime-500 hover:bg-lime-400 p-1 text-sm">Update</button></a></td>
            <td class="text-center">
                <!-- Delete Form -->
                <form action="../blog/{{ $post->id}}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="border rounded bg-red-500 text-white p-1 text-sm hover:bg-red-400">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="mt-6 w-1/2 mx-auto">
        {{ $posts->links() }}
    </div>
</div>

@endsection