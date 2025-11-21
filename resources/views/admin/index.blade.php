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

        {{-- Support Tickets Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-800">Support Tickets</h2>
                <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                    {{ $tickets_total }} total
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 md:grid-cols-2 gap-4 text-sm text-zinc-600">
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Open</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $tickets_open }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $tickets_pending }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Awaiting reply</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $tickets_awaiting }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3">
                    <p class="text-zinc-500">Closed</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $tickets_closed }}</p>
                </div>
            </div>

            <div class="mt-5">
                <a href="{{ url('/admin/support') }}" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">
                    {{-- icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75h.008v.008H9.75V9.75zm0 3h.008v.008H9.75V12.75zm0 3h.008v.008H9.75V15.75zM12 9.75h.008v.008H12V9.75zm0 3h.008v.008H12V12.75zm0 3h.008v.008H12V15.75zM14.25 9.75h.008v.008h-.008V9.75zm0 3h.008v.008h-.008V12.75zm0 3h.008v.008h-.008V15.75z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6a3 3 0 013-3h10.5a3 3 0 013 3v12a3 3 0 01-3 3H6.75a3 3 0 01-3-3V6z" />
                    </svg>
                    View tickets
                </a>
            </div>
        </div>

        {{-- Upcoming Data Updates --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-800">Upcoming Data Updates</h2>

                @if(isset($upcoming_updates) && $upcoming_updates->count() > 0)
                    <span class="inline-flex items-center rounded-md bg-lime-50 px-2.5 py-0.5 text-xs font-medium text-lime-700 ring-1 ring-inset ring-lime-600/20">
                        Next {{ $upcoming_updates->count() }} due
                    </span>
                @else
                    <span class="inline-flex items-center rounded-md bg-zinc-50 px-2.5 py-0.5 text-xs font-medium text-zinc-700 ring-1 ring-inset ring-zinc-300/60">
                        None scheduled
                    </span>
                @endif
            </div>

            <div class="mt-5 space-y-4 text-sm text-zinc-600">
                @forelse($upcoming_updates as $update)
                    <div class="rounded-lg border border-zinc-200 p-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-zinc-900">{{ $update->name }}</p>
                            @if($update->notes)
                                <p class="mt-1 text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($update->notes, 80) }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-zinc-500">Next update</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">
                                {{ optional($update->next_update_due_at)->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-600">No upcoming data updates have been set.</p>
                @endforelse
            </div>

            <div class="mt-5">
                <a href="{{ url('/admin/updates') }}" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M6.75 3A2.25 2.25 0 004.5 5.25v13.5A2.25 2.25 0 006.75 21h10.5A2.25 2.25 0 0019.5 18.75V9.31a2.25 2.25 0 00-.659-1.591l-4.06-4.06A2.25 2.25 0 0013.19 3H6.75z" />
                        <path d="M9 8.25A.75.75 0 019.75 7.5h2.25a.75.75 0 010 1.5H9.75A.75.75 0 019 8.25zM9 11.25A.75.75 0 019.75 10.5h4.5a.75.75 0 010 1.5h-4.5A.75.75 0 019 11.25zM9.75 13.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5z" />
                    </svg>
                    Manage updates
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
            <a href="{{ url('/admin/support') }}" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Support</a>
        </div>
    </section>
</div>

@endsection