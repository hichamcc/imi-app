<x-layouts.app :title="__('Create User')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create New User') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Add a new user to the system') }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Back to Users') }}
            </a>
        </div>

        <!-- Create Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Basic Information') }}</h3>

                        <div>
                            <x-label for="name" :value="__('Full Name')" />
                            <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-error for="name" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="email" :value="__('Email Address')" />
                            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-error for="email" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="password" :value="__('Password')" />
                            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                            <x-error for="password" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Minimum 8 characters') }}</p>
                        </div>
                    </div>

                    <!-- API Credentials -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('API Credentials') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Configure API access for this user') }}</p>

                        <div>
                            <x-label for="api_base_url" :value="__('API Base URL')" />
                            <x-input id="api_base_url" class="block mt-1 w-full" type="url" name="api_base_url" :value="old('api_base_url', 'https://api.postingdeclaration.eu')" />
                            <x-error for="api_base_url" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="api_key" :value="__('API Key')" />
                            <x-input id="api_key" class="block mt-1 w-full" type="text" name="api_key" :value="old('api_key')" />
                            <x-error for="api_key" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="api_operator_id" :value="__('Operator ID')" />
                            <x-input id="api_operator_id" class="block mt-1 w-full" type="text" name="api_operator_id" :value="old('api_operator_id')" />
                            <x-error for="api_operator_id" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- User Settings -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('User Settings') }}</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input id="is_admin" name="is_admin" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ old('is_admin') ? 'checked' : '' }}>
                            <label for="is_admin" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Administrator privileges') }}
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">{{ __('Administrators can manage other users and system settings') }}</p>

                        <div class="flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Active user') }}
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">{{ __('Inactive users cannot log in to the system') }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Create User') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>