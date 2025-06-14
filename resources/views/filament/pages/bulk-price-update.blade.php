<x-filament-panels::page>
    <form wire:submit.prevent="updatePrices">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" form="updatePrices">
                Update
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
