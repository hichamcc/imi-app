<x-layouts.app :title="__('New Payroll Import')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('New Payroll Import') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Upload a bank statement (.xlsx, .xls or .csv).') }}</p>
            </div>
            <a href="{{ route('payroll-imports.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">{{ __('Back') }}</a>
        </div>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300 px-4 py-3 rounded-lg text-sm">
            💡 <strong>{{ __('Tip') }}:</strong>
            {{ __('Before uploading the bank file, make sure your drivers exist as HR Persons so the importer can match them automatically.') }}
            <a href="{{ route('persons.import-from-imi') }}" class="font-medium underline hover:no-underline">{{ __('Import drivers from IMI now') }}</a>
            {{ __('or') }}
            <a href="{{ route('persons.index') }}" class="font-medium underline hover:no-underline">{{ __('view existing persons') }}</a>.
        </div>

        <form method="POST" action="{{ route('payroll-imports.store') }}" enctype="multipart/form-data"
              class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6 max-w-2xl">
            @csrf

            <div x-data="{ filename: '', size: 0 }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Bank Statement File') }} *</label>
                <label for="bank_file_input"
                       class="flex flex-col items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:bg-gray-700/40 dark:hover:bg-gray-700/70 transition-colors">
                    <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1A5.5 5.5 0 0118 16h-1m-6-4l-2 2m0 0l-2-2m2 2V4"></path>
                    </svg>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span x-show="!filename" class="font-medium text-blue-600 dark:text-blue-400">{{ __('Click to choose a file') }}</span>
                        <span x-show="!filename" class="text-gray-500"> {{ __('or drag and drop') }}</span>
                        <span x-show="filename" x-cloak class="font-medium text-gray-900 dark:text-white" x-text="filename"></span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        <span x-show="!filename">.xlsx · .xls · .csv — {{ __('max 10 MB') }}</span>
                        <span x-show="filename" x-cloak x-text="(size / 1024).toFixed(1) + ' KB'"></span>
                    </p>
                    <input id="bank_file_input" type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="sr-only"
                           x-on:change="filename = $event.target.files[0]?.name || ''; size = $event.target.files[0]?.size || 0">
                </label>
                <p class="mt-2 text-xs text-gray-500">{{ __('Expected columns: Account Number, Date, Description, Debit, Credit, Balance.') }}</p>
                @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Payroll Month') }} *</label>
                <input type="date" name="payroll_month" value="{{ old('payroll_month', now()->startOfMonth()->format('Y-m-d')) }}" required
                    class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">{{ __('The month every generated payslip will be labelled with. Pick any day in that month.') }}</p>
                @error('payroll_month')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_payroll" value="1" {{ old('is_payroll', true) ? 'checked' : '' }}
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ __('This is a payroll import') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Tick this if the file contains salary payments. Uncheck to just archive the statement without generating payslips.') }}
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('payroll-imports.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">{{ __('Cancel') }}</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">{{ __('Upload & Parse') }}</button>
            </div>
        </form>
    </div>
</x-layouts.app>
