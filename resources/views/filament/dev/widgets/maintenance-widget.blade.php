<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Maintenance
        </x-slot>

        <div class="flex flex-wrap gap-4">
            <x-filament::button wire:click="optimize" color="warning" icon="heroicon-m-sparkles">
                Optimize:Clear
            </x-filament::button>

            <x-filament::button wire:click="clearView" color="gray" icon="heroicon-m-trash">
                Clear Views
            </x-filament::button>

            <x-filament::button wire:click="clearConfig" color="gray" icon="heroicon-m-cog">
                Clear Config
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>