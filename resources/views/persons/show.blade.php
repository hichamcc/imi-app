<x-layouts.app :title="$person->full_name">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-xl font-medium text-blue-600 dark:text-blue-400">
                    {{ $person->initials }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $person->full_name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $person->position }}</p>
                </div>
            </div>
            <div class="flex gap-2" x-data="{ contractOpen: false }">
                <button type="button" @click="contractOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium">
                    {{ __('Generate Employment Agreement') }}
                </button>
                <a href="{{ route('persons.edit', $person->id) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">{{ __('Edit') }}</a>
                <a href="{{ route('persons.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back') }}</a>

                {{-- Contract generation modal --}}
                <div x-show="contractOpen" x-cloak class="fixed inset-0 z-50 bg-gray-900/60 flex items-center justify-center p-4" @click.self="contractOpen = false">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-xl">
                        <form method="POST" action="{{ route('persons.contract.post', $person->id) }}" target="_blank">
                            @csrf
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Generate Employment Agreement') }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Add optional notes that will be included in the PDF before the signature block.') }}</p>
                            </div>
                            <div class="px-6 py-4 space-y-4">
                                <div class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/40 rounded p-3 space-y-0.5">
                                    <div><strong>{{ __('Employee') }}:</strong> {{ $person->full_name }}</div>
                                    <div><strong>{{ __('Document') }}:</strong> {{ $person->document_number ?? '—' }}</div>
                                    <div><strong>{{ __('Contract start') }}:</strong> {{ $person->contract_start_date?->format('d M Y') ?? __('today') }}</div>
                                    <div><strong>{{ __('IBAN') }}:</strong> {{ $person->bank_iban ?? '—' }}</div>
                                </div>
                                <div>
                                    <label for="contract_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Additional notes') }} <span class="text-gray-400 text-xs">({{ __('optional') }})</span>
                                    </label>
                                    <textarea name="notes" id="contract_notes" rows="5" maxlength="3000"
                                              placeholder="{{ __('e.g. special clauses, bonuses, agreed exceptions, internal references…') }}"
                                              class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                            </div>
                            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                                <button type="button" @click="contractOpen = false" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">{{ __('Cancel') }}</button>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">{{ __('Download PDF') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300 px-4 py-3 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Identity --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Identity') }}</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->date_of_birth?->format('M j, Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Document') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->document_type ?? '—' }} {{ $person->document_number ? '· ' . $person->document_number : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Issuing Country') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->document_issuing_country ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('License Number') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->license_number ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Address --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Address & Contract') }}</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="md:col-span-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Address') }}</dt>
                            <dd class="text-gray-900 dark:text-white">
                                {{ $person->address_street }}
                                @if($person->address_post_code || $person->address_city || $person->address_country)
                                    <div>{{ trim(($person->address_post_code ?? '') . ' ' . ($person->address_city ?? '')) }} {{ $person->address_country }}</div>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Contract Start') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->contract_start_date?->format('M j, Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Applicable Law') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->applicable_law ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Bank / Salary --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Banking & Salary') }}</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('IBAN') }}</dt>
                            <dd class="text-gray-900 dark:text-white font-mono">{{ $person->bank_iban ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('SWIFT/BIC') }}</dt>
                            <dd class="text-gray-900 dark:text-white font-mono">{{ $person->bank_swift ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Monthly Salary') }}</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $person->monthly_salary ? number_format($person->monthly_salary, 2) . ' EUR' : '—' }}</dd>
                        </div>
                    </dl>
                </div>

                @if($person->notes)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Notes') }}</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $person->notes }}</p>
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Payslips') }}</h3>
                    @if(($payslips ?? collect())->count() > 0)
                        <table class="min-w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="py-2 text-left">{{ __('Month') }}</th>
                                    <th class="py-2 text-left">{{ __('Salary') }}</th>
                                    <th class="py-2 text-left">{{ __('Per Diem') }}</th>
                                    <th class="py-2 text-left">{{ __('Transfer') }}</th>
                                    <th class="py-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($payslips as $p)
                                    <tr>
                                        <td class="py-2 text-gray-900 dark:text-white">{{ $p->payroll_month->format('M Y') }}</td>
                                        <td class="py-2 text-gray-900 dark:text-white font-medium">{{ number_format($p->gross_salary, 2) }} {{ $p->currency }}</td>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ number_format($p->per_diem, 2) }} {{ $p->currency }}</td>
                                        <td class="py-2 text-gray-700 dark:text-gray-300">{{ number_format($p->transfer_amount, 2) }} {{ $p->currency }}</td>
                                        <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                            <a href="{{ route('payslips.view', $p->id) }}" target="_blank" class="text-blue-600 hover:text-blue-900 text-xs">{{ __('View') }}</a>
                                            <a href="{{ route('payslips.download', $p->id) }}" class="text-green-600 hover:text-green-900 text-xs">{{ __('Download') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No payslips yet.') }}</p>
                    @endif
                </div>
            </div>

            {{-- File archive sidebar --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('IMI Status') }}</h3>
                    @if($person->imi_driver_id)
                        <div class="text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ __('Linked to IMI') }}</span>
                            @if($person->imiUser)
                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">{{ __('Created under') }}: <span class="font-medium">{{ $person->imiUser->name }}</span></p>
                            @endif
                            <p class="mt-1 text-gray-500 dark:text-gray-400 text-xs font-mono break-all">{{ $person->imi_driver_id }}</p>
                        </div>
                    @else
                        <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-3">⚠️ {{ __('This person is not linked to an IMI driver yet.') }}</p>
                        <form method="POST" action="{{ route('persons.sync-to-imi', $person->id) }}">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium text-sm">
                                {{ __('Sync to IMI now') }}
                            </button>
                        </form>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('All IMI driver fields must be filled (DOB, license, document, full address, contract start, applicable law).') }}</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('File Archive') }}</h3>

                    <form method="POST" action="{{ route('persons.files.upload', $person->id) }}" enctype="multipart/form-data" class="space-y-3 mb-4">
                        @csrf
                        <input type="text" name="label" placeholder="{{ __('Label (optional)') }}" maxlength="255"
                            class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <input type="file" name="file" required class="block w-full text-sm text-gray-700 dark:text-gray-300">
                        @error('file')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium text-sm">{{ __('Upload File') }}</button>
                    </form>

                    @if($files->count() > 0)
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($files as $file)
                                <li class="py-3 flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('persons.files.download', [$person->id, $file->id]) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 truncate block">
                                            {{ $file->original_name }}
                                        </a>
                                        @if($file->label)<div class="text-xs text-gray-500">{{ $file->label }}</div>@endif
                                        <div class="text-xs text-gray-400">{{ $file->human_size }} · {{ $file->created_at->format('M j, Y') }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('persons.files.destroy', [$person->id, $file->id]) }}" onsubmit="return confirm('{{ __('Delete this file?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs">{{ __('Delete') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No files yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
