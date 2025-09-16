<x-layouts.app :title="__('Driver Details')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $driver['driverFullName'] ?? (($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Driver Details') }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('drivers.edit', $driver['driverId']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Edit Driver') }}
                </a>
                <a href="{{ route('drivers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Drivers') }}
                </a>
            </div>
        </div>

        <!-- Driver Information -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Personal Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Personal Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('First Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverLatinFirstName'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Last Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverLatinLastName'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Full Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverFullName'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverDateOfBirth'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Driver ID') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded">{{ $driver['driverId'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Operator ID') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded">{{ $driver['driverOperatorId'] ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- License Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('License Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('License Number') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded text-center">
                                {{ $driver['driverLicenseNumber'] ?? 'N/A' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Document Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Document Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Document Type') }}</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $driver['driverDocumentType'] ?? 'N/A' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Document Number') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ $driver['driverDocumentNumber'] ?? 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Issuing Country') }}</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ strtoupper($driver['driverDocumentIssuingCountry'] ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Address Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Street Address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverAddressStreet'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Post Code') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverAddressPostCode'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('City') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverAddressCity'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Country') }}</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    {{ strtoupper($driver['driverAddressCountry'] ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Contract Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Contract Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Contract Start Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $driver['driverContractStartDate'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Applicable Law') }}</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                    {{ strtoupper($driver['driverApplicableLaw'] ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Quick Actions') }}</h3>
                    <div class="space-y-3">
                        <a href="{{ route('drivers.edit', $driver['driverId']) }}"
                           class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors text-center block">
                            {{ __('Edit Driver') }}
                        </a>
                        <button onclick="if(confirm('{{ __('Are you sure you want to delete this driver?') }}')) { document.getElementById('delete-form').submit(); }"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Delete Driver') }}
                        </button>
                        <form id="delete-form" method="POST" action="{{ route('drivers.destroy', $driver['driverId']) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>

                <!-- Driver Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Summary') }}</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Driver ID') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white font-mono">
                                {{ $driver['driverId'] ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Document Type') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $driver['driverDocumentType'] ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Country') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ strtoupper($driver['driverAddressCountry'] ?? 'N/A') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Applicable Law') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ strtoupper($driver['driverApplicableLaw'] ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Status') }}</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-3 w-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Active') }}
                                </span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Driver is available for declarations') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>