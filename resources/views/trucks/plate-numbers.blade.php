<x-layouts.app :title="__('IMI Plate Numbers')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('IMI Plate-Number Register') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Vehicles registered with the IMI API. Plates must exist here before declarations can reference them.') }}</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('trucks.plate-numbers.push-all') }}" onsubmit="return confirm('{{ __('Push every local truck that is not yet in the IMI register?') }}')">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium">{{ __('Push all missing to IMI') }}</button>
                </form>
                @if(count($remote) > 0)
                    <form method="POST" action="{{ route('trucks.plate-numbers.delete-all') }}"
                          onsubmit="return confirm('{{ __('Delete ALL :n plate(s) from the IMI register? This cannot be undone. Active declarations referencing these plates will be rejected by the API.', ['n' => count($remote)]) }}')">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">{{ __('Delete all from IMI') }}</button>
                    </form>
                @endif
                <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back to Trucks') }}</a>
            </div>
        </div>

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300 px-4 py-3 rounded-lg">{{ session('warning') }}</div>@endif
        @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>@endif

        @if($error)
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ __('Failed to load API register') }}: {{ $error }}
            </div>
        @endif

        {{-- Local trucks + push status --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-medium text-gray-700 dark:text-gray-200">
                {{ __('Your local trucks') }} ({{ $localTrucks->count() }})
            </div>
            @if($localTrucks->count())
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Plate') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Country') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Carriage') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Weight') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('In IMI?') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        @foreach($localTrucks as $t)
                            <tr>
                                <td class="px-6 py-3 font-mono text-gray-900 dark:text-white">{{ $t->plate }}</td>
                                <td class="px-6 py-3">{{ $t->registration_country ?? '—' }}</td>
                                <td class="px-6 py-3 text-xs">{{ $t->carriage_type === 'CARRIAGE_OF_PASSENGERS' ? __('Passengers') : ($t->carriage_type === 'CARRIAGE_OF_GOODS' ? __('Goods') : '—') }}</td>
                                <td class="px-6 py-3 text-xs">{{ $t->weight_type ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    @if($t->api_match)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ __('Registered') }}</span>
                                    @elseif($t->registration_country && $t->carriage_type)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">{{ __('Missing in IMI') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('Incomplete') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if(!$t->api_match)
                                        <form method="POST" action="{{ route('trucks.push-plate-number', $t->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 text-xs font-medium">{{ __('Push to IMI') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="px-6 py-6 text-sm text-gray-500">{{ __('No local trucks. Add some under Trucks first.') }}</p>
            @endif
        </div>

        {{-- API register --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-medium text-gray-700 dark:text-gray-200">
                {{ __('IMI register') }} ({{ count($remote) }})
            </div>
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex justify-end items-center">
                @if($debug)
                    <a href="{{ route('trucks.plate-numbers') }}" class="text-red-600 hover:underline">{{ __('Hide raw') }}</a>
                @else
                    <a href="{{ route('trucks.plate-numbers', ['debug' => 1]) }}" class="text-blue-600 hover:underline">{{ __('Show raw API response') }}</a>
                @endif
            </div>
            @if(count($remote))
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Plate') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Country') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Transport') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Weight') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('IMI ID') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        @foreach($remote as $r)
                            @php
                                $transport = $r['plateNumberTransportType']
                                    ?? $r['transportType']
                                    ?? null;
                                $weight = $r['vehicleWeight'] ?? null;
                                $transportLabel = match ($transport) {
                                    'CARRIAGE_OF_PASSENGERS' => __('Passengers'),
                                    'CARRIAGE_OF_GOODS' => __('Goods'),
                                    null, '' => null,
                                    default => $transport,
                                };
                                $plateId = $r['plateNumberId'] ?? '';
                            @endphp
                            <tr>
                                <td class="px-6 py-3 font-mono text-gray-900 dark:text-white">{{ $r['plateNumber'] ?? '' }}</td>
                                <td class="px-6 py-3">{{ $r['registrationCountry'] ?? '—' }}</td>
                                <td class="px-6 py-3 text-xs">
                                    @if($transportLabel === null)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        {{ $transportLabel }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs">
                                    @if($weight === null || $weight === '')
                                        <span class="text-gray-400">—</span>
                                    @else
                                        {{ $weight }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-500" title="{{ $plateId }}">{{ \Illuminate\Support\Str::limit($plateId, 14) }}</td>
                                <td class="px-6 py-3 text-right">
                                    @if($plateId)
                                        <form method="POST" action="{{ route('trucks.plate-numbers.delete') }}" class="inline"
                                              onsubmit="return confirm('{{ __('Permanently delete') }} \'{{ $r['plateNumber'] ?? '' }}\' {{ __('from the IMI register? Declarations referencing this plate will be rejected by the API.') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="plate_number_id" value="{{ $plateId }}">
                                            <input type="hidden" name="plate" value="{{ $r['plateNumber'] ?? '' }}">
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs font-medium">{{ __('Delete from IMI') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if($debug)
                                <tr class="bg-gray-50 dark:bg-gray-900/40">
                                    <td colspan="6" class="px-6 py-2">
                                        <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto">{{ json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="px-6 py-6 text-sm text-gray-500">{{ __('Nothing in the IMI register yet — push your local trucks.') }}</p>
            @endif
        </div>
    </div>
</x-layouts.app>
