<x-filament::page>
    {{ $this->form }}
    <div class="flex gap-4">
        <x-filament::button wire:click="approve" color="success">Approve</x-filament::button>
        <x-filament::button wire:click="reject" color="danger">Reject</x-filament::button>
    </div>
</x-filament::page>
