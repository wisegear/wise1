@extends('layouts.app')

@section('content')

    <!-- Viewing the user profile -->
    <div class="max-w-3xl mx-auto">
        <!-- Display name and social profiles -->
        <div class="text-center mb-4">
            <p class="font-semibold mb-2 text-lg">You're viewing the user profile for <span class="text-slate-500">{{ $user->name }}</span></p>
            <div class="flex space-x-4 justify-center my-6">
                <a href="{{ $user->x }}" class="border border-gray-500 px-2 py-1 rounded-full hover:bg-lime-100"><i class="fa-brands fa-x-twitter w-4 h-4"></i></a>					
                <a href="{{ $user->facebook }}" class="border border-gray-500 px-2 py-1 rounded-full hover:bg-lime-100"><i class="fa-brands fa-facebook-f text-[#1877f2] w-4 h-4"></i></a>					
                <a href="{{ $user->linkedin }}" class="border border-gray-500 px-2 py-1 rounded-full hover:bg-lime-100"><i class="fa-brands fa-linkedin-in text-[#0a66c2] w-4 h-4"></i></a>
            </div>				
        </div>
        <!-- Display profile and other user details -->
        <div class="flex flex-col md:flex-row md:justify-evenly items-center md:space-y-0 space-y-6 my-6">
            <!-- Profile Avatar -->
            <div class="">
                <img class="rounded-full border p-1" src="{{ asset("/assets/images/avatars/$user->avatar") }}">
            </div>
            <div class="dark:text-white">
                <ul class="text-sm space-y-1 flex flex-col justify-center">
                    <!-- Website -->
                    <li>
                        <i class="fa-brands fa-internet-explorer mr-2"></i>				
                        <a class="text-teal-700" href="{{ $user->website }}">{{ $user->website }}</a>
                    </li>       
                    <!-- Location -->            
                    <li>
                        <i class="fa-solid fa-globe mr-2"></i>				
                        {{ $user->location }}
                    </li>
                    <!-- Email -->
                    <li>
                        <i class="fa-solid fa-at mr-2"></i>	
                        <!-- is email shared publically -->
                        @if($user->email_visible === 0)
                            Not shared
                        @else				
                        {{ $user->email }}
                        @endif
                    </li>
                </ul>
            </div>
        </div>
        <!-- User Bio -->
        <div class="text-center">
            @if (empty($user->bio))
                <!-- If no user Bio -->
                <p class="border p-2">User has not provided any information about themselves.</p>
                @else
                    <!-- display user Bio -->
                    <p class="border rounded-md border-slate-200 shadow-lg p-4 text-gray-700 text-sm">{{ $user->bio }}</p>
            @endif
        </div>
            <!-- Edit Profile -->
            @if (Auth::user()->name_slug === $user->name_slug || Auth::user()->has_user_role('Admin'))
                <div class="my-6 text-center">
                    <form action="/profile/{{ $user->name_slug }}/edit" method="GET">
                        <button type="submit" class="standard-button">Edit Profile</button>
                    </form>
                </div>
            @endif
    </div>

@endsection