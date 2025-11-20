@extends('layouts.admin')

@section('content')

<div class="max-w-7xl mx-auto px-4">
    {{-- Hero / Header --}}
    <section class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                Admin Console
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">Dashboard</h1>
            <p class="mt-3 text-sm md:text-base leading-7 text-zinc-500 max-w-2xl">
                Manage users, content and operations. This mirrors the clean card layout used on the frontend.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ url('/admin/users') }}" class="rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">Manage Users</a>
            <a href="{{ url('/admin/blogposts') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black">Manage Blog</a>
        </div>
    </section>

    {{-- Stats --}}
    <section class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Users Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-800">Users</h2>
                <span class="inline-flex items-center rounded-md bg-lime-50 px-2.5 py-0.5 text-xs font-medium text-lime-700 ring-1 ring-inset ring-lime-600/20">{{ $users->count() }} total</span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-4 text-sm text-zinc-600">
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Banned</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $users_banned }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $users_pending }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Members</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $users_active }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Admin</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $users_admin }}</p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ url('/admin/users') }}" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">
                    {{-- icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M7.5 7.5a3 3 0 116 0 3 3 0 01-6 0z"/><path fill-rule="evenodd" d="M4.5 18a6 6 0 1112 0v.75a.75.75 0 01-.75.75h-10.5a.75.75 0 01-.75-.75V18z" clip-rule="evenodd"/></svg>
                    View users
                </a>
            </div>
        </div>

        {{-- Blog Posts Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-800">Blog posts</h2>
                <span class="inline-flex items-center rounded-md bg-cyan-50 px-2.5 py-0.5 text-xs font-medium text-cyan-700 ring-1 ring-inset ring-cyan-600/20">{{ $blogposts->count() }} total</span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-4 text-sm text-zinc-600">
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Unpublished</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $blogunpublished->count() }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Published</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $blogposts->count() - $blogunpublished->count() }}</p>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <a href="{{ url('/admin/blogposts') }}" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M4.5 4.5h15v2.25h-15zM4.5 9.75h15v2.25h-15zM4.5 15h15v2.25h-15z"/></svg>
                    View posts
                </a>
                <a href="{{ url('/admin/blogposts/create') }}" class="inline-flex items-center gap-2 rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-black">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z" clip-rule="evenodd"/></svg>
                    New post
                </a>
            </div>
        </div>
    </section>

    {{-- Quick Links --}}
    <section class="mt-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-zinc-800">Quick links</h3>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ url('/admin/users') }}" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Users</a>
            <a href="{{ url('/admin/blog') }}" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Blog</a>
            <a href="{{ url('/admin/articles') }}" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Articles</a>
            <a href="{{ url('/admin/support') }}" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Support</a>
        </div>
    </section>
</div>

@endsection