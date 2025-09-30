<x-layouts.app :title="__('User Management')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Management') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage and impersonate users') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Back to Dashboard') }}
            </a>
        </div>

        <!-- Users List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('Active Users') }} ({{ $users->count() }})
                </h3>
            </div>

            @if($users->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($users as $user)
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center space-x-4">
                                <!-- User Avatar -->
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ $user->initials() }}
                                        </span>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </p>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->hasValidApiCredentials() ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                            {{ $user->hasValidApiCredentials() ? 'API Connected' : 'No API Credentials' }}
                                        </span>
                                        @if($user->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-3">
                                @if($user->canBeImpersonated())
                                    <form method="POST" action="{{ route('impersonate', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                                onclick="return confirm('Are you sure you want to impersonate {{ $user->name }}?')">
                                            <x-phosphor-user-switch class="w-4 h-4 mr-1" />
                                            {{ __('Impersonate') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">
                                        {{ __('Cannot impersonate') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center">
                    <x-phosphor-users class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No users found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No active non-admin users are available for impersonation.') }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-phosphor-info class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('Impersonation Guidelines') }}
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('Only active non-admin users can be impersonated') }}</li>
                            <li>{{ __('You will see exactly what the user sees with their API credentials') }}</li>
                            <li>{{ __('Use the "Stop Impersonating" button to return to your admin account') }}</li>
                            <li>{{ __('All actions performed will be logged under the impersonated user') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>