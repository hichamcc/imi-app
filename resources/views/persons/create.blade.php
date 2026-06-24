<x-layouts.app :title="__('Add Person')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add Person') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Create a new HR record') }}</p>
            </div>
            <a href="{{ route('persons.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back') }}</a>
        </div>

        <form method="POST" action="{{ route('persons.store') }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            @csrf
            @include('persons._form')

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="also_create_in_imi" value="1" {{ old('also_create_in_imi') ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('Also create this person as a driver in IMI') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Requires all IMI driver fields: DOB, license number, document type/number/issuing country, full address, contract start date, applicable law. If any field is missing, the person will be saved locally and you can retry the sync later from their profile.') }}
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('persons.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium">{{ __('Cancel') }}</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">{{ __('Create Person') }}</button>
            </div>
        </form>
    </div>
</x-layouts.app>
