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
            <h1 class="mt-4 text-2xl md:text-3xl font-bold tracking-tight text-zinc-900">User Administration</h1>
            <p class="mt-3 text-sm md:text-base leading-7 text-zinc-500 max-w-2xl">
                View, sort and manage users. Use the arrows in the header to sort by name, email, created date, trust and notes.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin" class="rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50">Back to Dashboard</a>
        </div>
    </section>

    {{-- Users Table Card --}}
    <section class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name 
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=name&order=asc">&#8593;</a>
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=name&order=desc">&#8595;</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Membership</th>
                        <th class="px-4 py-3 font-medium">Email 
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=email&order=asc">&#8593;</a>
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=email&order=desc">&#8595;</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Created 
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=created_at&order=asc">&#8593;</a>
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=created_at&order=desc">&#8595;</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Trusted? 
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=trusted&order=asc">&#8593;</a>
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=trusted&order=desc">&#8595;</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Notes 
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=notes&order=asc">&#8593;</a>
                            <a class="ml-1 hover:text-lime-700" href="/admin/users?field=notes&order=desc">&#8595;</a>
                        </th>
                        <th class="px-4 py-3 font-medium">Edit</th>
                        <th class="px-4 py-3 font-medium">Delete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($users as $user)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-3 text-lime-700 font-medium"><a class="hover:underline" href="/profile/{{ $user->name }}">{{ $user->name }}</a></td>
                        <td class="px-4 py-3 text-zinc-700">
                            @foreach ($user->user_roles as $role)
                                <span class="inline-flex rounded-md border border-zinc-200 bg-white px-2 py-0.5 text-xs mr-1 mb-1">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-zinc-700">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $user->created_at->DiffForHumans() }}</td>
                        <td class="px-4 py-3">
                            @if($user->trusted)
                                <span class="inline-flex items-center rounded-full bg-lime-50 px-2 py-0.5 text-xs font-medium text-lime-700 ring-1 ring-inset ring-lime-600/20">Yes</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 ring-1 ring-inset ring-zinc-300">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700">{{ $user->notes }}</td>
                        <td class="px-4 py-3">
                            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-50" href="/profile/{{ $user->name_slug }}/edit" role="button">
                                Edit
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <form action="/admin/users/{{ $user->id}}" method="post" onsubmit="return confirm('Do you really want to delete this user? ');">
                                @csrf
                                @method('DELETE')
                                @if ($user->lock === 1)
                                    <button class="inline-flex items-center gap-2 cursor-not-allowed rounded-md bg-orange-200 px-2.5 py-1.5 text-xs font-semibold text-orange-800" type="submit" disabled>Locked</button>
                                @else
                                    <button class="inline-flex items-center gap-2 rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-700" type="submit">Delete</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-4 border-t border-zinc-200">
            <div class="max-w-lg mx-auto">
                {{ $users->appends(Request::except('page'))->links() }}
            </div>
        </div>
    </section>
</div>

@endsection