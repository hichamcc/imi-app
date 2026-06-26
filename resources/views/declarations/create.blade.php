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
                                    <option value="{{ $driver['driverId'] }}" {{ old('driverId', request('driverId')) == $driver['driverId'] ? 'selected' : '' }}>
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
                                    <button type="button" onclick="selectPresetCountries()" class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-2 py-1 rounded font-medium">{{ __('Select EU+') }}</button>
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

                        <!-- Transport Types + plates: grouped under one Alpine scope so the plate sections show/hide -->
                        <div class="md:col-span-2" x-data="{ types: @js(old('declarationTransportType', [])) }">
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
                                    ⚠️ {{ __('Could not load the IMI plate-number register, showing local trucks instead. The API will reject plates not registered with') }} <code>/plate-numbers</code>.
                                </div>
                            @endif

                            <!-- Heavy goods plates -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_GOODS')" x-cloak>
                                <div class="flex items-center justify-between">
                                    <x-label>{{ __('Heavy goods vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['goods_heavy']) }})</span></x-label>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="togglePlateGroup('heavy', true)" class="text-xs text-blue-600 hover:text-blue-800">{{ __('Select All') }}</button>
                                        <button type="button" onclick="togglePlateGroup('heavy', false)" class="text-xs text-gray-600 hover:text-gray-800">{{ __('Clear') }}</button>
                                    </div>
                                </div>
                                @if(count($plateGroups['goods_heavy']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['goods_heavy'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumberHeavy[]" value="{{ $p['plate'] }}"
                                                    class="plate-heavy rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], old('declarationVehiclePlateNumberHeavy', [])) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No heavy-goods plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumberHeavy" />
                            </div>

                            <!-- Light goods plates -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_GOODS')" x-cloak>
                                <div class="flex items-center justify-between">
                                    <x-label>{{ __('Light goods vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['goods_light']) }})</span></x-label>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="togglePlateGroup('light', true)" class="text-xs text-blue-600 hover:text-blue-800">{{ __('Select All') }}</button>
                                        <button type="button" onclick="togglePlateGroup('light', false)" class="text-xs text-gray-600 hover:text-gray-800">{{ __('Clear') }}</button>
                                    </div>
                                </div>
                                @if(count($plateGroups['goods_light']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['goods_light'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumberLight[]" value="{{ $p['plate'] }}"
                                                    class="plate-light rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], old('declarationVehiclePlateNumberLight', [])) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No light-goods plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumberLight" />
                            </div>

                            <!-- Passenger plates -->
                            <div class="mt-6" x-show="types.includes('CARRIAGE_OF_PASSENGERS')" x-cloak>
                                <div class="flex items-center justify-between">
                                    <x-label>{{ __('Passenger vehicles') }} <span class="text-xs text-gray-500">({{ count($plateGroups['passengers']) }})</span></x-label>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="togglePlateGroup('passenger', true)" class="text-xs text-blue-600 hover:text-blue-800">{{ __('Select All') }}</button>
                                        <button type="button" onclick="togglePlateGroup('passenger', false)" class="text-xs text-gray-600 hover:text-gray-800">{{ __('Clear') }}</button>
                                    </div>
                                </div>
                                @if(count($plateGroups['passengers']))
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($plateGroups['passengers'] as $p)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="declarationVehiclePlateNumber[]" value="{{ $p['plate'] }}"
                                                    class="plate-passenger rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    {{ in_array($p['plate'], old('declarationVehiclePlateNumber', [])) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $p['plate'] }} <span class="text-xs text-gray-500">{{ $p['country'] }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">{{ __('No passenger plates registered yet.') }}</p>
                                @endif
                                <x-error for="declarationVehiclePlateNumber" />
                            </div>

                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Plates are loaded from the IMI vehicle register. Register new plates under Trucks → Vehicle Registration.') }}
                            </p>
                        </div>
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
        // Preset country selection (EU+ common posting countries)
        function selectPresetCountries() {
            const presetCountries = [
                'AT', 'BE', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR',
                'HU', 'IT', 'LI', 'LT', 'LU', 'LV', 'MT', 'NL', 'NO', 'PL',
                'PT', 'RO', 'SE'
            ];
            const checkboxes = document.querySelectorAll('.country-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = presetCountries.includes(checkbox.value);
            });
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

        // Plate-group selection helpers
        function togglePlateGroup(group, state) {
            const cls = group === 'heavy' ? '.plate-heavy'
                      : group === 'light' ? '.plate-light'
                      : '.plate-passenger';
            document.querySelectorAll(cls).forEach(cb => { cb.checked = state; });
        }
    </script>
</x-layouts.app>
