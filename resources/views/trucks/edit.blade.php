<x-layouts.app :title="__('Edit Truck')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Truck') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $truck->name }} - {{ $truck->plate }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('trucks.show', $truck->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('View Truck') }}
                </a>
                <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Trucks') }}
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('trucks.update', $truck->id) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Truck Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Truck Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Truck Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Truck Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $truck->name) }}" required maxlength="100" placeholder="e.g. Volvo FH, Mercedes Actros" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Plate Number -->
                        <div>
                            <label for="plate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Plate Number') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="plate" id="plate" value="{{ old('plate', $truck->plate) }}" required maxlength="20" placeholder="e.g. BG 1234 AA" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('plate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label for="capacity_tons" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Capacity (tons)') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="capacity_tons" id="capacity_tons" value="{{ old('capacity_tons', $truck->capacity_tons) }}" required min="0.01" max="999999.99" step="0.01" placeholder="e.g. 40.00" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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
                                    <option value="{{ $key }}" {{ old('status', $truck->status) == $key ? 'selected' : '' }}>
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

                <!-- Current Assignments Warning -->
                @if($truck->activeAssignments->count() > 0)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    {{ __('Active Assignments') }}
                                </h3>
                                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                    {{ __('This truck has') }} {{ $truck->activeAssignments->count() }} {{ __('active driver assignment(s). Changing the status may affect assignment availability.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('trucks.show', $truck->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Update Truck') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>