<x-layouts.app :title="__('Bulk Update - Step 5: Preview Changes')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Step 5: Preview and confirm changes') }}</p>
            </div>
            <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Cancel') }}
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <!-- Progress indicator -->
                <div class="mb-6">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ __('Progress') }}</span>
                        <span>5/6</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 83.33%"></div>
                    </div>
                </div>

                <!-- Summary cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Declarations') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ count($previewData) }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-900 dark:text-green-100">{{ __('Will Change') }}</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ collect($previewData)->where('will_change', true)->count() }}
                        </div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">{{ __('No Changes') }}</div>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ collect($previewData)->where('will_change', false)->count() }}
                        </div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-purple-900 dark:text-purple-100">{{ __('Action') }}</div>
                        <div class="text-lg font-bold {{ $action === 'add' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ strtoupper($action) }}
                        </div>
                    </div>
                </div>

                <!-- Selected plates info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg mb-6">
                    <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-2">
                        {{ __('Selected Plates to') }} {{ strtoupper($action) }}
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($selectedPlates as $plate)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $action === 'add' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                {{ $plate }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Preview table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('Preview Changes') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Review the changes that will be made to each declaration') }}
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Declaration') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Current Plates') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('New Plates') }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Status') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                @forelse($previewData as $item)
                                    <tr class="{{ $item['will_change'] ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-800' }}">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $item['declaration']['declarationId'] ?? 'Unknown' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                @if(isset($item['declaration']['declarationPostingCountry']) && isset($item['declaration']['declarationEndDate']))
                                                    {{ $item['declaration']['declarationPostingCountry'] }} • {{ \Carbon\Carbon::parse($item['declaration']['declarationEndDate'])->format('M d, Y') }}
                                                @else
                                                    {{ __('Declaration details unavailable') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if(!empty($item['current_plates']))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($item['current_plates'] as $plate)
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                            {{ $plate }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('No plates') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            @if(!empty($item['new_plates']))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($item['new_plates'] as $plate)
                                                        @php
                                                            $isNewPlate = $action === 'add' && !in_array($plate, $item['current_plates']);
                                                            $isRemovedPlate = $action === 'remove' && in_array($plate, $item['current_plates']) && !in_array($plate, $item['new_plates']);
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $isNewPlate ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                                            {{ $plate }}
                                                            @if($isNewPlate)
                                                                <x-phosphor-plus class="ml-1 h-3 w-3" />
                                                            @endif
                                                        </span>
                                                    @endforeach

                                                    @if($action === 'remove')
                                                        @foreach($item['current_plates'] as $plate)
                                                            @if(in_array($plate, $selectedPlates) && !in_array($plate, $item['new_plates']))
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 line-through">
                                                                    {{ $plate }}
                                                                    <x-phosphor-minus class="ml-1 h-3 w-3" />
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-sm text-red-500 dark:text-red-400">{{ __('All plates will be removed!') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if(isset($item['error']))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    <x-phosphor-warning class="mr-1 h-3 w-3" />
                                                    {{ __('Error') }}
                                                </span>
                                            @elseif($item['will_change'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    <x-phosphor-info class="mr-1 h-3 w-3" />
                                                    {{ __('Will Update') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                    <x-phosphor-info class="mr-1 h-3 w-3" />
                                                    {{ __('No Change') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            {{ __('No declarations to preview') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Warnings and confirmations -->
                @if(collect($previewData)->where('will_change', false)->count() > 0)
                    <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-phosphor-warning class="h-5 w-5 text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    {{ __('Some declarations will not be changed because the selected plates are already present or not present.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(collect($previewData)->contains('error'))
                    <div class="mt-4 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-phosphor-x-circle class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    {{ __('Some declarations could not be loaded. They will be skipped during processing.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($action === 'remove' && collect($previewData)->contains(fn($item) => empty($item['new_plates'])))
                    <div class="mt-4 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-phosphor-warning class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    {{ __('Warning: Some declarations will have no plates') }}
                                </h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <p>{{ __('Removing these plates would leave some declarations with no plate numbers, which may not be valid. These declarations will be skipped.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('declarations.bulk-update.execute') }}">
                    @csrf

                    <!-- Pass through all the data -->
                    @foreach($selectedDriverIds as $driverId)
                        <input type="hidden" name="selected_drivers[]" value="{{ $driverId }}">
                    @endforeach

                    @foreach($selectedDeclarationIds as $declarationId)
                        <input type="hidden" name="selected_declarations[]" value="{{ $declarationId }}">
                    @endforeach

                    @foreach($selectedPlates as $plate)
                        <input type="hidden" name="selected_plates[]" value="{{ $plate }}">
                    @endforeach

                    <input type="hidden" name="action" value="{{ $action }}">

                    <!-- Navigation buttons -->
                    <div class="flex justify-between pt-6 border-t mt-6">
                        <a href="{{ route('declarations.bulk-update.step4') }}"
                           class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            {{ __('Previous') }}
                        </a>

                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-md font-medium transition-colors flex items-center space-x-2"
                                @if(collect($previewData)->where('will_change', true)->count() === 0) disabled @endif>
                            <x-phosphor-info class="h-5 w-5" />
                            <span>{{ __('Execute Bulk Update') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>