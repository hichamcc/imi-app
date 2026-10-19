<x-layouts.app :title="__('Tacho Activity Report')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Tacho Activity Report') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    {{ __('Drivers with active IMI declarations cross-checked against MAPON tacho activity in the last') }}
                    <strong>{{ $windowDays }}</strong> {{ __('days') }}.
                </p>
            </div>
            <form method="GET" action="{{ route('admin.tacho-activity.index') }}" class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('Organisation') }}</label>
                    <select name="user_id" onchange="this.form.submit()"
                            class="rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @if($apiUsers->isEmpty())
                            <option value="">— {{ __('no users with API credentials') }} —</option>
                        @endif
                        @foreach($apiUsers as $u)
                            <option value="{{ $u->id }}" {{ (int) $selectedUserId === (int) $u->id ? 'selected' : '' }}>
                                {{ $u->name }} (ID: {{ $u->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('Window (days)') }}</label>
                    <input type="number" name="window_days" value="{{ $windowDays }}" min="1" max="365"
                           class="rounded-lg border border-gray-200 px-3 py-2 w-24 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">{{ __('Run') }}</button>
            </form>
        </div>

        @if(!$maponConfigured)
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
                ⚠️ {{ __('MAPON_API_KEY is not set in .env — nothing to cross-check against. Add it and reload this page.') }}
            </div>
        @endif
        @if($error)
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
                {{ $error }}
            </div>
        @endif

        @if($stats)
            <div x-data="{ filter: null }" class="space-y-3">
                <div class="grid grid-cols-4 gap-3">
                    {{-- Cards toggle the Alpine `filter` state; clicking the same card again clears it. --}}
                    <button type="button" @click="filter = null"
                            :class="filter === null ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-gray-900' : ''"
                            class="text-left block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ __('Checked') }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['checked'] }}</div>
                    </button>
                    <button type="button" @click="filter = filter === 'active' ? null : 'active'"
                            :class="filter === 'active' ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-gray-900' : ''"
                            class="text-left block bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 p-4 hover:shadow-md transition-shadow">
                        <div class="text-xs text-green-700 dark:text-green-300 uppercase">{{ __('Active') }}</div>
                        <div class="text-2xl font-bold text-green-800 dark:text-green-200">{{ $stats['active'] }}</div>
                    </button>
                    <button type="button" @click="filter = filter === 'stale' ? null : 'stale'"
                            :class="filter === 'stale' ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-gray-900' : ''"
                            class="text-left block bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4 hover:shadow-md transition-shadow">
                        <div class="text-xs text-red-700 dark:text-red-300 uppercase">{{ __('Inactive (>') }}{{ $windowDays }}d)</div>
                        <div class="text-2xl font-bold text-red-800 dark:text-red-200">{{ $stats['stale'] }}</div>
                    </button>
                    <button type="button" @click="filter = filter === 'no_mapon' ? null : 'no_mapon'"
                            :class="filter === 'no_mapon' ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-gray-900' : ''"
                            class="text-left block bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-4 hover:shadow-md transition-shadow">
                        <div class="text-xs text-yellow-700 dark:text-yellow-300 uppercase">{{ __('Not in MAPON') }}</div>
                        <div class="text-2xl font-bold text-yellow-800 dark:text-yellow-200">{{ $stats['no_mapon'] }}</div>
                    </button>
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-400" x-show="filter !== null" x-cloak>
                    {{ __('Filtered by') }}: <strong x-text="filter"></strong> ·
                    <button type="button" @click="filter = null" class="text-blue-600 hover:underline">{{ __('clear filter') }}</button>
                </div>

                {{-- The table is reached via a sibling selector; filter visibility is driven by the Alpine scope above. --}}
                <template x-effect="document.querySelectorAll('tr[data-status]').forEach(r => r.style.display = (!filter || r.dataset.status === filter) ? '' : 'none')"></template>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if(count($drivers) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Driver') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Active IMI countries') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('MAPON match') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Last activity') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($drivers as $row)
                                <tr data-status="{{ $row['status'] }}">
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</div>
                                        @if($row['driver_id'])
                                            <div class="text-xs text-gray-500 font-mono">{{ substr($row['driver_id'], 0, 8) }}…</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($row['active_countries'] as $c)
                                                <span class="inline-flex px-1.5 py-0.5 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">{{ $c }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if($row['mapon_matched'])
                                            <span class="inline-flex px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ __('Yes') }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if($row['last_activity'])
                                            <div class="text-gray-900 dark:text-white">{{ $row['last_activity'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $row['days_since'] }} {{ __('day(s) ago') }}</div>
                                        @else
                                            <span class="text-xs text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if($row['status'] === 'stale')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                🔴 {{ __('Inactive') }}
                                            </span>
                                        @elseif($row['status'] === 'no_mapon')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                🟡 {{ __('Not in MAPON') }}
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                🟢 {{ __('Active') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif(!$error && $maponConfigured)
                <div class="text-center py-12 text-gray-500 dark:text-gray-400 text-sm">
                    {{ __('No drivers with active IMI declarations found for this organisation.') }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
