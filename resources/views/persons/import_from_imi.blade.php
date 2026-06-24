<x-layouts.app :title="__('Import from IMI')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Import drivers from IMI') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Pull existing IMI drivers into your local HR records.') }}</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('persons.import-from-imi.bulk') }}" onsubmit="return confirm('{{ __('Import every IMI driver that is not yet linked. Continue?') }}')">
                    @csrf
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium">
                        {{ __('Bulk Import All Missing') }}
                    </button>
                </form>
                <a href="{{ route('persons.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back') }}</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if(count($drivers) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date of Birth') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Document') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('IMI Driver ID') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($drivers as $driver)
                                @php $linked = $linkedByDriverId->get($driver['driverId'] ?? ''); @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $driver['driverDateOfBirth'] ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">
                                        {{ $driver['driverDocumentType'] ?? '—' }}
                                        @if(!empty($driver['driverDocumentNumber']))<div class="text-gray-500 font-mono">{{ $driver['driverDocumentNumber'] }}</div>@endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-500 dark:text-gray-400">{{ Str::limit($driver['driverId'] ?? '', 14) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        @if($linked)
                                            <a href="{{ route('persons.show', $linked->id) }}" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                {{ __('Linked — View') }}
                                            </a>
                                        @else
                                            <form method="POST" action="{{ route('persons.import-from-imi.one') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="driver_id" value="{{ $driver['driverId'] }}">
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                                                    {{ __('Import to HR') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($nextKey || $startKey)
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                        @if($startKey)
                            <a href="{{ route('persons.import-from-imi') }}" class="text-sm text-blue-600 hover:text-blue-800">← {{ __('First Page') }}</a>
                        @else
                            <span></span>
                        @endif
                        @if($nextKey)
                            <a href="{{ route('persons.import-from-imi', ['startKey' => $nextKey]) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('Next') }} →</a>
                        @endif
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('No IMI drivers found.') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Make sure your API credentials are set in') }} <a href="{{ route('settings.profile.edit') }}" class="text-blue-600 hover:underline">{{ __('Settings') }}</a>.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
