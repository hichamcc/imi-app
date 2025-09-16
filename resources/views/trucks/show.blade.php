<x-layouts.app :title="__('Truck Details')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $truck->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Plate') }}: {{ $truck->plate }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('trucks.edit', $truck->id) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Edit Truck') }}
                </a>
                <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Trucks') }}
                </a>
            </div>
        </div>

        <!-- Truck Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Truck Information') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Capacity') }}</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($truck->capacity_tons, 1) }} {{ __('tons') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                    <span class="inline-flex px-2 py-1 text-sm font-semibold rounded-full {{ $truck->status_color }}">
                        {{ $truck->status }}
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Added') }}</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $truck->created_at->format('M j, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Driver Assignments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Assigned Drivers') }}</h3>
                @if($truck->canBeAssigned())
                    <button onclick="document.getElementById('assign-driver-form').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Assign Driver') }}
                    </button>
                @endif
            </div>

            <!-- Assign Driver Form -->
            @if($truck->canBeAssigned())
                <div id="assign-driver-form" class="hidden mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <form method="POST" action="{{ route('trucks.assign-driver', $truck->id) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="driver_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Select Driver') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="driver_id" id="driver_id" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                    <option value="">{{ __('Select Driver') }}</option>
                                    @foreach($availableDrivers as $driver)
                                        <option value="{{ $driver['driverId'] }}">
                                            {{ trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Notes') }} <span class="text-gray-500">({{ __('Optional') }})</span>
                                </label>
                                <input type="text" name="notes" id="notes" maxlength="1000" placeholder="{{ __('Assignment notes...') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Assign Driver') }}
                            </button>
                            <button type="button" onclick="document.getElementById('assign-driver-form').classList.add('hidden')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Active Assignments -->
            @if($truck->activeAssignments->count() > 0)
                <div class="space-y-3">
                    @foreach($truck->activeAssignments as $assignment)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $assignment->driver_name ?? 'Unknown Driver' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Driver ID') }}: {{ $assignment->driver_id }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Assigned') }}: {{ $assignment->assigned_date->format('M j, Y') }}
                                    ({{ $assignment->duration }} {{ __('days ago') }})
                                </p>
                                @if($assignment->notes)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Notes') }}: {{ $assignment->notes }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('trucks.unassign-driver', $assignment->id) }}" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 px-3 py-1 text-sm"
                                        onclick="return confirm('{{ __('Are you sure you want to unassign this driver?') }}')">
                                    {{ __('Unassign') }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No drivers assigned') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($truck->canBeAssigned())
                            {{ __('Assign drivers to this truck to get started.') }}
                        @else
                            {{ __('This truck cannot be assigned due to its current status.') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <!-- Assignment History -->
        @if($truck->assignments->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Assignment History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Driver') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Assigned Date') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Unassigned Date') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($truck->assignments as $assignment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ $assignment->driver_name ?? 'Unknown Driver' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $assignment->driver_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $assignment->assigned_date->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $assignment->unassigned_date ? $assignment->unassigned_date->format('M j, Y') : '--' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $assignment->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                                            {{ $assignment->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>