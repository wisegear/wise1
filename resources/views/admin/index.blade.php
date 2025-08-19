@extends('layouts.admin')

@section('content')

<div class="w-1/2 mx-auto text-center mb-10">
	<h1 class="text-2xl font-bold">Dashboard</h1>
	<p class="text-gray-500">Admin Dashboard. From here you can manage the Users, Blog, Articles, Support tickets and other elements as required.</p>
</div>

<div class="grid grid-cols-3 gap-20">

	<div class="border bg-gray-100 rounded p-4 shadow-lg">
		<div class="border-b border-b-gray-400">
			<h2 class="text-xl font-bold text-center pb-2">Users <span class="text-blue-700 text-center">{{ $users->count() }}</span></h2>		
		</div>
		<div class="mt-5 text-center text-sm text-gray-500">
			<p class="">Banned: <span class="">{{ $users_banned }}</span></p>
			<p class="">Pending: <span class="">{{ $users_pending }}</span></p>
			<p class="">Members: <span class="">{{ $users_active }}</span></p>
            <p class="">Admin: <span class="">{{ $users_admin }}</span></p>
		</div>
	</div>

	<div class="border bg-gray-100 rounded p-4 shadow-lg">
		<div class="border-b border-b-gray-400">
			<h2 class="text-xl font-bold text-center pb-2">Blog Posts <span class="text-blue-700 text-center">{{ $blogposts->count() }}</span></h2>
		</div>
		<div class="mt-5 text-center text-sm text-gray-500">
			<p class="">Not published: <span class=""></span>{{ $blogunpublished->count() }}</p>
		</div>
	</div>

</div>
 
@endsection