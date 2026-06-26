<x-layouts.app :title="__('Edit Declaration')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Declaration') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Declaration') }} #{{ substr($declaration['declarationId'] ?? '', 0, 8) }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('declarations.show', $declaration['declarationId']) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('View Declaration') }}
                </a>
                <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Declarations') }}
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.update', $declaration['declarationId']) }}" class="space-y-8">
                @method('PUT')

                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Basic Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Driver Selection -->
                        <x-field>
                            <x-label for="driverId">{{ __('Driver') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="driverId" id="driverId" required>
                                <option value="">{{ __('Select Driver') }}</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver['driverId'] }}"
                                            {{ (old('driverId', $declaration['driverId'] ?? '') == $driver['driverId']) ? 'selected' : '' }}>
                                        {{ trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="driverId" />
                        </x-field>

                        <!-- Posting Country -->
                        <x-field>
                            <x-label for="declarationPostingCountry">{{ __('Posting Country') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="declarationPostingCountry" id="declarationPostingCountry" required>
                                <option value="">{{ __('Select Country') }}</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}"
                                            {{ (old('declarationPostingCountry', $declaration['declarationPostingCountry'] ?? '') == $code) ? 'selected' : '' }}>
                                        {{ __($name) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="declarationPostingCountry" />
                        </x-field>

                        <!-- Start Date -->
                        <x-field>
                            <x-label for="declarationStartDate">{{ __('Start Date') }} <span class="text-red-500">*</span></x-label>
                            <input type="date" name="declarationStartDate" id="declarationStartDate" value="{{ old('declarationStartDate', $declaration['declarationStartDate'] ?? '') }}" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="declarationStartDate" />
                        </x-field>

                        <!-- End Date -->
                        <x-field>
                            <x-label for="declarationEndDate">{{ __('End Date') }} <span class="text-red-500">*</span></x-label>
                            <input type="date" name="declarationEndDate" id="declarationEndDate" value="{{ old('declarationEndDate', $declaration['declarationEndDate'] ?? '') }}" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="declarationEndDate" />
                        </x-field>
                    </div>
                </div>

                <!-- Operation Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Operation Information') }}</h3>
                    <div class="space-y-6">
                        <!-- Operation Types -->
                        <x-field>
                            <x-label>{{ __('Operation Types') }} <span class="text-red-500">*</span></x-label>
                            <div class="mt-2 space-y-2">
                                @php
                                    $selectedOperationTypes = old('declarationOperationType', $declaration['declarationOperationType'] ?? []);
                                @endphp
                                @foreach($operationTypes as $key => $value)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="declarationOperationType[]"
                                               value="{{ $key }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ in_array($key, $selectedOperationTypes) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __($value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-error for="declarationOperationType" />
                        </x-field>

                        <!-- Transport Types -->
                        @php
                            $selectedTransportTypes = old('declarationTransportType', $declaration['declarationTransportType'] ?? []);
                            $existingPassenger = old('declarationVehiclePlateNumber', $declaration['declarationVehiclePlateNumber'] ?? []);
                            $existingLight = old('declarationVehiclePlateNumberLight', $declaration['declarationVehiclePlateNumberLight'] ?? []);
                            $existingHeavy = old('declarationVehiclePlateNumberHeavy', $declaration['declarationVehiclePlateNumberHeavy'] ?? []);
                        @endphp
                        <div x-data="{ types: @js($selectedTransportTypes) }">
                            <x-field>
                                <x-label>{{ __('Transport Types') }} <span class="text-red-500">*</span></x-label>
                                <div class="mt-2 space-y-2">
                                    @foreach($transportTypes as $key => $value)
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   name="declarationTransportType[]"
                                                   value="{{ $key }}"
                                                   x-model="types"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __($value) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-error for="declarationTransportType" />
                            </x-field>

                            @if(($plateGroups['source'] ?? '') === 'local')
                                <div class="mt-3 p-3 rounded border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800 text-xs text-yellow-800 dark:text-yellow-300">
                                    ⚠️ {{ __('Could not load the IMI plate-number register, showing local trucks instead.') }}
                                </div>
                            @endif

                            <!-- Heavy goods -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_GOODS')" x-cloak>
                                <x-label>{{ __('Heavy goods vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['goods_heavy']) }})</span></x-label>
                                @if(count($plateGroups['goods_heavy']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['goods_heavy'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumberHeavy[]" value="{{ $p['plate'] }}"
                                                    class="plate-heavy rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], $existingHeavy) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No heavy-goods plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumberHeavy" />
                            </div>

                            <!-- Light goods -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_GOODS')" x-cloak>
                                <x-label>{{ __('Light goods vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['goods_light']) }})</span></x-label>
                                @if(count($plateGroups['goods_light']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['goods_light'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumberLight[]" value="{{ $p['plate'] }}"
                                                    class="plate-light rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], $existingLight) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No light-goods plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumberLight" />
                            </div>

                            <!-- Passenger -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_PASSENGERS')" x-cloak>
                                <x-label>{{ __('Passenger vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['passengers']) }})</span></x-label>
                                @if(count($plateGroups['passengers']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['passengers'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumber[]" value="{{ $p['plate'] }}"
                                                    class="plate-passenger rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], $existingPassenger) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No passenger plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumber" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('declarations.show', $declaration['declarationId']) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <x-button type="submit" class="bg-green-600 hover:bg-green-700">
                        {{ __('Update Declaration') }}
                    </x-button>
                </div>
            </x-form>
        </div>
    </div>

    <script>
        let currentSelectedDriver = null;
        let availableTruckPlates = [];

        // Initialize with current driver
        document.addEventListener('DOMContentLoaded', function() {
            const driverSelect = document.getElementById('driverId');
            if (driverSelect.value) {
                currentSelectedDriver = driverSelect.value;
                fetchDriverTruckPlates(driverSelect.value);
            }
        });

        // Listen for driver selection changes
        document.getElementById('driverId').addEventListener('change', function() {
            const driverId = this.value;
            const autoPopulateBtn = document.getElementById('auto-populate-btn');

            if (driverId && driverId !== currentSelectedDriver) {
                currentSelectedDriver = driverId;
                fetchDriverTruckPlates(driverId);
            } else {
                currentSelectedDriver = null;
                availableTruckPlates = [];
                autoPopulateBtn.style.display = 'none';
            }
        });

        function fetchDriverTruckPlates(driverId) {
            fetch(`{{ url('declarations/driver') }}/${driverId}/truck-plates`)
                .then(response => response.json())
                .then(data => {
                    if (data.plates && data.plates.length > 0) {
                        availableTruckPlates = data.plates;
                        document.getElementById('auto-populate-btn').style.display = 'inline-block';
                    } else {
                        availableTruckPlates = [];
                        document.getElementById('auto-populate-btn').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching truck plates:', error);
                    availableTruckPlates = [];
                    document.getElementById('auto-populate-btn').style.display = 'none';
                });
        }

        function autoPopulatePlates() {
            if (availableTruckPlates.length === 0) {
                alert('{{ __("No assigned trucks found for this driver.") }}');
                return;
            }

            // Dispatch a custom event to trigger Alpine.js update
            const plateContainer = document.getElementById('plate-container');

            // Try to access Alpine component directly
            try {
                // Use Alpine.js event system to update the plates
                plateContainer.dispatchEvent(new CustomEvent('auto-populate-plates', {
                    detail: { plates: availableTruckPlates }
                }));
            } catch (error) {
                console.error('Error with Alpine.js event:', error);

                // Fallback: direct manipulation
                const firstSelect = plateContainer.querySelector('select[name*="declarationVehiclePlateNumber"]');
                if (firstSelect) {
                    // Clear all existing values
                    const allSelects = plateContainer.querySelectorAll('select[name*="declarationVehiclePlateNumber"]');
                    allSelects.forEach(select => select.value = '');

                    // Set the first plate
                    firstSelect.value = availableTruckPlates[0] || '';

                    // Trigger change events to notify Alpine.js
                    firstSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Show success message
            const message = `{{ __("Auto-populated") }} ${availableTruckPlates.length} {{ __("truck plate(s) for selected driver.") }}`;

            // Create a temporary notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);

            // Remove notification after 3 seconds
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        }
    </script>
</x-layouts.app>