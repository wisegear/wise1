@extends('layouts.admin')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Prime & Ultra Prime Postcodes</h1>
        <a href="{{ route('admin.postcodes.create') }}"
           class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
            + Add Postcode
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-2 border">Postcode</th>
                    <th class="px-4 py-2 border">Category</th>
                    <th class="px-4 py-2 border">Notes</th>
                    <th class="px-4 py-2 border w-32">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($postcodes as $postcode)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border font-mono">{{ $postcode->postcode }}</td>
                        <td class="px-4 py-2 border">{{ $postcode->category }}</td>
                        <td class="px-4 py-2 border max-w-xs truncate" title="{{ $postcode->notes }}">
                            {{ Str::limit($postcode->notes, 80) }}
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <a href="{{ route('admin.postcodes.edit', $postcode->id) }}"
                               class="px-2 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">Edit</a>
                            <form action="{{ route('admin.postcodes.destroy', $postcode->id) }}" method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Are you sure you want to delete this postcode?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-gray-500">No postcodes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection