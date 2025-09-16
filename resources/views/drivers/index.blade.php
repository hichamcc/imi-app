<x-layouts.app :title="__('Drivers')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Drivers') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your transport drivers') }}</p>
            </div>
            <a href="{{ route('drivers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Add Driver') }}
            </a>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" action="{{ route('drivers.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input
                            type="text"
                            name="term"
                            :value="$term ?? ''"
                            placeholder="{{ __('Search by driver name...') }}"
                        />
                    </div>
                    <div>
                        <x-input
                            type="date"
                            name="dateOfBirth"
                            :value="$dateOfBirth ?? ''"
                            placeholder="{{ __('Date of Birth') }}"
                        />
                    </div>
                    <div>
                        <x-select name="withActiveDeclarations">
                            <option value="">{{ __('All Drivers') }}</option>
                            <option value="1" {{ ($withActiveDeclarations ?? '') == '1' ? 'selected' : '' }}>{{ __('With Active Declarations') }}</option>
                            <option value="0" {{ ($withActiveDeclarations ?? '') == '0' ? 'selected' : '' }}>{{ __('Without Active Declarations') }}</option>
                        </x-select>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="submit" class="flex-1">
                            {{ __('Search') }}
                        </x-button>
                        @if(($term ?? false) || ($dateOfBirth ?? false) || ($withActiveDeclarations ?? false))
                            <a href="{{ route('drivers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Drivers List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if(isset($drivers['items']) && count($drivers['items']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Name') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Date of Birth') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Declarations') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Last Updated') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($drivers['items'] as $driver)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                        {{ substr($driver['driverLatinFirstName'] ?? '', 0, 1) }}{{ substr($driver['driverLatinLastName'] ?? '', 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $driver['driverFullName'] ?? (($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    ID: {{ $driver['driverId'] ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $driver['driverDateOfBirth'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            @if($driver['driverHasDeclarations'] ?? false)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                    {{ $driver['driverCountActiveDeclarations'] ?? 0 }} active
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                    None
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ isset($driver['driverLastUpdate']) ? \Carbon\Carbon::parse($driver['driverLastUpdate'])->format('M j, Y') : 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('drivers.show', $driver['driverId']) }}"
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ __('View') }}
                                            </a>
                                            <a href="{{ route('drivers.edit', $driver['driverId']) }}"
                                               class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('drivers.destroy', $driver['driverId']) }}"
                                                  class="inline-block"
                                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this driver?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($drivers['lastEvaluatedKey']) || isset($startKey))
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                @if($startKey)
                                    <a href="{{ route('drivers.index', array_merge(request()->except('startKey'), [])) }}"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        {{ __('First Page') }}
                                    </a>
                                @endif
                                @if(isset($drivers['lastEvaluatedKey']))
                                    <a href="{{ route('drivers.index', array_merge(request()->query(), ['startKey' => $drivers['lastEvaluatedKey']])) }}"
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        {{ __('Next') }}
                                    </a>
                                @endif
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ __('Showing') }}
                                        <span class="font-medium">{{ count($drivers['items'] ?? []) }}</span>
                                        {{ __('drivers') }}
                                        @if($limit ?? false)
                                            ({{ __('limit') }}: {{ $limit }})
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        @if($startKey)
                                            <a href="{{ route('drivers.index', array_merge(request()->except('startKey'), [])) }}"
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">{{ __('First Page') }}</span>
                                                ← {{ __('First') }}
                                            </a>
                                        @endif
                                        @if(isset($drivers['lastEvaluatedKey']))
                                            <a href="{{ route('drivers.index', array_merge(request()->query(), ['startKey' => $drivers['lastEvaluatedKey']])) }}"
                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">{{ __('Next') }}</span>
                                                {{ __('Next') }} →
                                            </a>
                                        @endif
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No drivers found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($search ?? false)
                            {{ __('No drivers match your search criteria.') }}
                        @else
                            {{ __('Get started by adding your first driver.') }}
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('drivers.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('Add Driver') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>