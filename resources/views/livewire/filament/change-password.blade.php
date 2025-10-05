<div>

    {{-- زر فتح المودال --}}
    <x-filament::dropdown.list.item icon="heroicon-m-key" tag="button"
        x-on:click="$dispatch('open-modal', { id: 'change-password-modal' })">
        {{ __('Change password') }}
    </x-filament::dropdown.list.item>

    {{-- المودال --}}
    <x-filament::modal id="change-password-modal" width="md">
        <x-slot name="heading">
            {{ __('Change password') }}
        </x-slot>

        {{-- نستدعي الفورم من الكلاس مباشرة --}}
        <form wire:submit.prevent="change" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-4 w-full">
                {{ __('Save') }}
            </x-filament::button>
        </form>
    </x-filament::modal>

</div>
