<x-layouts.app :title="__('Bulk Update Declarations - Choose Action')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Step 2: Choose whether to add or remove plate numbers') }}</p>
            </div>
            <a href="{{ route('declarations.bulk-update.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Back to Driver Selection') }}
            </a>
        </div>

        <!-- Progress Indicator -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm">✓</div>
                        <span class="text-sm font-medium text-green-600">{{ __('Select Drivers') }}</span>
                    </div>
                    <div class="w-16 h-1 bg-blue-600 rounded"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                        <span class="text-sm font-medium text-blue-600">{{ __('Choose Action') }}</span>
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

        <!-- Selected Drivers Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Selected Drivers Summary') }}</h3>

            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ count($selectedDrivers) }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">{{ __('Drivers Selected') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $totalSubmittedDeclarations }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">{{ __('Total Submitted Declarations') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($selectedDrivers as $driverData)
                    @php
                        $driver = $driverData['driver'];
                        $submittedCount = $driverData['submitted_count'];
                    @endphp
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ ($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '') }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $submittedCount }} {{ __('submitted declarations') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Selection Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.bulk-update.step3') }}">
                <!-- Pass selected drivers -->
                @foreach($selectedDrivers as $driverData)
                    <input type="hidden" name="selected_drivers[]" value="{{ $driverData['driver']['driverId'] ?? '' }}">
                @endforeach

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('Choose Your Action') }}</h3>

                <!-- Action Selection -->
                <div class="space-y-4 mb-8">
                    <!-- Add Plates Option -->
                    <label class="action-option flex items-start p-6 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-green-300 dark:hover:border-green-700 cursor-pointer transition-colors">
                        <input type="radio" name="action" value="add" class="mt-1 mr-4 text-green-600 focus:ring-green-500">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <x-phosphor-plus class="w-6 h-6 text-green-600" />
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Add Plate Numbers') }}</span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ __('Add new plate numbers to the selected declarations. Existing plates will be preserved, and new ones will be added to the list.') }}
                            </p>
                            <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded">
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    <strong>{{ __('Example:') }}</strong>
                                    {{ __('If a declaration has plates [ABC123, DEF456] and you add [XYZ789], the result will be [ABC123, DEF456, XYZ789]') }}
                                </p>
                            </div>
                        </div>
                    </label>

                    <!-- Remove Plates Option -->
                    <label class="action-option flex items-start p-6 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-red-300 dark:hover:border-red-700 cursor-pointer transition-colors">
                        <input type="radio" name="action" value="remove" class="mt-1 mr-4 text-red-600 focus:ring-red-500">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <x-phosphor-minus class="w-6 h-6 text-red-600" />
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Remove Plate Numbers') }}</span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ __('Remove specific plate numbers from the selected declarations. Only the selected plates will be removed, others will remain.') }}
                            </p>
                            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    <strong>{{ __('Example:') }}</strong>
                                    {{ __('If a declaration has plates [ABC123, DEF456, XYZ789] and you remove [DEF456], the result will be [ABC123, XYZ789]') }}
                                </p>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('declarations.bulk-update.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        ← {{ __('Back to Driver Selection') }}
                    </a>
                    <x-button type="submit" id="continue-button" class="bg-blue-600 hover:bg-blue-700" disabled>
                        {{ __('Continue to Declaration Review') }} →
                    </x-button>
                </div>
            </x-form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="action"]');
            const continueButton = document.getElementById('continue-button');
            const actionOptions = document.querySelectorAll('.action-option');

            function updateActionSelection() {
                const selectedAction = document.querySelector('input[name="action"]:checked');
                continueButton.disabled = !selectedAction;

                // Update visual feedback
                actionOptions.forEach(option => {
                    const radio = option.querySelector('input[type="radio"]');
                    if (radio.checked) {
                        if (radio.value === 'add') {
                            option.classList.add('border-green-400', 'bg-green-50', 'dark:bg-green-900/10');
                            option.classList.remove('border-gray-200', 'dark:border-gray-600');
                        } else {
                            option.classList.add('border-red-400', 'bg-red-50', 'dark:bg-red-900/10');
                            option.classList.remove('border-gray-200', 'dark:border-gray-600');
                        }
                    } else {
                        option.classList.remove('border-green-400', 'bg-green-50', 'dark:bg-green-900/10',
                                                'border-red-400', 'bg-red-50', 'dark:bg-red-900/10');
                        option.classList.add('border-gray-200', 'dark:border-gray-600');
                    }
                });
            }

            radioButtons.forEach(radio => {
                radio.addEventListener('change', updateActionSelection);
            });

            // Initial state
            updateActionSelection();
        });
    </script>
</x-layouts.app>