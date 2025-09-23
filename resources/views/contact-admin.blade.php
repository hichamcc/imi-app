<x-layouts.app :title="__('Contact Administrator')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                <x-phosphor-warning class="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">{{ __('API Configuration Required') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ __('Your account needs API credentials to access the system.') }}
            </p>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="text-center space-y-4">
                    <x-phosphor-user-gear class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('Contact Your Administrator') }}
                        </h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('To access the EU Road Transport Posting Declaration system, you need API credentials that must be configured by an administrator.') }}
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('Required Information:') }}
                        </h3>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• {{ __('API Base URL') }}</li>
                            <li>• {{ __('API Key') }}</li>
                            <li>• {{ __('Operator ID') }}</li>
                        </ul>
                    </div>

                    <div class="pt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            {{ __('Please contact your system administrator to configure these credentials for your account.') }}
                        </p>

                        <!-- User Info for Admin Reference -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                                {{ __('Your Account Information:') }}
                            </h4>
                            <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                <div><strong>{{ __('Name:') }}</strong> {{ auth()->user()->name }}</div>
                                <div><strong>{{ __('Email:') }}</strong> {{ auth()->user()->email }}</div>
                                <div><strong>{{ __('User ID:') }}</strong> {{ auth()->user()->id }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-4">
                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Sign Out') }}
                            </button>
                        </form>

                        <button type="button" onclick="window.location.reload()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Refresh Page') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="mx-auto max-w-md w-full">
            <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                {{ __('Once your administrator configures your API credentials, refresh this page to access the system.') }}
            </div>
        </div>
    </div>
</x-layouts.app>