<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IMI Transport - EU Road Transport Declaration System</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Tailwind CSS -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <span class="ml-3 text-xl font-bold text-gray-900 dark:text-white">IMI Transport</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                        Get Started
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                        EU Road Transport
                        <span class="text-blue-600">Declaration System</span>
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Streamline your EU road transport posting declarations with our comprehensive management system. Handle drivers, declarations, and compliance efficiently.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-blue-600 px-6 py-3 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="bg-blue-600 px-6 py-3 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Get Started
                                </a>
                                <a href="{{ route('login') }}" class="text-gray-900 dark:text-white font-semibold hover:text-blue-600 transition-colors">
                                    Sign in <span aria-hidden="true">→</span>
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-24 bg-white dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Everything you need for transport declarations
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        Comprehensive tools to manage your EU road transport posting requirements
                    </p>
                </div>

                <div class="mt-20 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Driver Management -->
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">Driver Management</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Register, update, and manage driver information with real-time validation and search capabilities.
                        </p>
                    </div>

                    <!-- Declaration Processing -->
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">Declaration Processing</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Create, edit, submit, and track posting declarations with multi-step forms and status management.
                        </p>
                    </div>

                    <!-- Compliance Tracking -->
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">Compliance Tracking</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Monitor declaration status, track submissions, and ensure compliance with EU regulations.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="text-center">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-lg font-bold text-gray-900 dark:text-white">IMI Transport</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        EU Road Transport Posting Declaration System
                    </p>
                    <p class="text-gray-500 dark:text-gray-500 text-xs mt-2">
                        © {{ date('Y') }} IMI Transport. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>