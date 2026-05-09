<x-layouts.app :title="__('Profile | Settings')">
<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        @php
            $lawCountries = [
                'AM' => 'Armenia', 'AT' => 'Austria', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
                'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
                'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
                'HU' => 'Hungary', 'IN' => 'India', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
                'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
                'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
                'SI' => 'Slovenia', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SE' => 'Sweden', 'UA' => 'Ukraine',
                'ZW' => 'Zimbabwe',
            ];
        @endphp
        <x-form method="put" action="{{ route('settings.profile.update') }}" class="my-6 w-full space-y-6">
            <x-input type="text" :label="__('Name')" :value="$user->name" name="name" required autofocus autocomplete="name" />

            <div>
                <x-input type="email" :label="__('Email')" :value="$user->email" name="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <x-text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <x-button variant="link" :formaction="route('verification.store')" name="_method" value="post">
                                {{ __('Click here to re-send the verification email.') }}
                            </x-button>
                        </x-text>

                        @if (session('status') === 'verification-link-sent')
                            <x-text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </x-text>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <x-label for="applicable_law">{{ __('Applicable Law (Company Home Country)') }}</x-label>
                <select name="applicable_law" id="applicable_law"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">{{ __('— Not set —') }}</option>
                    @foreach($lawCountries as $code => $name)
                        <option value="{{ $code }}" {{ $user->applicable_law === $code ? 'selected' : '' }}>
                            {{ $code }} — {{ $name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('This is applied automatically as the Applicable Law for all drivers and cloning operations in your company profile.') }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-button class="w-full">{{ __('Save') }}</x-button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </x-form>

        <section class="mt-10 space-y-6">
            <div class="relative mb-5">
                <x-heading>{{ __('Delete account') }}</x-heading>
                <x-subheading>{{ __('Delete your account and all of its resources') }}</x-subheading>
            </div>

            <x-button type="button" variant="danger" x-init="" x-on:click="$dispatch('modal:open', 'confirm_user_deletion')">
                {{ __('Delete account') }}
            </x-button>

            <x-modal id="confirm_user_deletion" :open="$errors->has('password')">
                <x-form method="delete" action="{{ route('settings.profile.destroy') }}" class="space-y-6">
                    <div>
                        <x-heading size="lg">{{ __('Are you sure you want to delete your account?') }}</x-heading>
                        <x-subheading>
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </x-subheading>
                    </div>

                    <x-input type="password" :label="__('Password')" name="password" />

                    <div class="flex justify-end space-x-2">
                        <x-button variant="secondary" form="confirm_user_deletion_close">{{ __('Cancel') }}</x-button>
                        <x-button variant="danger">{{ __('Delete account') }}</x-button>
                    </div>
                </x-form>
            </x-modal>
        </section>
    </x-settings.layout>
</section>
</x-layouts.app>
