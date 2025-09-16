<x-layouts.app :title="__('Declaration Details')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Declaration Details') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Declaration') }} #{{ substr($declaration['declarationId'] ?? '', 0, 8) }}</p>
            </div>
            <div class="flex space-x-3">
                @if(($declaration['declarationStatus'] ?? '') === 'DRAFT')
                    <a href="{{ route('declarations.edit', $declaration['declarationId']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Edit Declaration') }}
                    </a>
                    <form method="POST" action="{{ route('declarations.submit', $declaration['declarationId']) }}" class="inline-block">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                onclick="return confirm('{{ __('Are you sure you want to submit this declaration?') }}')">
                            {{ __('Submit Declaration') }}
                        </button>
                    </form>
                @elseif(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                    <form method="POST" action="{{ route('declarations.withdraw', $declaration['declarationId']) }}" class="inline-block">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                onclick="return confirm('{{ __('Are you sure you want to withdraw this declaration?') }}')">
                            {{ __('Withdraw Declaration') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Declarations') }}
                </a>
            </div>
        </div>

        <!-- Declaration Information -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status and Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Declaration Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Declaration ID') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $declaration['declarationId'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                            <dd class="mt-1">
                                @php
                                    $status = $declaration['declarationStatus'] ?? '';
                                    $statusColors = [
                                        'DRAFT' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'SUBMITTED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'WITHDRAWN' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'EXPIRED' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Posting Country') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationPostingCountry'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Last Updated') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ isset($declaration['declarationLastUpdate']) ? \Carbon\Carbon::parse($declaration['declarationLastUpdate'])->format('M j, Y H:i') : 'N/A' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Period Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Declaration Period') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Start Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationStartDate'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('End Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationEndDate'] ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Driver Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Driver Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Driver Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $declaration['driverFullName'] ?? (($declaration['driverLatinFirstName'] ?? '') . ' ' . ($declaration['driverLatinLastName'] ?? '')) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverDateOfBirth'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('License Number') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverLicenseNumber'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Document Type') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverDocumentType'] ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Operation Details -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Operation Details') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Operation Types') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationOperationType']) && is_array($declaration['declarationOperationType']))
                                    @foreach($declaration['declarationOperationType'] as $type)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 mr-2 mb-2">
                                            {{ str_replace('_', ' ', $type) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Transport Types') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationTransportType']) && is_array($declaration['declarationTransportType']))
                                    @foreach($declaration['declarationTransportType'] as $type)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 mr-2 mb-2">
                                            {{ str_replace('_', ' ', $type) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Vehicle Plate Numbers') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationVehiclePlateNumber']) && is_array($declaration['declarationVehiclePlateNumber']))
                                    @foreach($declaration['declarationVehiclePlateNumber'] as $plate)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 mr-2 mb-2">
                                            {{ $plate }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
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
                        @if(($declaration['declarationStatus'] ?? '') === 'DRAFT')
                            <a href="{{ route('declarations.edit', $declaration['declarationId']) }}"
                               class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors text-center block">
                                {{ __('Edit Declaration') }}
                            </a>
                            <form method="POST" action="{{ route('declarations.submit', $declaration['declarationId']) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to submit this declaration?') }}')">
                                    {{ __('Submit Declaration') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('declarations.destroy', $declaration['declarationId']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to delete this declaration?') }}')">
                                    {{ __('Delete Declaration') }}
                                </button>
                            </form>
                        @elseif(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                            <form method="POST" action="{{ route('declarations.withdraw', $declaration['declarationId']) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to withdraw this declaration?') }}')">
                                    {{ __('Withdraw Declaration') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Status Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Status') }}</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @php
                                    $status = $declaration['declarationStatus'] ?? '';
                                    $statusColor = match($status) {
                                        'DRAFT' => 'bg-yellow-400',
                                        'SUBMITTED' => 'bg-green-400',
                                        'WITHDRAWN' => 'bg-red-400',
                                        'EXPIRED' => 'bg-gray-400',
                                        default => 'bg-gray-400'
                                    };
                                @endphp
                                <div class="h-3 w-3 rounded-full {{ $statusColor }}"></div>
                            </div>
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @switch($status)
                                @case('DRAFT')
                                    {{ __('Declaration is in draft state and can be edited') }}
                                    @break
                                @case('SUBMITTED')
                                    {{ __('Declaration has been submitted') }}
                                    @break
                                @case('WITHDRAWN')
                                    {{ __('Declaration has been withdrawn') }}
                                    @break
                                @case('EXPIRED')
                                    {{ __('Declaration has expired') }}
                                    @break
                                @default
                                    {{ __('Unknown status') }}
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>