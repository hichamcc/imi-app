<x-layouts.app :title="__('Add Driver')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add Driver') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Register a new driver in the system') }}</p>
            </div>
            <a href="{{ route('drivers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Back to Drivers') }}
            </a>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <x-form method="POST" action="{{ route('drivers.store') }}" class="space-y-8">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Personal Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <x-field>
                            <x-label for="driverLatinFirstName">{{ __('First Name') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverLatinFirstName"
                                id="driverLatinFirstName"
                                :value="old('driverLatinFirstName')"
                                required
                            />
                            <x-error for="driverLatinFirstName" />
                        </x-field>

                        <!-- Last Name -->
                        <x-field>
                            <x-label for="driverLatinLastName">{{ __('Last Name') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverLatinLastName"
                                id="driverLatinLastName"
                                :value="old('driverLatinLastName')"
                                required
                            />
                            <x-error for="driverLatinLastName" />
                        </x-field>

                        <!-- Date of Birth -->
                        <x-field>
                            <x-label for="driverDateOfBirth">{{ __('Date of Birth') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="date"
                                name="driverDateOfBirth"
                                id="driverDateOfBirth"
                                :value="old('driverDateOfBirth')"
                                required
                            />
                            <x-error for="driverDateOfBirth" />
                        </x-field>

                        <!-- License Number -->
                        <x-field>
                            <x-label for="driverLicenseNumber">{{ __('License Number') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverLicenseNumber"
                                id="driverLicenseNumber"
                                :value="old('driverLicenseNumber')"
                                required
                            />
                            <x-error for="driverLicenseNumber" />
                        </x-field>
                    </div>
                </div>

                <!-- Document Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Document Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Document Type -->
                        <x-field>
                            <x-label for="driverDocumentType">{{ __('Document Type') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="driverDocumentType" id="driverDocumentType" required>
                                <option value="">{{ __('Select Document Type') }}</option>
                                <option value="IDCARD" {{ old('driverDocumentType') == 'IDCARD' ? 'selected' : '' }}>{{ __('ID Card') }}</option>
                                <option value="PASSPORT" {{ old('driverDocumentType') == 'PASSPORT' ? 'selected' : '' }}>{{ __('Passport') }}</option>
                                <option value="DRIVINGLICENSE" {{ old('driverDocumentType') == 'DRIVINGLICENSE' ? 'selected' : '' }}>{{ __('Driving License') }}</option>
                            </x-select>
                            <x-error for="driverDocumentType" />
                        </x-field>

                        <!-- Document Number -->
                        <x-field>
                            <x-label for="driverDocumentNumber">{{ __('Document Number') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverDocumentNumber"
                                id="driverDocumentNumber"
                                :value="old('driverDocumentNumber')"
                                required
                            />
                            <x-error for="driverDocumentNumber" />
                        </x-field>

                        <!-- Document Issuing Country -->
                        <x-field>
                            <x-label for="driverDocumentIssuingCountry">{{ __('Issuing Country') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="driverDocumentIssuingCountry" id="driverDocumentIssuingCountry" required>
                                <option value="">{{ __('Select Country') }}</option>
                                @php
                                    $countries = [
                                        'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
                                        'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
                                        'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
                                        'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
                                        'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
                                        'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
                                        'SI' => 'Slovenia', 'ES' => 'Spain', 'SE' => 'Sweden'
                                    ];
                                @endphp
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ old('driverDocumentIssuingCountry') == $code ? 'selected' : '' }}>
                                        {{ __($name) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="driverDocumentIssuingCountry" />
                        </x-field>
                    </div>
                </div>

                <!-- Address Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Address Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Street -->
                        <x-field class="md:col-span-2">
                            <x-label for="driverAddressStreet">{{ __('Street Address') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverAddressStreet"
                                id="driverAddressStreet"
                                :value="old('driverAddressStreet')"
                                required
                            />
                            <x-error for="driverAddressStreet" />
                        </x-field>

                        <!-- Post Code -->
                        <x-field>
                            <x-label for="driverAddressPostCode">{{ __('Post Code') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverAddressPostCode"
                                id="driverAddressPostCode"
                                :value="old('driverAddressPostCode')"
                                required
                            />
                            <x-error for="driverAddressPostCode" />
                        </x-field>

                        <!-- City -->
                        <x-field>
                            <x-label for="driverAddressCity">{{ __('City') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="text"
                                name="driverAddressCity"
                                id="driverAddressCity"
                                :value="old('driverAddressCity')"
                                required
                            />
                            <x-error for="driverAddressCity" />
                        </x-field>

                        <!-- Country -->
                        <x-field class="md:col-span-2 lg:col-span-1">
                            <x-label for="driverAddressCountry">{{ __('Country') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="driverAddressCountry" id="driverAddressCountry" required>
                                <option value="">{{ __('Select Country') }}</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ old('driverAddressCountry') == $code ? 'selected' : '' }}>
                                        {{ __($name) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="driverAddressCountry" />
                        </x-field>
                    </div>
                </div>

                <!-- Contract Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Contract Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contract Start Date -->
                        <x-field>
                            <x-label for="driverContractStartDate">{{ __('Contract Start Date') }} <span class="text-red-500">*</span></x-label>
                            <x-input
                                type="date"
                                name="driverContractStartDate"
                                id="driverContractStartDate"
                                :value="old('driverContractStartDate')"
                                required
                            />
                            <x-error for="driverContractStartDate" />
                        </x-field>

                        <!-- Applicable Law -->
                        <x-field>
                            <x-label for="driverApplicableLaw">{{ __('Applicable Law') }} <span class="text-red-500">*</span></x-label>
                            <x-select name="driverApplicableLaw" id="driverApplicableLaw" required>
                                <option value="">{{ __('Select Country') }}</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ old('driverApplicableLaw') == $code ? 'selected' : '' }}>
                                        {{ __($name) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-error for="driverApplicableLaw" />
                        </x-field>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('drivers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <x-button type="submit" class="bg-blue-600 hover:bg-blue-700">
                        {{ __('Create Driver') }}
                    </x-button>
                </div>
            </x-form>
        </div>

        <!-- Help Text -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('Driver Information Requirements') }}
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('All fields marked with * are required') }}</li>
                            <li>{{ __('Dates must be in YYYY-MM-DD format') }}</li>
                            <li>{{ __('Country codes must be ISO 3166-1 alpha-2 format') }}</li>
                            <li>{{ __('Document information must match official documents') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>