<x-layouts.app :title="__('Review Payroll Import')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Review Payroll Import') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $import->original_filename }} — {{ $import->payroll_month->format('F Y') }}
                    @if($import->account_number) · <span class="font-mono text-xs">{{ $import->account_number }}</span>@endif
                </p>
            </div>
            <div class="flex gap-2">
                @if($import->payslips_generated > 0)
                    <span class="px-3 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-lg text-sm font-medium">
                        {{ $import->payslips_generated }} {{ __('payslips generated') }}
                    </span>
                @endif
                <form method="POST" action="{{ route('payroll-imports.generate-payslips', $import->id) }}"
                      onsubmit="return confirm('{{ __('Generate payslips for every ticked row with a matched person? Existing payslips for the same row will be replaced.') }}')">
                    @csrf
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium">
                        {{ __('Generate Payslips') }}
                    </button>
                </form>
                <a href="{{ route('payroll-imports.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">{{ __('Back') }}</a>
            </div>
        </div>

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>@endif

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-800 dark:text-blue-200">
            <strong>{{ __('How this works') }}:</strong>
            {{ __('Tick the rows that are payroll payments. The salary and per-diem are calculated automatically from the debit amount (per diem = 50% of transfer, capped at 1,800 €).') }}
            {{ __('Person matching:') }} <span class="inline-flex px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded">{{ __('local Person') }}</span>
            · <span class="inline-flex px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">{{ __('IMI driver') }}</span>
            · <span class="inline-flex px-1.5 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded">⚠️ {{ __('missing') }}</span>
        </div>

        <form method="POST" action="{{ route('payroll-imports.review.update', $import->id) }}">
            @csrf @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $rows->count() }} {{ __('rows parsed') }} · {{ $rows->where('looks_like_payroll', true)->count() }} {{ __('look like payroll') }}
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="toggleAll(true)" class="text-xs text-blue-600 hover:text-blue-800">{{ __('Check all') }}</button>
                        <button type="button" onclick="toggleAll(false)" class="text-xs text-gray-600 hover:text-gray-800">{{ __('Uncheck all') }}</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Payroll') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Description') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Debit') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Credit') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Person Name') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Salary') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Per Diem') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Match') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($rows as $row)
                                @php
                                    $perDiem = min((float) $row->debit * 0.5, 1800.0);
                                    $salary  = max((float) $row->debit - $perDiem, 0);
                                    $imiHits = $imiPresence[$row->id] ?? [];
                                    $isCharge = $row->credit > 0;
                                @endphp
                                <tr class="{{ $row->looks_like_payroll ? '' : 'bg-gray-50/50 dark:bg-gray-700/30' }}">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="rows[{{ $row->id }}][is_payroll]" value="1"
                                            class="payroll-cb h-4 w-4 text-blue-600 rounded border-gray-300"
                                            {{ $row->is_payroll ? 'checked' : '' }}
                                            {{ $isCharge ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $row->date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 max-w-xs">
                                        @if($row->reference)<span class="text-gray-400 font-mono">{{ $row->reference }}</span> @endif
                                        <span class="block truncate" title="{{ $row->description }}">{{ $row->description }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-sm font-medium {{ $row->debit > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        @if($row->debit > 0){{ number_format($row->debit, 2) }}@else—@endif
                                    </td>
                                    <td class="px-3 py-2 text-sm font-medium {{ $row->credit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                        @if($row->credit > 0){{ number_format($row->credit, 2) }}@else—@endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="rows[{{ $row->id }}][parsed_name]" value="{{ $row->parsed_name }}"
                                            class="w-44 rounded border-gray-200 px-2 py-1 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            placeholder="—">
                                    </td>
                                    <td class="px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        @if($row->debit > 0){{ number_format($salary, 2) }} €@else—@endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        @if($row->debit > 0){{ number_format($perDiem, 2) }} €@else—@endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($row->matched_person_id)
                                            <input type="hidden" name="rows[{{ $row->id }}][matched_person_id]" value="{{ $row->matched_person_id }}">
                                            <a href="{{ route('persons.show', $row->matched_person_id) }}" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                {{ $row->matchedPerson?->full_name ?? __('Local Person') }}
                                            </a>
                                        @elseif(count($imiHits) > 0)
                                            <div class="space-y-0.5">
                                                @foreach($imiHits as $hit)
                                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                        {{ $hit['company_name'] }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($row->parsed_name)
                                            <div class="space-y-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300" title="{{ __('Not found in local persons or any IMI org') }}">
                                                    ⚠️ {{ __('Missing') }}
                                                </span>
                                                <button form="create-person-{{ $row->id }}" type="submit" class="text-xs text-blue-600 hover:text-blue-800 underline">
                                                    {{ __('Create person') }}
                                                </button>
                                                <form id="create-person-{{ $row->id }}" method="POST" action="{{ route('payroll-imports.rows.create-person', [$import->id, $row->id]) }}" class="hidden">
                                                    @csrf
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('payroll-imports.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">{{ __('Cancel') }}</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">{{ __('Save Review') }}</button>
            </div>
        </form>
    </div>

    <script>
        function toggleAll(state) {
            document.querySelectorAll('.payroll-cb').forEach(cb => { if (!cb.disabled) cb.checked = state; });
        }
    </script>
</x-layouts.app>
