<div>
    @if($progressData && isset($progressData['total']) && $progressData['total'] > 0)
        @php
            $percentage = round(($progressData['current'] / $progressData['total']) * 100);
        @endphp
        <div wire:poll.2s="updateProgress" 
             class="flex items-center gap-x-3 px-4 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full shadow-sm">
            
            <!-- Animated Spinner Circle -->
            <div class="relative flex items-center justify-center">
                <svg class="w-5 h-5 animate-spin-slow text-primary-500" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center text-[8px] font-bold text-primary-600">
                    {{ $percentage }}%
                </div>
            </div>

            <!-- Labels and Mini Progress Bar -->
            <div class="flex flex-col min-w-[120px]">
                <div class="flex justify-between items-center mb-0.5">
                    <span class="text-[9px] font-semibold text-gray-500 uppercase tracking-wider">
                        {{ $progressData['status'] }}
                    </span>
                    <span class="text-[9px] font-bold text-primary-600">
                        {{ $progressData['current'] }} / {{ $progressData['total'] }}
                    </span>
                </div>
                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-600 rounded-full transition-all duration-700 ease-out" 
                         style="width: {{ $percentage }}%">
                    </div>
                </div>
            </div>
        </div>

        <style>
            .animate-spin-slow {
                animation: spin 3s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    @endif
</div>
