<x-layouts.app :title="__('Bulk Update - Step 4: Select Plates')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Step 4: Select plate numbers to') }} <span class="font-semibold {{ $action === 'add' ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($action) }}</span></p>
            </div>
            <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Cancel') }}
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Step 4: Select Plate Numbers') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Choose which plate numbers to') }} <span class="font-semibold {{ $action === 'add' ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($action) }}</span> {{ __('for the selected declarations.') }}
                    </p>
                </div>

                <!-- Progress indicator -->
                <div class="mb-6">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ __('Progress') }}</span>
                        <span>4/6</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 66.67%"></div>
                    </div>
                </div>

                <!-- Current selection summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Selected Drivers') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ count($selectedDriverIds) }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-900 dark:text-green-100">{{ __('Selected Declarations') }}</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($selectedDeclarationIds) }}</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-purple-900 dark:text-purple-100">{{ __('Action') }}</div>
                        <div class="text-2xl font-bold {{ $action === 'add' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ strtoupper($action) }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('declarations.bulk-update.step5') }}">
                    @csrf

                    <!-- Pass through previous selections -->
                    @foreach($selectedDriverIds as $driverId)
                        <input type="hidden" name="selected_drivers[]" value="{{ $driverId }}">
                    @endforeach

                    @foreach($selectedDeclarationIds as $declarationId)
                        <input type="hidden" name="selected_declarations[]" value="{{ $declarationId }}">
                    @endforeach

                    <input type="hidden" name="action" value="{{ $action }}">

                    <div class="space-y-6">
                        <!-- Available trucks section -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                                {{ __('Your Available Trucks') }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ count($trucks) }} {{ __('available') }})</span>
                            </h4>

                            @if($trucks->isEmpty())
                                <div class="text-center py-8">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <x-phosphor-truck class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No Available Trucks') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('You need to have available trucks to select plate numbers.') }}</p>
                                    <div class="mt-6">
                                        <a href="{{ route('trucks.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <x-phosphor-plus class="-ml-1 mr-2 h-5 w-5" />
                                            {{ __('Add New Truck') }}
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                    @foreach($trucks as $truck)
                                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
                                                        <x-phosphor-truck class="h-5 w-5 text-white" />
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <label for="plate_{{ $truck->id }}" class="cursor-pointer">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                            {{ $truck->plate }}
                                                        </p>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                            {{ $truck->name }}
                                                        </p>
                                                    </label>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <input type="checkbox"
                                                           id="plate_{{ $truck->id }}"
                                                           name="selected_plates[]"
                                                           value="{{ $truck->plate }}"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                           {{ in_array($truck->plate, old('selected_plates', [])) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Quick select actions -->
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <button type="button" onclick="selectAllPlates()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        {{ __('Select All') }}
                                    </button>
                                    <button type="button" onclick="deselectAllPlates()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        {{ __('Deselect All') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($action === 'remove' && !empty($currentPlatesData))
                        <!-- Current plates in declarations (for remove action) -->
                        <div class="border-t pt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                                {{ __('Current Plates in Selected Declarations') }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ __('plates that can be removed') }})</span>
                            </h4>

                            @php
                                $allCurrentPlates = collect($currentPlatesData)->flatten()->unique()->sort();
                            @endphp

                            @if($allCurrentPlates->isNotEmpty())
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg mb-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <x-phosphor-warning class="h-5 w-5 text-yellow-400" />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                                {{ __('These are the plate numbers currently in your selected declarations. You can choose which ones to remove.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach($allCurrentPlates as $plateNumber)
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 p-3 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox"
                                                       id="current_plate_{{ $loop->index }}"
                                                       name="selected_plates[]"
                                                       value="{{ $plateNumber }}"
                                                       class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                                       {{ in_array($plateNumber, old('selected_plates', [])) ? 'checked' : '' }}>
                                                <label for="current_plate_{{ $loop->index }}" class="text-sm font-medium text-gray-900 dark:text-white cursor-pointer">
                                                    {{ $plateNumber }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No plates found in the selected declarations.') }}</p>
                            @endif
                        </div>
                        @endif

                        <!-- Validation errors -->
                        @error('selected_plates')
                            <div class="rounded-md bg-red-50 dark:bg-red-900/50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-phosphor-x-circle class="h-5 w-5 text-red-400" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                                    </div>
                                </div>
                            </div>
                        @enderror

                        <!-- Selected plates counter -->
                        <div class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <x-phosphor-info class="h-5 w-5 text-blue-400" />
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    {{ __('Selected plates:') }} <span id="selected-count">0</span>
                                </span>
                            </div>
                        </div>

                        <!-- Navigation buttons -->
                        <div class="flex justify-between pt-6 border-t">
                            <a href="{{ route('declarations.bulk-update.step3') }}"
                               class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('Previous') }}
                            </a>

                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="continue-btn"
                                    disabled>
                                {{ __('Continue to Preview') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectAllPlates() {
            const checkboxes = document.querySelectorAll('input[name="selected_plates[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }

        function deselectAllPlates() {
            const checkboxes = document.querySelectorAll('input[name="selected_plates[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('input[name="selected_plates[]"]:checked');
            const count = checkboxes.length;

            document.getElementById('selected-count').textContent = count;
            document.getElementById('continue-btn').disabled = count === 0;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add change listeners to all checkboxes
            const checkboxes = document.querySelectorAll('input[name="selected_plates[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            // Initial count
            updateSelectedCount();
        });
    </script>
</x-layouts.app>