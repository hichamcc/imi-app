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
                <button onclick="openCloneModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Clone to Organization') }}
                </button>
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

                <!-- Driver's Declarations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Declarations') }}</h2>
                        <div class="flex items-center space-x-3">
                            <button id="withdrawSelectedBtn" onclick="withdrawSelected()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors text-sm hidden">
                                {{ __('Withdraw Selected') }} (<span id="withdrawCount">0</span>)
                            </button>
                            <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded">
                                {{ count($declarations) }} {{ __('declarations') }}
                            </span>
                        </div>
                    </div>

                    @if(count($declarations) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <div class="flex items-center">
                                                <input type="checkbox" id="selectAllWithdraw" onchange="toggleSelectAllWithdraw()" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                                                <label for="selectAllWithdraw" class="ml-2 sr-only">{{ __('Select All') }}</label>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ __('Declaration ID') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ __('Country') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ __('Period') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($declarations as $declaration)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                                                    <input type="checkbox"
                                                           class="withdraw-checkbox h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
                                                           value="{{ $declaration['declarationId'] }}"
                                                           onchange="updateWithdrawCount()">
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ substr($declaration['declarationId'] ?? 'N/A', 0, 8) }}...
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $status = $declaration['declarationStatus'] ?? 'UNKNOWN';
                                                    $statusClass = match($status) {
                                                        'DRAFT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                        'SUBMITTED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                        'WITHDRAWN' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                        'EXPIRED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                    {{ strtoupper($declaration['declarationPostingCountry'] ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $declaration['declarationStartDate'] ?? 'N/A' }} -<br>
                                                {{ $declaration['declarationEndDate'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('declarations.show', $declaration['declarationId']) }}"
                                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                        {{ __('View') }}
                                                    </a>

                                                    @if(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                                        <a href="{{ route('declarations.edit-submitted', $declaration['declarationId']) }}"
                                                           class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                            {{ __('Update') }}
                                                        </a>
                                                    @elseif(($declaration['declarationStatus'] ?? '') === 'DRAFT')
                                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                                        <a href="{{ route('declarations.edit', $declaration['declarationId']) }}"
                                                           class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300">
                                                            {{ __('Edit') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No declarations found for this driver') }}</p>
                            <a href="{{ route('declarations.create', ['driverId' => $driver['driverId']]) }}"
                               class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ __('Create Declaration') }}
                            </a>
                        </div>
                    @endif
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

    <!-- Clone Driver Modal -->
    <div id="cloneModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('Clone Driver to Another Organization') }}
                    </h3>
                    <button onclick="closeCloneModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                {{ __('Source Driver') }}: <strong>{{ $driver['driverFullName'] ?? (($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}</strong>
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                {{ __('The driver will be created as a new driver with a new ID. No declarations will be copied.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Target Organizations') }}
                        </label>
                        <div class="flex space-x-2">
                            <button type="button" onclick="selectAllOrganizations()" class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400">
                                {{ __('Select All') }}
                            </button>
                            <button type="button" onclick="clearAllOrganizations()" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400">
                                {{ __('Clear All') }}
                            </button>
                        </div>
                    </div>
                    <div id="organizationsLoading" class="text-center py-4">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Loading organizations...') }}
                        </div>
                    </div>
                    <div id="organizationsList" class="hidden max-h-64 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 space-y-2">
                        <!-- Organizations will be loaded here as checkboxes -->
                    </div>
                    <div id="organizationsError" class="hidden text-sm text-red-600 dark:text-red-400 mt-2"></div>
                </div>

                <div id="cloneProgress" class="hidden mb-4">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Cloning Progress') }}:
                    </div>
                    <div id="progressList" class="space-y-2 max-h-48 overflow-y-auto">
                        <!-- Progress items will be added here -->
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button onclick="closeCloneModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                        {{ __('Cancel') }}
                    </button>
                    <button onclick="cloneDriver()" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700" id="cloneButton">
                        {{ __('Clone Driver') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let impersonatableUsers = [];

        function openCloneModal() {
            document.getElementById('cloneModal').classList.remove('hidden');
            document.getElementById('cloneProgress').classList.add('hidden');
            document.getElementById('progressList').innerHTML = '';
            loadImpersonatableUsers();
        }

        function closeCloneModal() {
            document.getElementById('cloneModal').classList.add('hidden');
            clearAllOrganizations();
        }

        function loadImpersonatableUsers() {
            const loadingDiv = document.getElementById('organizationsLoading');
            const listDiv = document.getElementById('organizationsList');
            const errorDiv = document.getElementById('organizationsError');

            loadingDiv.classList.remove('hidden');
            listDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');

            // Get current user's impersonatable users
            fetch('/api/impersonatable-users', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.users) {
                    impersonatableUsers = data.users;

                    if (impersonatableUsers.length === 0) {
                        errorDiv.textContent = '{{ __("You don't have access to any other organizations.") }}';
                        errorDiv.classList.remove('hidden');
                    } else {
                        renderOrganizationsList(impersonatableUsers);
                        listDiv.classList.remove('hidden');
                    }
                } else {
                    errorDiv.textContent = data.message || '{{ __("Failed to load organizations") }}';
                    errorDiv.classList.remove('hidden');
                }

                loadingDiv.classList.add('hidden');
            })
            .catch(error => {
                console.error('Error loading organizations:', error);
                errorDiv.textContent = '{{ __("Failed to load organizations. Please try again.") }}';
                errorDiv.classList.remove('hidden');
                loadingDiv.classList.add('hidden');
            });
        }

        function renderOrganizationsList(users) {
            const listDiv = document.getElementById('organizationsList');
            listDiv.innerHTML = '';

            users.forEach(user => {
                const div = document.createElement('div');
                div.className = 'flex items-center p-2 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700';
                div.innerHTML = `
                    <input type="checkbox"
                           id="org_${user.id}"
                           value="${user.id}"
                           class="organization-checkbox mr-3 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="org_${user.id}" class="flex-1 cursor-pointer text-sm text-gray-900 dark:text-white">
                        <div class="font-medium">${user.name}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">${user.email}</div>
                    </label>
                `;
                listDiv.appendChild(div);
            });
        }

        function selectAllOrganizations() {
            const checkboxes = document.querySelectorAll('.organization-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function clearAllOrganizations() {
            const checkboxes = document.querySelectorAll('.organization-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        function getSelectedOrganizations() {
            const checkboxes = document.querySelectorAll('.organization-checkbox:checked');
            return Array.from(checkboxes).map(cb => ({
                id: cb.value,
                name: impersonatableUsers.find(u => u.id == cb.value)?.name || 'Unknown',
                email: impersonatableUsers.find(u => u.id == cb.value)?.email || ''
            }));
        }

        async function cloneDriver() {
            const selectedOrgs = getSelectedOrganizations();

            if (selectedOrgs.length === 0) {
                alert('{{ __("Please select at least one target organization") }}');
                return;
            }

            const cloneButton = document.getElementById('cloneButton');
            const originalText = cloneButton.textContent;

            // Show loading state
            cloneButton.textContent = '{{ __("Cloning...") }}';
            cloneButton.disabled = true;

            // Show progress section
            document.getElementById('cloneProgress').classList.remove('hidden');
            const progressList = document.getElementById('progressList');
            progressList.innerHTML = '';

            let successCount = 0;
            let errorCount = 0;
            const results = [];

            // Clone to each organization sequentially
            for (const org of selectedOrgs) {
                const progressItem = createProgressItem(org.id, org.name);
                progressList.appendChild(progressItem);

                try {
                    const response = await fetch('{{ route("drivers.clone", $driver["driverId"]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            target_user_id: org.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        successCount++;
                        updateProgressItem(org.id, 'success', data.message, data.new_driver_id);
                        results.push({
                            org: org,
                            success: true,
                            newDriverId: data.new_driver_id
                        });
                    } else {
                        errorCount++;
                        updateProgressItem(org.id, 'error', data.message || '{{ __("Failed to clone driver") }}');
                        results.push({
                            org: org,
                            success: false,
                            error: data.message
                        });
                    }
                } catch (error) {
                    console.error('Error cloning to', org.name, error);
                    errorCount++;
                    updateProgressItem(org.id, 'error', '{{ __("Network error occurred") }}');
                    results.push({
                        org: org,
                        success: false,
                        error: error.message
                    });
                }
            }

            // Reset button state
            cloneButton.textContent = originalText;
            cloneButton.disabled = false;

            // Show summary
            const summaryMessage = `{{ __("Completed") }}: ${successCount} {{ __("successful") }}, ${errorCount} {{ __("failed") }}`;
            showMessage(summaryMessage, errorCount === 0 ? 'success' : 'info');

            // Ask if user wants to view one of the cloned drivers
            if (successCount > 0) {
                const firstSuccess = results.find(r => r.success);
                if (firstSuccess && confirm(`{{ __("Clone completed! Do you want to view the cloned driver in") }} ${firstSuccess.org.name}?`)) {
                    // Create a form and submit POST request to impersonate
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/impersonate/${firstSuccess.org.id}`;

                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfInput);

                    // Add redirect parameter
                    const redirectInput = document.createElement('input');
                    redirectInput.type = 'hidden';
                    redirectInput.name = 'redirect';
                    redirectInput.value = `/drivers/${firstSuccess.newDriverId}`;
                    form.appendChild(redirectInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }

        function createProgressItem(orgId, orgName) {
            const div = document.createElement('div');
            div.id = `progress_${orgId}`;
            div.className = 'flex items-center p-2 border border-gray-200 dark:border-gray-600 rounded';
            div.innerHTML = `
                <div class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">${orgName}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __("Cloning...") }}</div>
                </div>
            `;
            return div;
        }

        function updateProgressItem(orgId, status, message, newDriverId = null) {
            const div = document.getElementById(`progress_${orgId}`);
            if (!div) return;

            const iconHtml = status === 'success'
                ? '<svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

            const messageColor = status === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

            const messageHtml = newDriverId
                ? `<div class="text-xs ${messageColor}">${message} (ID: ${newDriverId})</div>`
                : `<div class="text-xs ${messageColor}">${message}</div>`;

            div.innerHTML = `
                <div class="flex-shrink-0">
                    ${iconHtml}
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">${div.querySelector('.text-sm').textContent}</div>
                    ${messageHtml}
                </div>
            `;
        }

        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Close modal when clicking outside
        document.getElementById('cloneModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCloneModal();
            }
        });

        // Add CSRF token to head if not already present
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const metaTag = document.createElement('meta');
            metaTag.name = 'csrf-token';
            metaTag.content = '{{ csrf_token() }}';
            document.head.appendChild(metaTag);
        }

        // Bulk withdraw functions
        function updateWithdrawCount() {
            const checkboxes = document.querySelectorAll('.withdraw-checkbox:checked');
            const count = checkboxes.length;
            const withdrawCountElement = document.getElementById('withdrawCount');
            const withdrawBtn = document.getElementById('withdrawSelectedBtn');

            withdrawCountElement.textContent = count;

            if (count > 0) {
                withdrawBtn.classList.remove('hidden');
            } else {
                withdrawBtn.classList.add('hidden');
            }
        }

        function toggleSelectAllWithdraw() {
            const selectAllCheckbox = document.getElementById('selectAllWithdraw');
            const checkboxes = document.querySelectorAll('.withdraw-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateWithdrawCount();
        }

        async function withdrawSelected() {
            const checkboxes = document.querySelectorAll('.withdraw-checkbox:checked');
            const declarationIds = Array.from(checkboxes).map(cb => cb.value);

            if (declarationIds.length === 0) {
                alert('{{ __("Please select at least one declaration to withdraw.") }}');
                return;
            }

            if (!confirm(`{{ __("Are you sure you want to WITHDRAW") }} ${declarationIds.length} {{ __("selected declarations?") }}`)) {
                return;
            }

            const withdrawBtn = document.getElementById('withdrawSelectedBtn');
            const originalText = withdrawBtn.innerHTML;
            withdrawBtn.disabled = true;
            withdrawBtn.innerHTML = '{{ __("Withdrawing...") }}';

            try {
                const response = await fetch('{{ route("declarations.bulk-withdraw") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        declaration_ids: declarationIds
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    // Refresh page to show updated statuses
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('{{ __("Error") }}: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error withdrawing declarations:', error);
                showMessage('{{ __("Failed to withdraw declarations. Please try again.") }}', 'error');
            } finally {
                withdrawBtn.disabled = false;
                withdrawBtn.innerHTML = originalText;
            }
        }
    </script>
</x-layouts.app>