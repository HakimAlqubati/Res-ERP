<x-filament::page>
    {{ $this->form }}
    <div class="flex gap-4">
        <x-filament::button wire:click="approve" color="success">Approve</x-filament::button>
        {{ $this->rejectAction }}
    </div>
</x-filament::page>
