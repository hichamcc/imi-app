<x-layouts.app :title="__('Declaration Details')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Declaration Details') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Declaration') }} #{{ substr($declaration['declarationId'] ?? '', 0, 8) }}</p>
            </div>
            <div class="flex space-x-3">
                <!-- Print and Email Actions -->
                <button onclick="openPrintModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    📄 {{ __('Print') }}
                </button>
                <button onclick="openEmailModal()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    ✉️ {{ __('Email') }}
                </button>

                @if(($declaration['declarationStatus'] ?? '') === 'DRAFT')
                    <a href="{{ route('declarations.edit', $declaration['declarationId']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        {{ __('Edit Declaration') }}
                    </a>
                    <form method="POST" action="{{ route('declarations.submit', $declaration['declarationId']) }}" class="inline-block">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                onclick="return confirm('{{ __('Are you sure you want to submit this declaration?') }}')">
                            {{ __('Submit Declaration') }}
                        </button>
                    </form>
                @elseif(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                    <form method="POST" action="{{ route('declarations.withdraw', $declaration['declarationId']) }}" class="inline-block">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                onclick="return confirm('{{ __('Are you sure you want to withdraw this declaration?') }}')">
                            {{ __('Withdraw Declaration') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    {{ __('Back to Declarations') }}
                </a>
            </div>
        </div>

        <!-- Declaration Information -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status and Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Declaration Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Declaration ID') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $declaration['declarationId'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                            <dd class="mt-1">
                                @php
                                    $status = $declaration['declarationStatus'] ?? '';
                                    $statusColors = [
                                        'DRAFT' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'SUBMITTED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'WITHDRAWN' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'EXPIRED' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Posting Country') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationPostingCountry'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Last Updated') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ isset($declaration['declarationLastUpdate']) ? \Carbon\Carbon::parse($declaration['declarationLastUpdate'])->format('M j, Y H:i') : 'N/A' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Period Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Declaration Period') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Start Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationStartDate'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('End Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['declarationEndDate'] ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Driver Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Driver Information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Driver Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $declaration['driverFullName'] ?? (($declaration['driverLatinFirstName'] ?? '') . ' ' . ($declaration['driverLatinLastName'] ?? '')) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverDateOfBirth'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('License Number') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverLicenseNumber'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Document Type') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $declaration['driverDocumentType'] ?? 'N/A' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Operation Details -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Operation Details') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Operation Types') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationOperationType']) && is_array($declaration['declarationOperationType']))
                                    @foreach($declaration['declarationOperationType'] as $type)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 mr-2 mb-2">
                                            {{ str_replace('_', ' ', $type) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Transport Types') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationTransportType']) && is_array($declaration['declarationTransportType']))
                                    @foreach($declaration['declarationTransportType'] as $type)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 mr-2 mb-2">
                                            {{ str_replace('_', ' ', $type) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Vehicle Plate Numbers') }}</dt>
                            <dd class="mt-1">
                                @if(isset($declaration['declarationVehiclePlateNumber']) && is_array($declaration['declarationVehiclePlateNumber']))
                                    @foreach($declaration['declarationVehiclePlateNumber'] as $plate)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 mr-2 mb-2">
                                            {{ $plate }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">N/A</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Quick Actions') }}</h3>
                    <div class="space-y-3">
                        @if(($declaration['declarationStatus'] ?? '') === 'DRAFT')
                            <a href="{{ route('declarations.edit', $declaration['declarationId']) }}"
                               class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors text-center block">
                                {{ __('Edit Declaration') }}
                            </a>
                            <form method="POST" action="{{ route('declarations.submit', $declaration['declarationId']) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to submit this declaration?') }}')">
                                    {{ __('Submit Declaration') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('declarations.destroy', $declaration['declarationId']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to delete this declaration?') }}')">
                                    {{ __('Delete Declaration') }}
                                </button>
                            </form>
                        @elseif(($declaration['declarationStatus'] ?? '') === 'SUBMITTED')
                            <form method="POST" action="{{ route('declarations.withdraw', $declaration['declarationId']) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                        onclick="return confirm('{{ __('Are you sure you want to withdraw this declaration?') }}')">
                                    {{ __('Withdraw Declaration') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Status Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Status') }}</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @php
                                    $status = $declaration['declarationStatus'] ?? '';
                                    $statusColor = match($status) {
                                        'DRAFT' => 'bg-yellow-400',
                                        'SUBMITTED' => 'bg-green-400',
                                        'WITHDRAWN' => 'bg-red-400',
                                        'EXPIRED' => 'bg-gray-400',
                                        default => 'bg-gray-400'
                                    };
                                @endphp
                                <div class="h-3 w-3 rounded-full {{ $statusColor }}"></div>
                            </div>
                            <div class="ml-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @switch($status)
                                @case('DRAFT')
                                    {{ __('Declaration is in draft state and can be edited') }}
                                    @break
                                @case('SUBMITTED')
                                    {{ __('Declaration has been submitted') }}
                                    @break
                                @case('WITHDRAWN')
                                    {{ __('Declaration has been withdrawn') }}
                                    @break
                                @case('EXPIRED')
                                    {{ __('Declaration has expired') }}
                                    @break
                                @default
                                    {{ __('Unknown status') }}
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Declaration Modal -->
    <div id="printModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Print Declaration') }}</h3>
                    <button onclick="closePrintModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="printForm" class="space-y-4">
                    @csrf
                    <div>
                        <label for="print_language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Language') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="declarationLanguage" id="print_language" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">{{ __('Select Language') }}</option>
                            <option value="bg">{{ __('Bulgarian') }}</option>
                            <option value="cs">{{ __('Czech') }}</option>
                            <option value="da">{{ __('Danish') }}</option>
                            <option value="de">{{ __('German') }}</option>
                            <option value="et">{{ __('Estonian') }}</option>
                            <option value="el">{{ __('Greek') }}</option>
                            <option value="en">{{ __('English') }}</option>
                            <option value="es">{{ __('Spanish') }}</option>
                            <option value="fr">{{ __('French') }}</option>
                            <option value="fi">{{ __('Finnish') }}</option>
                            <option value="ga">{{ __('Irish') }}</option>
                            <option value="hr">{{ __('Croatian') }}</option>
                            <option value="hu">{{ __('Hungarian') }}</option>
                            <option value="it">{{ __('Italian') }}</option>
                            <option value="lv">{{ __('Latvian') }}</option>
                            <option value="lt">{{ __('Lithuanian') }}</option>
                            <option value="mt">{{ __('Maltese') }}</option>
                            <option value="nl">{{ __('Dutch') }}</option>
                            <option value="no">{{ __('Norwegian') }}</option>
                            <option value="pl">{{ __('Polish') }}</option>
                            <option value="pt">{{ __('Portuguese') }}</option>
                            <option value="ro">{{ __('Romanian') }}</option>
                            <option value="sk">{{ __('Slovak') }}</option>
                            <option value="sl">{{ __('Slovenian') }}</option>
                            <option value="sv">{{ __('Swedish') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <button type="button" onclick="closePrintModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            📄 {{ __('Generate PDF') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Declaration Modal -->
    <div id="emailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Email Declaration') }}</h3>
                    <button onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="emailForm" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Email Address') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="emailAddress" id="email_address" required placeholder="{{ __('driver@example.com') }}" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label for="email_language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Language') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="declarationLanguage" id="email_language" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">{{ __('Select Language') }}</option>
                            <option value="bg">{{ __('Bulgarian') }}</option>
                            <option value="cs">{{ __('Czech') }}</option>
                            <option value="da">{{ __('Danish') }}</option>
                            <option value="de">{{ __('German') }}</option>
                            <option value="et">{{ __('Estonian') }}</option>
                            <option value="el">{{ __('Greek') }}</option>
                            <option value="en">{{ __('English') }}</option>
                            <option value="es">{{ __('Spanish') }}</option>
                            <option value="fr">{{ __('French') }}</option>
                            <option value="fi">{{ __('Finnish') }}</option>
                            <option value="ga">{{ __('Irish') }}</option>
                            <option value="hr">{{ __('Croatian') }}</option>
                            <option value="hu">{{ __('Hungarian') }}</option>
                            <option value="it">{{ __('Italian') }}</option>
                            <option value="lv">{{ __('Latvian') }}</option>
                            <option value="lt">{{ __('Lithuanian') }}</option>
                            <option value="mt">{{ __('Maltese') }}</option>
                            <option value="nl">{{ __('Dutch') }}</option>
                            <option value="no">{{ __('Norwegian') }}</option>
                            <option value="pl">{{ __('Polish') }}</option>
                            <option value="pt">{{ __('Portuguese') }}</option>
                            <option value="ro">{{ __('Romanian') }}</option>
                            <option value="sk">{{ __('Slovak') }}</option>
                            <option value="sl">{{ __('Slovenian') }}</option>
                            <option value="sv">{{ __('Swedish') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeEmailModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            ✉️ {{ __('Send Email') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const declarationId = '{{ $declaration["declarationId"] }}';

        // Print Modal Functions
        function openPrintModal() {
            document.getElementById('printModal').classList.remove('hidden');
        }

        function closePrintModal() {
            document.getElementById('printModal').classList.add('hidden');
            document.getElementById('printForm').reset();
        }

        // Email Modal Functions
        function openEmailModal() {
            document.getElementById('emailModal').classList.remove('hidden');
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.add('hidden');
            document.getElementById('emailForm').reset();
        }

        // Print Form Submission
        document.getElementById('printForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                declarationLanguage: formData.get('declarationLanguage'),
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ {{ __("Generating...") }}';
            submitBtn.disabled = true;

            fetch(`{{ route('declarations.print', $declaration['declarationId']) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': data._token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    // Open the PDF in a new tab
                    window.open(data.url, '_blank');
                    closePrintModal();
                    showNotification('{{ __("PDF generated successfully!") }}', 'success');
                } else {
                    throw new Error(data.message || '{{ __("Failed to generate PDF") }}');
                }
            })
            .catch(error => {
                console.error('Print error:', error);
                showNotification(error.message || '{{ __("Failed to generate PDF") }}', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Email Form Submission
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                emailAddress: formData.get('emailAddress'),
                declarationLanguage: formData.get('declarationLanguage'),
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ {{ __("Sending...") }}';
            submitBtn.disabled = true;

            fetch(`{{ route('declarations.email', $declaration['declarationId']) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': data._token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeEmailModal();
                    showNotification('{{ __("Declaration sent successfully!") }}', 'success');
                } else {
                    throw new Error(data.message || '{{ __("Failed to send email") }}');
                }
            })
            .catch(error => {
                console.error('Email error:', error);
                showNotification(error.message || '{{ __("Failed to send email") }}', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                document.body.removeChild(notification);
            }, 5000);
        }

        // Close modals when clicking outside
        document.getElementById('printModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePrintModal();
            }
        });

        document.getElementById('emailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmailModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePrintModal();
                closeEmailModal();
            }
        });
    </script>
</x-layouts.app>