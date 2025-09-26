<x-layouts.app :title="__('Edit Submitted Declaration')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Submitted Declaration') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Declaration') }} #{{ substr($declaration['declarationId'] ?? '', 0, 8) }}</p>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                        {{ $declaration['declarationStatus'] ?? 'SUBMITTED' }}
                    </span>
                </div>
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

        <!-- Notice -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-phosphor-warning class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                        {{ __('This declaration is already submitted. Only limited fields can be updated. Changes will not trigger a new email notification.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('declarations.update-submitted', $declaration['declarationId']) }}" class="space-y-8">
                @method('PUT')

                <!-- Declaration Details (Read-Only) -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Declaration Details') }} ({{ __('Read-Only') }})</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div>
                            <x-label>{{ __('Driver') }}</x-label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $declaration['driverLatinFullName'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <x-label>{{ __('Posting Country') }}</x-label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $declaration['declarationPostingCountry'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <x-label>{{ __('Start Date') }}</x-label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $declaration['declarationStartDate'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Updatable Fields -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Updatable Information') }}</h3>

                    <!-- End Date -->
                    <div class="mb-6">
                        <x-field>
                            <x-label for="declarationEndDate">{{ __('End Date') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="date"
                                name="declarationEndDate"
                                id="declarationEndDate"
                                value="{{ old('declarationEndDate', $declaration['declarationEndDate'] ?? '') }}"
                                required
                            />
                            <x-error for="declarationEndDate" />
                        </x-field>
                    </div>

                    <!-- Operation Type -->
                    <div class="mb-6">
                        <x-field>
                            <x-label>{{ __('Operation Type') }} <span class="text-red-500">*</span></x-label>
                            <div class="space-y-2">
                                @php
                                    $selectedOperationTypes = old('declarationOperationType', $declaration['declarationOperationType'] ?? []);
                                    // Ensure it's an array
                                    if (!is_array($selectedOperationTypes)) {
                                        $selectedOperationTypes = [$selectedOperationTypes];
                                    }
                                @endphp
                                <label class="flex items-center">
                                    <input type="checkbox" name="declarationOperationType[]" value="INTERNATIONAL_CARRIAGE"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                           {{ in_array('INTERNATIONAL_CARRIAGE', $selectedOperationTypes) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('International Carriage') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="declarationOperationType[]" value="CABOTAGE_OPERATIONS"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                           {{ in_array('CABOTAGE_OPERATIONS', $selectedOperationTypes) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Cabotage Operations') }}</span>
                                </label>
                            </div>
                            <x-error for="declarationOperationType" />
                        </x-field>
                    </div>

                    <!-- Transport Type -->
                    <div class="mb-6">
                        <x-field>
                            <x-label>{{ __('Transport Type') }} <span class="text-red-500">*</span></x-label>
                            <div class="space-y-2">
                                @php
                                    $selectedTransportTypes = old('declarationTransportType', $declaration['declarationTransportType'] ?? []);
                                    // Ensure it's an array
                                    if (!is_array($selectedTransportTypes)) {
                                        $selectedTransportTypes = [$selectedTransportTypes];
                                    }
                                @endphp
                                <label class="flex items-center">
                                    <input type="checkbox" name="declarationTransportType[]" value="CARRIAGE_OF_GOODS"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                           {{ in_array('CARRIAGE_OF_GOODS', $selectedTransportTypes) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Carriage of Goods') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="declarationTransportType[]" value="CARRIAGE_OF_PASSENGERS"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                           {{ in_array('CARRIAGE_OF_PASSENGERS', $selectedTransportTypes) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Carriage of Passengers') }}</span>
                                </label>
                            </div>
                            <x-error for="declarationTransportType" />
                        </x-field>
                    </div>

                    <!-- Vehicle Plate Numbers -->
                    <div class="mb-6">
                        <x-field>
                            <x-label for="declarationVehiclePlateNumber">{{ __('Vehicle Plate Numbers') }} <span class="text-red-500">*</span></x-label>

                            @if(!empty($missingPlates))
                                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex">
                                        <x-phosphor-warning class="h-5 w-5 text-yellow-400 mr-2 mt-0.5" />
                                        <div>
                                            <p class="text-sm text-yellow-700 dark:text-yellow-200 font-medium">
                                                {{ __('Some plates from API not found in your trucks') }}:
                                            </p>
                                            <p class="text-sm text-yellow-600 dark:text-yellow-300 mt-1">
                                                {{ implode(', ', $missingPlates) }}
                                            </p>
                                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                                {{ __('These plates will be added as text inputs below the truck selection.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($trucks->count() > 0)
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('Select from your available trucks:') }}</p>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        @foreach($trucks as $truck)
                                            @php
                                                $selectedPlates = old('declarationVehiclePlateNumber', $declaration['declarationVehiclePlateNumber'] ?? []);
                                                if (!is_array($selectedPlates)) $selectedPlates = [$selectedPlates];
                                            @endphp
                                            <label class="flex items-center">
                                                <input type="checkbox"
                                                       name="declarationVehiclePlateNumber[]"
                                                       value="{{ $truck->plate }}"
                                                       class="plate-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       {{ in_array($truck->plate, $selectedPlates) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $truck->plate }} - {{ $truck->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($missingPlates))
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('Plates from API not in your truck database:') }}</p>
                                    @foreach($missingPlates as $missingPlate)
                                        <div class="flex items-center mb-2">
                                            <input type="hidden" name="declarationVehiclePlateNumber[]" value="{{ $missingPlate }}">
                                            <input
                                                type="text"
                                                value="{{ $missingPlate }}"
                                                readonly
                                                class="flex-1 rounded-md border-gray-300 bg-gray-100 dark:bg-gray-600 dark:border-gray-600 dark:text-white px-3 py-2"
                                            />
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ __('(from API)') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select one or more vehicle plates for this declaration.') }}</p>
                            <x-error for="declarationVehiclePlateNumber" />
                        </x-field>
                    </div>
                </div>

                <!-- Other Contact Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Other Contact Information') }}</h3>

                    <!-- Transport Manager Checkbox -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="otherContactAsTransportManager" value="1"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                   {{ old('otherContactAsTransportManager', ($declaration['otherContactAsTransportManager'] ?? false)) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Other Contact as Transport Manager') }}</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-field>
                            <x-label for="otherContactFirstName">{{ __('First Name') }}</x-label>
                            <x-input type="text" name="otherContactFirstName" id="otherContactFirstName" value="{{ old('otherContactFirstName', $declaration['otherContactFirstName'] ?? '') }}" />
                            <x-error for="otherContactFirstName" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactLastName">{{ __('Last Name') }}</x-label>
                            <x-input type="text" name="otherContactLastName" id="otherContactLastName" value="{{ old('otherContactLastName', $declaration['otherContactLastName'] ?? '') }}" />
                            <x-error for="otherContactLastName" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactEmail">{{ __('Email') }}</x-label>
                            <x-input type="email" name="otherContactEmail" id="otherContactEmail" value="{{ old('otherContactEmail', $declaration['otherContactEmail'] ?? '') }}" />
                            <x-error for="otherContactEmail" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactPhone">{{ __('Phone') }}</x-label>
                            <x-input type="text" name="otherContactPhone" id="otherContactPhone" value="{{ old('otherContactPhone', $declaration['otherContactPhone'] ?? '') }}" />
                            <x-error for="otherContactPhone" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactAddressStreet">{{ __('Street Address') }}</x-label>
                            <x-input type="text" name="otherContactAddressStreet" id="otherContactAddressStreet" value="{{ old('otherContactAddressStreet', $declaration['otherContactAddressStreet'] ?? '') }}" />
                            <x-error for="otherContactAddressStreet" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactAddressCity">{{ __('City') }}</x-label>
                            <x-input type="text" name="otherContactAddressCity" id="otherContactAddressCity" value="{{ old('otherContactAddressCity', $declaration['otherContactAddressCity'] ?? '') }}" />
                            <x-error for="otherContactAddressCity" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactAddressCountry">{{ __('Country') }}</x-label>
                            <x-select name="otherContactAddressCountry" id="otherContactAddressCountry">
                                <option value="">{{ __('Select Country') }}</option>
                                @foreach(\App\Services\DeclarationService::getPostingCountries() as $code => $country)
                                    <option value="{{ $code }}" {{ old('otherContactAddressCountry', $declaration['otherContactAddressCountry'] ?? '') === $code ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="otherContactAddressCountry" />
                        </x-field>

                        <x-field>
                            <x-label for="otherContactAddressPostCode">{{ __('Postal Code') }}</x-label>
                            <x-input type="text" name="otherContactAddressPostCode" id="otherContactAddressPostCode" value="{{ old('otherContactAddressPostCode', $declaration['otherContactAddressPostCode'] ?? '') }}" />
                            <x-error for="otherContactAddressPostCode" />
                        </x-field>
                    </div>
                </div>

                <!-- Driver Email -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Driver Email') }}</h3>
                    <x-field>
                        <x-label for="driverEmail">{{ __('Driver Email') }}</x-label>
                        <x-input type="email" name="driverEmail" id="driverEmail" value="{{ old('driverEmail', $declaration['driverEmail'] ?? '') }}" />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Optional: Email address for the driver') }}</p>
                        <x-error for="driverEmail" />
                    </x-field>
                </div>

                <!-- Actions -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('declarations.show', $declaration['declarationId']) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </a>
                        <x-button type="submit" class="bg-green-600 hover:bg-green-700">
                            {{ __('Update Declaration') }}
                        </x-button>
                    </div>
                </div>
            </x-form>
        </div>
    </div>

</x-layouts.app>