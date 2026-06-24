@php($p = $person ?? null)

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Identity --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('First Name') }} *</label>
        <input type="text" name="first_name" value="{{ old('first_name', $p?->first_name) }}" required maxlength="100"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Last Name') }} *</label>
        <input type="text" name="last_name" value="{{ old('last_name', $p?->last_name) }}" required maxlength="100"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Date of Birth') }}</label>
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $p?->date_of_birth?->format('Y-m-d')) }}"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('date_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Position') }} *</label>
        <input type="text" name="position" value="{{ old('position', $p?->position ?? 'Driver') }}" required maxlength="100"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        @error('position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Document --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Document Type') }}</label>
        <select name="document_type" class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ __('— Select —') }}</option>
            @foreach($documentTypes as $key => $label)
                <option value="{{ $key }}" {{ old('document_type', $p?->document_type) == $key ? 'selected' : '' }}>{{ __($label) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Document Number') }}</label>
        <input type="text" name="document_number" value="{{ old('document_number', $p?->document_number) }}" maxlength="50"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Issuing Country') }}</label>
        <select name="document_issuing_country" class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ __('— Select —') }}</option>
            @foreach($countries as $code => $name)
                <option value="{{ $code }}" {{ old('document_issuing_country', $p?->document_issuing_country) == $code ? 'selected' : '' }}>{{ $code }} — {{ __($name) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('License Number') }}</label>
        <input type="text" name="license_number" value="{{ old('license_number', $p?->license_number) }}" maxlength="50"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>

    {{-- Address --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Street Address') }}</label>
        <input type="text" name="address_street" value="{{ old('address_street', $p?->address_street) }}"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Post Code') }}</label>
        <input type="text" name="address_post_code" value="{{ old('address_post_code', $p?->address_post_code) }}" maxlength="20"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('City') }}</label>
        <input type="text" name="address_city" value="{{ old('address_city', $p?->address_city) }}" maxlength="100"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Country') }}</label>
        <select name="address_country" class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ __('— Select —') }}</option>
            @foreach($countries as $code => $name)
                <option value="{{ $code }}" {{ old('address_country', $p?->address_country) == $code ? 'selected' : '' }}>{{ $code }} — {{ __($name) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Applicable Law') }}</label>
        <select name="applicable_law" class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">{{ __('— Select —') }}</option>
            @foreach($countries as $code => $name)
                <option value="{{ $code }}" {{ old('applicable_law', $p?->applicable_law ?? auth()->user()->applicable_law) == $code ? 'selected' : '' }}>{{ $code }} — {{ __($name) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Contract Start Date') }}</label>
        <input type="date" name="contract_start_date" value="{{ old('contract_start_date', $p?->contract_start_date?->format('Y-m-d')) }}"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>

    {{-- Contact + Bank + Salary --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Email') }}</label>
        <input type="email" name="email" value="{{ old('email', $p?->email) }}"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $p?->phone) }}" maxlength="50"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Bank IBAN') }}</label>
        <input type="text" name="bank_iban" value="{{ old('bank_iban', $p?->bank_iban) }}" maxlength="50"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Bank SWIFT/BIC') }}</label>
        <input type="text" name="bank_swift" value="{{ old('bank_swift', $p?->bank_swift) }}" maxlength="20"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Monthly Salary (EUR)') }}</label>
        <input type="number" step="0.01" min="0" name="monthly_salary" value="{{ old('monthly_salary', $p?->monthly_salary) }}"
            class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="block w-full rounded-lg border border-gray-200 px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $p?->notes) }}</textarea>
    </div>
</div>
