<x-layouts.app :title="__('Create Declaration')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create Declaration') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Create a new transport posting declaration') }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Declarations') }}
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.store') }}" class="space-y-8">
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
                                    <option value="{{ $driver['driverId'] }}" {{ old('driverId') == $driver['driverId'] ? 'selected' : '' }}>
                                        {{ trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="driverId" />
                        </x-field>

                        <!-- Posting Countries (Multiple Selection) -->
                        <x-field class="md:col-span-2">
                            <div class="flex items-center justify-between">
                                <x-label>{{ __('Posting Countries') }} <span class="text-red-500">*</span></x-label>
                                <div class="flex space-x-2">
                                    <button type="button" onclick="selectAllCountries()" class="text-xs text-blue-600 hover:text-blue-800">{{ __('Select All') }}</button>
                                    <button type="button" onclick="clearAllCountries()" class="text-xs text-gray-600 hover:text-gray-800">{{ __('Clear All') }}</button>
                                </div>
                            </div>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4" id="countries-container">
                                @foreach($countries as $code => $name)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="declarationPostingCountries[]"
                                               value="{{ $code }}"
                                               class="country-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ in_array($code, old('declarationPostingCountries', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $code }} - {{ __($name) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Select one or more countries. A separate declaration will be created for each selected country.') }}</p>
                            <x-error for="declarationPostingCountries" />
                        </x-field>

                        <!-- Start Date -->
                        <x-field>
                            <x-label for="declarationStartDate">{{ __('Start Date') }} <span class="text-red-500">*</span></x-label>
                            <input type="date" name="declarationStartDate" id="declarationStartDate" value="{{ old('declarationStartDate') }}" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="declarationStartDate" />
                        </x-field>

                        <!-- End Date -->
                        <x-field>
                            <x-label for="declarationEndDate">{{ __('End Date') }} <span class="text-red-500">*</span></x-label>
                            <input type="date" name="declarationEndDate" id="declarationEndDate" value="{{ old('declarationEndDate') }}" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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
                                @foreach($operationTypes as $key => $value)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="declarationOperationType[]"
                                               value="{{ $key }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ in_array($key, old('declarationOperationType', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __($value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-error for="declarationOperationType" />
                        </x-field>

                        <!-- Transport Types -->
                        <x-field>
                            <x-label>{{ __('Transport Types') }} <span class="text-red-500">*</span></x-label>
                            <div class="mt-2 space-y-2">
                                @foreach($transportTypes as $key => $value)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="declarationTransportType[]"
                                               value="{{ $key }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ in_array($key, old('declarationTransportType', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __($value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-error for="declarationTransportType" />
                        </x-field>

                        <!-- Vehicle Plate Numbers -->
                        <x-field>
                            <x-label for="vehiclePlate1">{{ __('Vehicle Plate Numbers') }} <span class="text-red-500">*</span></x-label>
                            <div class="space-y-2" x-data="{
                                plates: {{ old('declarationVehiclePlateNumber') ? json_encode(old('declarationVehiclePlateNumber')) : json_encode(['']) }},
                                handleAutoPopulate(event) {
                                    this.plates = event.detail.plates;
                                }
                            }"
                            @auto-populate-plates="handleAutoPopulate"
                            id="plate-container">
                                <template x-for="(plate, index) in plates" :key="index">
                                    <div class="flex items-center space-x-2">
                                        <select x-bind:name="`declarationVehiclePlateNumber[${index}]`"
                                                x-model="plates[index]"
                                                x-bind:required="index === 0"
                                                class="flex-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">{{ __('Select Vehicle Plate') }}</option>
                                            @foreach($trucks as $truck)
                                                <option value="{{ $truck->plate }}">{{ $truck->plate }} - {{ $truck->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button"
                                                x-show="plates.length > 1"
                                                @click="plates.splice(index, 1)"
                                                class="text-red-600 hover:text-red-800 px-2">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                                <div class="flex space-x-2">
                                    <button type="button"
                                            @click="plates.push('')"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                        + {{ __('Add Another Vehicle') }}
                                    </button>
                                    <button type="button"
                                            id="auto-populate-btn"
                                            onclick="autoPopulatePlates()"
                                            class="text-green-600 hover:text-green-800 text-sm"
                                            style="display: none;">
                                        🚛 {{ __('Auto-populate from assigned trucks') }}
                                    </button>
                                </div>
                            </div>
                            <x-error for="declarationVehiclePlateNumber" />
                        </x-field>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Contact Information') }} <span class="text-sm text-gray-500">({{ __('Optional') }})</span></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Transport Manager Checkbox -->
                        <x-field class="md:col-span-2">
                            <input type="hidden" name="otherContactAsTransportManager" value="0">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       name="otherContactAsTransportManager"
                                       value="1"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       {{ old('otherContactAsTransportManager') ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Contact is Transport Manager') }}</span>
                            </label>
                        </x-field>

                        <!-- First Name -->
                        <x-field>
                            <x-label for="otherContactFirstName">{{ __('Contact First Name') }}</x-label>
                            <input type="text" name="otherContactFirstName" id="otherContactFirstName" value="{{ old('otherContactFirstName') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="otherContactFirstName" />
                        </x-field>

                        <!-- Last Name -->
                        <x-field>
                            <x-label for="otherContactLastName">{{ __('Contact Last Name') }}</x-label>
                            <input type="text" name="otherContactLastName" id="otherContactLastName" value="{{ old('otherContactLastName') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="otherContactLastName" />
                        </x-field>

                        <!-- Email -->
                        <x-field>
                            <x-label for="otherContactEmail">{{ __('Contact Email') }}</x-label>
                            <input type="email" name="otherContactEmail" id="otherContactEmail" value="{{ old('otherContactEmail') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="otherContactEmail" />
                        </x-field>

                        <!-- Phone -->
                        <x-field>
                            <x-label for="otherContactPhone">{{ __('Contact Phone') }}</x-label>
                            <input type="tel" name="otherContactPhone" id="otherContactPhone" value="{{ old('otherContactPhone') }}" placeholder="+32567234534" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <x-error for="otherContactPhone" />
                        </x-field>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <x-button type="submit" class="bg-blue-600 hover:bg-blue-700">
                        {{ __('Create Declaration') }}
                    </x-button>
                </div>
            </x-form>
        </div>
    </div>

    <script>
        let currentSelectedDriver = null;
        let availableTruckPlates = [];

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

        // Country selection functions
        function selectAllCountries() {
            const checkboxes = document.querySelectorAll('.country-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function clearAllCountries() {
            const checkboxes = document.querySelectorAll('.country-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</x-layouts.app>