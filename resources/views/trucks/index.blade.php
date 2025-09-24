<x-layouts.app :title="__('Trucks')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Trucks') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Manage your fleet vehicles') }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('trucks.import') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <x-phosphor-upload class="w-4 h-4 inline mr-2" />
                    {{ __('Import Trucks') }}
                </a>
                <a href="{{ route('trucks.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Add Truck') }}
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" action="{{ route('trucks.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search by name or plate...') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <select name="status" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach($statuses as $key => $value)
                                <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>{{ __($value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Filter') }}
                        </button>
                        @if(($filters['search'] ?? false) || ($filters['status'] ?? false))
                            <a href="{{ route('trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Trucks List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($trucks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Truck') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Plate Number') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Capacity') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Operating Countries') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Assigned Drivers') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($trucks as $truck)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $truck->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Added') }} {{ $truck->created_at->format('M j, Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $truck->plate }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ number_format($truck->capacity_tons, 1) }} {{ __('tons') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $truck->status_color }}">
                                            {{ $truck->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($truck->countries && count($truck->countries) > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(array_slice($truck->countries, 0, 3) as $countryCode)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                        {{ $countryCode }}
                                                    </span>
                                                @endforeach
                                                @if(count($truck->countries) > 3)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        +{{ count($truck->countries) - 3 }} more
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('None specified') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($truck->activeAssignments->count() > 0)
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ $truck->activeAssignments->count() }} {{ __('driver(s)') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                @foreach($truck->activeAssignments as $assignment)
                                                    <div>{{ $assignment->driver_name ?? 'Unknown' }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ __('No drivers assigned') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <!-- View -->
                                            <a href="{{ route('trucks.show', $truck->id) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="{{ __('View Details') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>

                                            <!-- Edit -->
                                            <a href="{{ route('trucks.edit', $truck->id) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-1" title="{{ __('Edit Truck') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>

                                            <!-- Assign Driver -->
                                            @if($truck->canBeAssigned() && count($availableDrivers) > 0)
                                                <button onclick="openAssignModal({{ $truck->id }}, '{{ $truck->name }}')" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 p-1" title="{{ __('Assign Driver') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            <!-- Delete -->
                                            <form method="POST" action="{{ route('trucks.destroy', $truck->id) }}" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1" title="{{ __('Delete Truck') }}"
                                                        onclick="return confirm('{{ __('Are you sure you want to delete this truck?') }}')">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($trucks->hasPages())
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                        {{ $trucks->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No trucks found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($filters['search'] ?? false)
                            {{ __('No trucks match your search criteria.') }}
                        @else
                            {{ __('Get started by adding your first truck.') }}
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('trucks.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('Add Truck') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Assign Driver Modal -->
        <div id="assignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modalTitle">{{ __('Assign Driver') }}</h3>
                        <button onclick="closeAssignModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form id="assignForm" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="modal_driver_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Select Driver') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="driver_id" id="modal_driver_id" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">{{ __('Select Driver') }}</option>
                                @foreach($availableDrivers as $driver)
                                    <option value="{{ $driver['driverId'] }}">
                                        {{ trim(($driver['driverLatinFirstName'] ?? '') . ' ' . ($driver['driverLatinLastName'] ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="modal_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Notes') }} <span class="text-gray-500">({{ __('Optional') }})</span>
                            </label>
                            <input type="text" name="notes" id="modal_notes" maxlength="1000" placeholder="{{ __('Assignment notes...') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeAssignModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Assign Driver') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAssignModal(truckId, truckName) {
            document.getElementById('modalTitle').textContent = '{{ __("Assign Driver to") }} ' + truckName;
            document.getElementById('assignForm').action = '/trucks/' + truckId + '/assign-driver';
            document.getElementById('modal_driver_id').value = '';
            document.getElementById('modal_notes').value = '';
            document.getElementById('assignModal').classList.remove('hidden');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAssignModal();
            }
        });
    </script>
</x-layouts.app>