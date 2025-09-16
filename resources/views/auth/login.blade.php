<x-layouts.auth :title="__('Log in')">
<div class="space-y-6">
    <!-- Custom Logo/Branding -->
    <div class="text-center mb-6">
        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">IMI Transport</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">EU Road Transport Declaration System</p>
    </div>

    <x-auth-header :title="__('Welcome back')" :description="__('Sign in to manage your transport declarations')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-form method="post" :action="route('login')" class="space-y-6">
        <x-input
            type="email"
            :label="__('Email address')"
            name="email"
            required
            autofocus
            autocomplete="email"
        />

        <div class="relative">
            <x-input
                type="password"
                :label="__('Password')"
                name="password"
                required
                autocomplete="current-password"
            />

            @if (Route::has('password.request'))
                <x-link class="absolute right-0 top-0 text-sm" :href="route('password.request')">
                    {{ __('Forgot your password?') }}
                </x-link>
            @endif
        </div>

        <x-checkbox name="remember" :label="__('Remember me')" />

        <x-button class="w-full">{{ __('Log in') }}</x-button>
    </x-form>

    @if (Route::has('register'))
      <p class="text-center text-sm text-gray-600 dark:text-gray-400">
          <span>{{ __('Don\'t have an account?') }}</span>
          <x-link :href="route('register')">Sign up</x-link>
      </p>
    @endif
</div>
</x-layouts.auth>
