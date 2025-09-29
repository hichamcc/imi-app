<x-layouts.app :title="__('Bulk Update Declarations - Review Declarations')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('Step 3: Review and select declarations to :action plates', ['action' => $action === 'add' ? __('add') : __('remove')]) }}
                </p>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm">✓</div>
                        <span class="text-sm font-medium text-green-600">{{ __('Select Drivers') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-green-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm">✓</div>
                        <span class="text-sm font-medium text-green-600">{{ __('Choose Action') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-blue-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">3</div>
                        <span class="text-sm font-medium text-blue-600">{{ __('Review') }}</span>
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

        <!-- Action Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-4">
                @if($action === 'add')
                    <x-phosphor-plus class="w-8 h-8 text-green-600" />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Add Plate Numbers') }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('New plates will be added to the selected declarations while preserving existing plates') }}</p>
                    </div>
                @else
                    <x-phosphor-minus class="w-8 h-8 text-red-600" />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Remove Plate Numbers') }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('Selected plates will be removed from the selected declarations') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Declarations List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.bulk-update.step4') }}">
                <!-- Pass previous selections -->
                @foreach($selectedDriverIds as $driverId)
                    <input type="hidden" name="selected_drivers[]" value="{{ $driverId }}">
                @endforeach
                <input type="hidden" name="action" value="{{ $action }}">

                <!-- Stats Display -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('Declaration Statistics') }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">{{ __('Updated as you select declarations') }}</p>
                        </div>
                        <div class="flex space-x-6">
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-800 dark:text-blue-200" id="selected-declarations-count">0</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">{{ __('Declarations Selected') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-800 dark:text-blue-200">{{ count($declarations) }}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">{{ __('Total Available') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selection Controls -->
                <div class="mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="flex space-x-2">
                        <button type="button" id="select-all-declarations" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                            {{ __('Select All') }}
                        </button>
                        <button type="button" id="clear-all-declarations" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            {{ __('Clear All') }}
                        </button>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('All declarations are selected by default') }}
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ __('Submitted Declarations') }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ count($declarations) }} {{ __('found') }})</span>
                </h3>

                @if(count($declarations) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-6 py-3">
                                        <input type="checkbox" id="select-all-checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Declaration') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Driver') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Country') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Period') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Current Plates') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($declarations as $declaration)
                                    <tr class="declaration-row hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                name="selected_declarations[]"
                                                value="{{ $declaration['declarationId'] ?? '' }}"
                                                class="declaration-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                checked
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ substr($declaration['declarationId'] ?? '', 0, 8) }}...
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                    {{ $declaration['declarationStatus'] ?? 'SUBMITTED' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $declaration['driverLatinFullName'] ?? ($declaration['driverLatinFirstName'] ?? '') . ' ' . ($declaration['driverLatinLastName'] ?? '') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $declaration['declarationPostingCountry'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <div>{{ $declaration['declarationStartDate'] ?? 'N/A' }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ $declaration['declarationEndDate'] ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $plates = $declaration['declarationVehiclePlateNumber'] ?? [];
                                            @endphp
                                            @if(count($plates) > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($plates as $plate)
                                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                                            {{ $plate }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('No plates') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <x-phosphor-file-text class="w-12 h-12 mx-auto" />
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ __('No submitted declarations found for the selected drivers') }}</p>
                        <a href="{{ route('declarations.bulk-update.index') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('Select Different Drivers') }}
                        </a>
                    </div>
                @endif

                @if(count($declarations) > 0)
                    <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                        <a href="{{ route('declarations.bulk-update.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            ← {{ __('Back to Driver Selection') }}
                        </a>
                        <x-button type="submit" id="continue-button" class="bg-blue-600 hover:bg-blue-700">
                            {{ __('Continue to Plate Selection') }} →
                        </x-button>
                    </div>
                @endif
            </x-form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.declaration-checkbox');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const selectedCountElement = document.getElementById('selected-declarations-count');
            const continueButton = document.getElementById('continue-button');
            const selectAllButton = document.getElementById('select-all-declarations');
            const clearAllButton = document.getElementById('clear-all-declarations');

            function updateStats() {
                const checkedCount = document.querySelectorAll('.declaration-checkbox:checked').length;
                selectedCountElement.textContent = checkedCount;

                // Update continue button state
                continueButton.disabled = checkedCount === 0;

                // Update select all checkbox state
                selectAllCheckbox.checked = checkedCount === checkboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }

            // Individual checkbox change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateStats);
            });

            // Select all checkbox
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateStats();
            });

            // Select all button
            selectAllButton.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateStats();
            });

            // Clear all button
            clearAllButton.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateStats();
            });

            // Initial stats
            updateStats();
        });
    </script>
</x-layouts.app>