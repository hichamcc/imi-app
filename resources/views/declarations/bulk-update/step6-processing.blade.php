<x-layouts.app :title="__('Bulk Update - Processing')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Bulk Update Declarations') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Step 6: Processing your bulk update') }}</p>
            </div>
            <a href="{{ route('declarations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                {{ __('View Declarations') }}
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <!-- Progress indicator -->
                <div class="mb-6">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ __('Progress') }}</span>
                        <span>6/6</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full transition-all duration-500" id="progress-bar" style="width: 100%"></div>
                    </div>
                </div>

                <!-- Live Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8" id="stats-grid">
                    <div class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Total') }}</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="total-count">{{ count($selectedDeclarationIds) }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-900 dark:text-green-100">{{ __('Successful') }}</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="success-count">0</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-red-900 dark:text-red-100">{{ __('Failed') }}</div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="error-count">0</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">{{ __('Processing') }}</div>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" id="processing-count">0</div>
                    </div>
                </div>

                <!-- Processing Status -->
                <div class="mb-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600" id="spinner"></div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="status-text">
                            {{ __('Starting bulk update...') }}
                        </h3>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400" id="current-item">
                        {{ __('Preparing to process') }} {{ count($selectedDeclarationIds) }} {{ __('declarations') }}
                    </div>
                </div>

                <!-- Action Summary -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg mb-6">
                    <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-2">
                        {{ __('Action:') }} {{ strtoupper($action) }} {{ __('plates') }}
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($selectedPlates as $plate)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $action === 'add' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                {{ $plate }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Results Log -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('Processing Log') }}
                        </h3>
                    </div>
                    <div class="max-h-96 overflow-y-auto p-4" id="results-log">
                        <!-- Results will be populated here via JavaScript -->
                    </div>
                </div>

                <!-- Final Actions -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-600" id="final-actions" style="display: none;">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white" id="completion-title">
                                {{ __('Bulk Update Complete') }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400" id="completion-summary">
                                {{ __('All declarations have been processed.') }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('declarations.bulk-update.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('Start New Bulk Update') }}
                            </a>
                            <a href="{{ route('declarations.index') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                {{ __('View All Declarations') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedDeclarations = @json($selectedDeclarationIds);
            const selectedPlates = @json($selectedPlates);
            const action = @json($action);

            let successCount = 0;
            let errorCount = 0;
            let processedCount = 0;
            const totalCount = selectedDeclarations.length;

            const elements = {
                successCount: document.getElementById('success-count'),
                errorCount: document.getElementById('error-count'),
                processingCount: document.getElementById('processing-count'),
                statusText: document.getElementById('status-text'),
                currentItem: document.getElementById('current-item'),
                resultsLog: document.getElementById('results-log'),
                finalActions: document.getElementById('final-actions'),
                spinner: document.getElementById('spinner'),
                completionTitle: document.getElementById('completion-title'),
                completionSummary: document.getElementById('completion-summary')
            };

            function updateStats() {
                elements.successCount.textContent = successCount;
                elements.errorCount.textContent = errorCount;
                elements.processingCount.textContent = Math.max(0, totalCount - processedCount);
            }

            function addLogEntry(type, message, declarationId = null) {
                const entry = document.createElement('div');
                entry.className = `flex items-start space-x-3 p-3 rounded-lg mb-2 ${type === 'success' ? 'bg-green-50 dark:bg-green-900/20' : type === 'error' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20'}`;

                const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
                const iconColor = type === 'success' ? 'text-green-600' : type === 'error' ? 'text-red-600' : 'text-blue-600';

                entry.innerHTML = `
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full text-sm font-medium ${iconColor}">
                            ${icon}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${message}
                        </p>
                        ${declarationId ? `<p class="text-xs text-gray-500 dark:text-gray-400">${declarationId}</p>` : ''}
                    </div>
                    <div class="flex-shrink-0 text-xs text-gray-400">
                        ${new Date().toLocaleTimeString()}
                    </div>
                `;

                elements.resultsLog.appendChild(entry);
                elements.resultsLog.scrollTop = elements.resultsLog.scrollHeight;
            }

            async function processDeclaration(declarationId, index) {
                try {
                    elements.statusText.textContent = `Processing declaration ${index + 1} of ${totalCount}`;
                    elements.currentItem.textContent = `Declaration: ${declarationId}`;

                    addLogEntry('info', `Processing declaration ${index + 1}/${totalCount}`, declarationId);

                    const response = await fetch('{{ route("declarations.bulk-update.process-declaration") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            declaration_id: declarationId,
                            selected_plates: selectedPlates,
                            action: action
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        successCount++;
                        addLogEntry('success',
                            `${action.toUpperCase()} successful: ${result.old_plates?.length || 0} → ${result.new_plates?.length || 0} plates`,
                            declarationId
                        );
                    } else {
                        errorCount++;
                        addLogEntry('error', `Failed: ${result.error || 'Unknown error'}`, declarationId);
                    }

                } catch (error) {
                    errorCount++;
                    addLogEntry('error', `Network error: ${error.message}`, declarationId);
                }

                processedCount++;
                updateStats();
            }

            async function processAllDeclarations() {
                addLogEntry('info', `Starting bulk ${action} operation on ${totalCount} declarations`);

                for (let i = 0; i < selectedDeclarations.length; i++) {
                    await processDeclaration(selectedDeclarations[i], i);

                    // Small delay to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 100));
                }

                // Processing complete
                elements.spinner.style.display = 'none';
                elements.statusText.textContent = 'Bulk update completed';
                elements.currentItem.textContent = `Processed ${totalCount} declarations`;

                const finalMessage = `Completed: ${successCount} successful, ${errorCount} failed`;
                addLogEntry('info', finalMessage);

                // Update completion summary
                elements.completionTitle.textContent = successCount === totalCount ? 'Bulk Update Successful!' : 'Bulk Update Completed with Errors';
                elements.completionSummary.textContent = finalMessage;

                // Show final actions
                elements.finalActions.style.display = 'block';
            }

            // Start processing
            setTimeout(() => {
                processAllDeclarations();
            }, 1000);
        });
    </script>
</x-layouts.app>