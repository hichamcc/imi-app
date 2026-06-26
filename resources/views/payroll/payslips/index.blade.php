<x-layouts.app :title="__('Payslips')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Payslips') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('All generated payslips') }}</p>
            </div>
            <a href="{{ route('payroll-imports.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">{{ __('Go to Imports') }}</a>
        </div>

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" action="{{ route('payslips.index') }}" class="flex gap-3 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by employee name...') }}"
                    class="flex-1 min-w-[200px] rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <input type="month" name="month" value="{{ request('month') }}"
                    class="rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">{{ __('Filter') }}</button>
                @if(request('search') || request('month'))
                    <a href="{{ route('payslips.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">{{ __('Clear') }}</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($payslips->count() > 0)
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employee') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Month') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Transfer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Salary') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Per Diem') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Payment Date') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($payslips as $p)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $p->employee_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->position }} @if($p->person)· <a href="{{ route('persons.show', $p->person_id) }}" class="text-blue-600 hover:underline">{{ __('Profile') }}</a>@endif</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $p->payroll_month->format('F Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($p->transfer_amount, 2) }} {{ $p->currency }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($p->gross_salary, 2) }} {{ $p->currency }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($p->per_diem, 2) }} {{ $p->currency }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $p->payment_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                                    <a href="{{ route('payslips.view', $p->id) }}" target="_blank" class="text-blue-600 hover:text-blue-900">{{ __('View') }}</a>
                                    <a href="{{ route('payslips.download', $p->id) }}" class="text-green-600 hover:text-green-900">{{ __('Download') }}</a>
                                    <form method="POST" action="{{ route('payslips.regenerate', $p->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-purple-600 hover:text-purple-900">{{ __('Regenerate') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('payslips.destroy', $p->id) }}" class="inline" onsubmit="return confirm('{{ __('Delete this payslip?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $payslips->links() }}</div>
            @else
                <div class="text-center py-12">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('No payslips yet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Upload a bank statement under Imports and generate payslips from the review screen.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
