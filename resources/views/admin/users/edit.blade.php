<x-layouts.app :title="__('Edit User')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit User') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Update user information and settings') }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('View User') }}
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Users') }}
                </a>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Basic Information') }}</h3>

                        <div>
                            <x-label for="name" :value="__('Full Name')" />
                            <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                            <x-error for="name" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="email" :value="__('Email Address')" />
                            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                            <x-error for="email" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="password" :value="__('New Password')" />
                            <x-input id="password" class="block mt-1 w-full" type="password" name="password" />
                            <x-error for="password" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Leave blank to keep current password') }}</p>
                        </div>
                    </div>

                    <!-- API Credentials -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('API Credentials') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Configure API access for this user') }}</p>

                        <div>
                            <x-label for="api_base_url" :value="__('API Base URL')" />
                            <x-input id="api_base_url" class="block mt-1 w-full" type="url" name="api_base_url" :value="old('api_base_url', $user->api_base_url)" />
                            <x-error for="api_base_url" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="api_key" :value="__('API Key')" />
                            <x-input id="api_key" class="block mt-1 w-full" type="text" name="api_key" :value="old('api_key', $user->api_key ? '••••••••••••' : '')" />
                            <x-error for="api_key" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Enter new API key to replace existing one') }}</p>
                        </div>

                        <div>
                            <x-label for="api_operator_id" :value="__('Operator ID')" />
                            <x-input id="api_operator_id" class="block mt-1 w-full" type="text" name="api_operator_id" :value="old('api_operator_id', $user->api_operator_id)" />
                            <x-error for="api_operator_id" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- User Settings -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('User Settings') }}</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input id="is_admin" name="is_admin" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                            <label for="is_admin" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Administrator privileges') }}
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">{{ __('Administrators can manage other users and system settings') }}</p>

                        <div class="flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Active user') }}
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">{{ __('Inactive users cannot log in to the system') }}</p>

                        @if($user->id === auth()->id())
                            <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                            {{ __('You are editing your own account. Be careful with admin privileges and active status changes.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Update User') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>