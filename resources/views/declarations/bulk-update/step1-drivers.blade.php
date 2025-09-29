<x-layouts.app :title="__('Bulk Update Declarations - Select Drivers')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Step 1: Select drivers whose submitted declarations you want to update') }}</p>
            </div>
            <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Cancel') }}
            </a>
        </div>

        <!-- Progress Indicator -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                        <span class="text-sm font-medium text-blue-600">{{ __('Select Drivers') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                        <span class="text-sm text-gray-500">{{ __('Choose Action') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">3</div>
                        <span class="text-sm text-gray-500">{{ __('Review') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">4</div>
                        <span class="text-sm text-gray-500">{{ __('Select Plates') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">5</div>
                        <span class="text-sm text-gray-500">{{ __('Confirm') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">6</div>
                        <span class="text-sm text-gray-500">{{ __('Process') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Driver Selection Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.bulk-update.step2') }}">
                <!-- Stats Display -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('Selection Statistics') }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">{{ __('Updated as you select drivers') }}</p>
                        </div>
                        <div class="flex space-x-6">
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-800 dark:text-blue-200" id="selected-drivers-count">0</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">{{ __('Drivers Selected') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-800 dark:text-blue-200" id="total-declarations-count">0</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">{{ __('Submitted Declarations') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <x-input
                                type="text"
                                name="search"
                                id="driver-search"
                                placeholder="{{ __('Search drivers by name...') }}"
                                class="w-full"
                            />
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" id="select-all-drivers" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                                {{ __('Select All') }}
                            </button>
                            <button type="button" id="clear-all-drivers" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                                {{ __('Clear All') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Drivers List -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ __('Available Drivers') }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ count($driversWithStats) }} {{ __('total') }})</span>
                    </h3>

                    @if(count($driversWithStats) > 0)
                        <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-1">
                                @foreach($driversWithStats as $driverData)
                                    @php
                                        $driver = $driverData['driver'];
                                        $submittedCount = $driverData['submitted_count'];
                                    @endphp
                                    <label class="driver-item flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600"
                                           data-submitted-count="{{ $submittedCount }}"
                                           data-driver-name="{{ strtolower(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}">
                                        <input
                                            type="checkbox"
                                            name="selected_drivers[]"
                                            value="{{ $driver['driverId'] ?? '' }}"
                                            class="driver-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3"
                                            data-submitted-count="{{ $submittedCount }}"
                                        />
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ ($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ __('ID:') }} {{ substr($driver['driverId'] ?? '', 0, 8) }}...
                                            </div>
                                            <div class="text-sm">
                                                @if($submittedCount > 0)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                        {{ $submittedCount }} {{ __('submitted declarations') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ __('No submitted declarations') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <x-phosphor-users class="w-12 h-12 mx-auto" />
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No drivers found') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Select at least one driver with submitted declarations to continue') }}
                    </div>
                    <x-button type="submit" id="continue-button" class="bg-blue-600 hover:bg-blue-700" disabled>
                        {{ __('Continue to Action Selection') }} →
                    </x-button>
                </div>
            </x-form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.driver-checkbox');
            const continueButton = document.getElementById('continue-button');
            const selectedCountElement = document.getElementById('selected-drivers-count');
            const totalDeclarationsElement = document.getElementById('total-declarations-count');
            const searchInput = document.getElementById('driver-search');
            const selectAllButton = document.getElementById('select-all-drivers');
            const clearAllButton = document.getElementById('clear-all-drivers');

            function updateStats() {
                const selectedCheckboxes = document.querySelectorAll('.driver-checkbox:checked');
                const selectedCount = selectedCheckboxes.length;
                let totalDeclarations = 0;

                selectedCheckboxes.forEach(checkbox => {
                    totalDeclarations += parseInt(checkbox.dataset.submittedCount) || 0;
                });

                selectedCountElement.textContent = selectedCount;
                totalDeclarationsElement.textContent = totalDeclarations;

                // Enable/disable continue button
                continueButton.disabled = selectedCount === 0 || totalDeclarations === 0;
            }

            function filterDrivers() {
                const searchTerm = searchInput.value.toLowerCase();
                const driverItems = document.querySelectorAll('.driver-item');

                driverItems.forEach(item => {
                    const driverName = item.dataset.driverName;
                    const shouldShow = driverName.includes(searchTerm);
                    item.style.display = shouldShow ? 'flex' : 'none';
                });
            }

            // Add event listeners
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateStats);
            });

            searchInput.addEventListener('input', filterDrivers);

            selectAllButton.addEventListener('click', function() {
                const visibleCheckboxes = Array.from(checkboxes).filter(cb =>
                    cb.closest('.driver-item').style.display !== 'none'
                );
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateStats();
            });

            clearAllButton.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateStats();
            });

            // Initial stats update
            updateStats();
        });
    </script>
</x-layouts.app>