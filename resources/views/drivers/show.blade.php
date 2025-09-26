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

                <!-- Driver's Declarations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Declarations') }}</h2>
                        <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded">
                            {{ count($declarations) }} {{ __('declarations') }}
                        </span>
                    </div>

                    @if(count($declarations) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
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
</x-layouts.app>