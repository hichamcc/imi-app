<x-layouts.app :title="__('Import Trucks')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Import Trucks') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Import multiple trucks from a text file') }}</p>
            </div>
            <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('Back to Trucks') }}
            </a>
        </div>

        <!-- Import Instructions -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-phosphor-info class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        {{ __('File Format Instructions') }}
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-200">
                        <p>{{ __('Your text file should contain one truck per line in the following format:') }}</p>
                        <div class="mt-3 p-3 bg-blue-100 dark:bg-blue-800 rounded border text-xs font-mono">
                            <div>plate_number;truck_name</div>
                      
                        </div>
                        <div class="mt-3">
                            <p class="font-medium">{{ __('Example:') }}</p>
                            <div class="mt-1 p-2 bg-blue-100 dark:bg-blue-800 rounded border text-xs font-mono">
                                ABC123;Truck Alpha<br>
                                DEF456;Truck Beta<br>
                           
                            </div>
                        </div>
                        <ul class="mt-3 list-disc list-inside space-y-1">
                            <li>{{ __('Maximum file size: 2MB') }}</li>
                            <li>{{ __('Supported formats: .txt, .csv') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('trucks.process-import') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <x-label for="truck_file" :value="__('Select Truck File')" />
                    <input
                        id="truck_file"
                        name="truck_file"
                        type="file"
                        accept=".txt,.csv"
                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-lg file:border-0
                               file:text-sm file:font-semibold
                               file:bg-blue-50 file:text-blue-700
                               file:hover:bg-blue-100
                               dark:file:bg-blue-900 dark:file:text-blue-300
                               dark:file:hover:bg-blue-800"
                        required
                    />
                    <x-error for="truck_file" class="mt-2" />
                </div>

                <!-- Actions -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('trucks.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <x-phosphor-upload class="w-4 h-4 inline mr-2" />
                            {{ __('Import Trucks') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Error Display -->
        @if(session('import_errors'))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-phosphor-warning class="h-5 w-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">
                            {{ __('Import Errors') }}
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-200">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>