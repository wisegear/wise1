

@extends('layouts.admin')

@section('content')
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Add Postcode</h1>

        <form action="{{ route('admin.postcodes.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="postcode" class="block text-sm font-medium text-gray-700">Postcode</label>
                <input type="text" name="postcode" id="postcode"
                       value="{{ old('postcode') }}"
                       class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200"
                       required>
                @error('postcode') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category" id="category"
                        class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200"
                        required>
                    <option value="">-- Select Category --</option>
                    <option value="Prime Central" {{ old('category') == 'Prime Central' ? 'selected' : '' }}>Prime Central</option>
                    <option value="Ultra Prime" {{ old('category') == 'Ultra Prime' ? 'selected' : '' }}>Ultra Prime</option>
                </select>
                @error('category') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="4"
                          class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:ring focus:ring-blue-200">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('admin.postcodes.index') }}"
                   class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
@endsection