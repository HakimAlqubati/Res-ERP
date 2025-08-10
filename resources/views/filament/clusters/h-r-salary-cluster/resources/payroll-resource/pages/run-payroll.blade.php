<x-filament-panels::page>
    <x-filament::section heading="Scope">
        {{ $this->form }}
        <x-filament::button wire:click="run" class="mt-4">Run simulation</x-filament::button>
    </x-filament::section>

    {{-- Here you can render live results table (polling) after the job finishes --}}
</x-filament-panels::page>
