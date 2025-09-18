<x-layouts.app :title="__('Add Truck')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add Truck') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Add a new vehicle to your fleet') }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Trucks') }}
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('trucks.store') }}" class="space-y-6">
                @csrf

                <!-- Truck Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Truck Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Truck Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Truck Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Volvo FH, Mercedes Actros" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Plate Number -->
                        <div>
                            <label for="plate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Plate Number') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="plate" id="plate" value="{{ old('plate') }}" required maxlength="20" placeholder="e.g. BG 1234 AA" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('plate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label for="capacity_tons" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Capacity (tons)') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="capacity_tons" id="capacity_tons" value="{{ old('capacity_tons') }}" required min="0.01" max="999999.99" step="0.01" placeholder="e.g. 40.00" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('capacity_tons')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Status') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="status" id="status" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($statuses as $key => $value)
                                    <option value="{{ $key }}" {{ old('status', 'Available') == $key ? 'selected' : '' }}>
                                        {{ __($value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Countries -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Operating Countries') }}</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Select countries where this truck operates') }}
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                            @foreach($countries as $code => $name)
                                <label class="flex items-center">
                                    <input type="checkbox" name="countries[]" value="{{ $code }}"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ in_array($code, old('countries', [])) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __($name) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('countries')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('countries.*')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Add Truck') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>