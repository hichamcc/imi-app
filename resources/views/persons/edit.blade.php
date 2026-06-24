<x-layouts.app :title="__('Edit Person')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Person') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $person->full_name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('persons.show', $person->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">{{ __('View') }}</a>
                <a href="{{ route('persons.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back') }}</a>
            </div>
        </div>

        <form method="POST" action="{{ route('persons.update', $person->id) }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            @csrf @method('PUT')
            @include('persons._form', ['person' => $person])

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('persons.show', $person->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium">{{ __('Cancel') }}</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">{{ __('Update Person') }}</button>
            </div>
        </form>
    </div>
</x-layouts.app>
