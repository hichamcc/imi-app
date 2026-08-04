<x-layouts.app :title="__('Declaration Renewals (Catch-up)')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Declaration Renewals — Catch-up') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    {{ __('Drivers with auto-renew enabled, at most 1 active declaration, and at least one expired declaration since') }}
                    <strong>{{ $cutoff }}</strong>.
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300 px-4 py-3 rounded-lg">{{ session('info') }}</div>
        @endif
        @if($apiError)
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ __('Failed to load data from API:') }} {{ $apiError }}
            </div>
        @endif

        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300 px-4 py-3 rounded-lg text-sm">
            ⚠️ {{ __('Renew ignores unregistered plates. Push your fleet to the IMI plate register first for the widest coverage.') }}
            <a href="{{ route('trucks.plate-numbers') }}" class="underline font-medium">{{ __('Open IMI Plate Register') }}</a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
                {{ count($drivers) }} {{ __('driver(s) match — out of') }} {{ $totalDrivers }} {{ __('total drivers.') }}
            </div>

            @if(count($drivers) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Driver') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Active') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Expired countries to renew') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($drivers as $row)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $row['name'] ?: '—' }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ substr($row['id'], 0, 8) }}…</div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="text-gray-700 dark:text-gray-300">{{ $row['active_count'] }}</div>
                                        @if(!empty($row['active_countries']))
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach($row['active_countries'] as $c)
                                                    <span class="inline-flex px-1.5 py-0.5 text-xs rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ $c }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($row['expired_countries'] as $country => $endDate)
                                                <span class="inline-flex flex-col px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"
                                                      title="{{ __('Expired') }} {{ $endDate }}">
                                                    {{ $country }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">{{ count($row['expired_countries']) }} {{ __('country/ies') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <form method="POST" action="{{ route('admin.declaration-renewals.renew', $row['id']) }}"
                                              onsubmit="return confirm('{{ __('Renew') }} {{ count($row['expired_countries']) }} {{ __('country/ies for') }} {{ $row['name'] }}?');">
                                            @csrf
                                            <button type="submit"
                                                    class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                                                {{ __('Renew all') }} ({{ count($row['expired_countries']) }})
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-gray-500 dark:text-gray-400 text-sm">
                    {{ __('No drivers match the criteria — every auto-renew driver either has enough active declarations, or nothing expired since the cutoff.') }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
