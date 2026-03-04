<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>Queue Management</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Execute artisan queue commands directly from the dashboard.
        </x-slot>

        <div class="flex flex-wrap gap-4">
            <x-filament::button wire:click="clearQueue" color="danger" icon="heroicon-m-trash">
                Clear Queue (Pending)
            </x-filament::button>

            <x-filament::button wire:click="restartQueue" color="warning" icon="heroicon-m-arrow-path">
                Restart Workers
            </x-filament::button>

            <x-filament::button wire:click="retryAllFailedJobs" color="success" icon="heroicon-m-arrow-path-rounded-square">
                Retry Failed Jobs
            </x-filament::button>

            <x-filament::button wire:click="flushFailedJobs" color="danger" icon="heroicon-m-x-circle" outlined>
                Flush Failed Jobs
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>