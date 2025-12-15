<div
    wire:loading.flex
    wire:target="data.category_id"
    class="fixed inset-0 z-50 items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
    style="backdrop-filter: blur(2px);">
    <div class="flex flex-col items-center gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-2xl">
        {{-- Spinner --}}
        <x-filament::loading-indicator class="h-8 w-8" />
        
        {{-- Loading Text --}}
        <div class="text-center">
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                {{ __('Products Loading...') }}
            </p>
        </div>
    </div>
</div>