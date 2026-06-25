<x-layouts.app :title="__('Payroll Imports')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Payroll Imports') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Bank statements imported to generate payslips') }}</p>
            </div>
            <a href="{{ route('payroll-imports.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                {{ __('New Import') }}
            </a>
        </div>

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>@endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($imports->count() > 0)
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('File') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Month') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Account') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Rows') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Uploaded') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($imports as $import)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $import->original_filename }}
                                    @if(!$import->is_payroll)<span class="ml-2 text-xs text-gray-500">(not payroll)</span>@endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $import->payroll_month->format('F Y') }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $import->account_number ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $import->total_rows }}</td>
                                <td class="px-6 py-4">
                                    @php $statusColor = match($import->status){'reviewed'=>'bg-blue-100 text-blue-800','generated'=>'bg-green-100 text-green-800',default=>'bg-yellow-100 text-yellow-800'}; @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">{{ ucfirst($import->status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $import->created_at->format('M j, Y H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    <a href="{{ route('payroll-imports.review', $import->id) }}" class="text-blue-600 hover:text-blue-900">{{ __('Review') }}</a>
                                    <form method="POST" action="{{ route('payroll-imports.destroy', $import->id) }}" class="inline" onsubmit="return confirm('{{ __('Delete this import and all its rows?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $imports->links() }}</div>
            @else
                <div class="text-center py-12">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('No imports yet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Upload a bank statement to start generating payslips.') }}</p>
                    <a href="{{ route('payroll-imports.create') }}" class="mt-4 inline-flex px-4 py-2 bg-blue-600 text-white rounded-lg">{{ __('New Import') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
