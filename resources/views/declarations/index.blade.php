<x-layouts.app :title="__('Declarations')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your transport posting declarations') }}</p>
            </div>
            <a href="{{ route('declarations.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Create Declaration') }}
            </a>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" action="{{ route('declarations.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-select name="status">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach(\App\Services\DeclarationService::getStatuses() as $key => $value)
                                <option value="{{ $key }}" {{ ($status ?? '') == $key ? 'selected' : '' }}>{{ __($value) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <x-select name="postingCountry">
                            <option value="">{{ __('All Countries') }}</option>
                            @foreach(\App\Services\DeclarationService::getPostingCountries() as $key => $value)
                                <option value="{{ $key }}" {{ ($postingCountry ?? '') == $key ? 'selected' : '' }}>{{ __($value) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <input type="date" name="endDateFrom" value="{{ $endDateFrom ?? '' }}" placeholder="{{ __('End Date From') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <input type="date" name="endDateTo" value="{{ $endDateTo ?? '' }}" placeholder="{{ __('End Date To') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit">
                        {{ __('Filter') }}
                    </x-button>
                    @if(($status ?? false) || ($postingCountry ?? false) || ($endDateFrom ?? false) || ($endDateTo ?? false))
                        <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Declarations List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if(isset($declarations['items']) && count($declarations['items']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Declaration') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Driver') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Country') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Period') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($declarations['items'] as $declaration)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ substr($declaration['declarationId'] ?? '', 0, 8) }}...
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ isset($declaration['declarationLastUpdate']) ? \Carbon\Carbon::parse($declaration['declarationLastUpdate'])->format('M j, Y') : 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $declaration['driverLatinFullName'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ strtoupper($declaration['declarationPostingCountry'] ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $declaration['declarationStartDate'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            to {{ $declaration['declarationEndDate'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('declarations.show', $declaration['declarationId']) }}"
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ __('View') }}
                                            </a>
                                            @if($status === 'DRAFT')
                                                <a href="{{ route('declarations.edit', $declaration['declarationId']) }}"
                                                   class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                    {{ __('Edit') }}
                                                </a>
                                                <form method="POST" action="{{ route('declarations.submit', $declaration['declarationId']) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                            onclick="return confirm('{{ __('Are you sure you want to submit this declaration?') }}')">
                                                        {{ __('Submit') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('declarations.destroy', $declaration['declarationId']) }}" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                            onclick="return confirm('{{ __('Are you sure you want to delete this declaration?') }}')">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            @elseif($status === 'SUBMITTED')
                                                <form method="POST" action="{{ route('declarations.withdraw', $declaration['declarationId']) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300"
                                                            onclick="return confirm('{{ __('Are you sure you want to withdraw this declaration?') }}')">
                                                        {{ __('Withdraw') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($declarations['lastEvaluatedKey']) || isset($startKey))
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                @if($startKey)
                                    <a href="{{ route('declarations.index', array_merge(request()->except('startKey'), [])) }}"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        {{ __('First Page') }}
                                    </a>
                                @endif
                                @if(isset($declarations['lastEvaluatedKey']))
                                    <a href="{{ route('declarations.index', array_merge(request()->query(), ['startKey' => $declarations['lastEvaluatedKey']])) }}"
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        {{ __('Next') }}
                                    </a>
                                @endif
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ __('Showing') }}
                                        <span class="font-medium">{{ count($declarations['items'] ?? []) }}</span>
                                        {{ __('declarations') }}
                                        @if($limit ?? false)
                                            ({{ __('limit') }}: {{ $limit }})
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        @if($startKey)
                                            <a href="{{ route('declarations.index', array_merge(request()->except('startKey'), [])) }}"
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">{{ __('First Page') }}</span>
                                                ← {{ __('First') }}
                                            </a>
                                        @endif
                                        @if(isset($declarations['lastEvaluatedKey']))
                                            <a href="{{ route('declarations.index', array_merge(request()->query(), ['startKey' => $declarations['lastEvaluatedKey']])) }}"
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No declarations found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($status ?? false)
                            {{ __('No declarations match your search criteria.') }}
                        @else
                            {{ __('Get started by creating your first declaration.') }}
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('declarations.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('Create Declaration') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>