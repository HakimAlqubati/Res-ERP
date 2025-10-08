<x-filament-panels::page>
    {{-- This is the correct Filament form component --}}
    <form wire:submit="submitConversion">

        {{-- This renders all the form fields defined in getFormSchema() --}}
        {{ $this->form }}

        {{-- This renders the form actions (buttons) defined in getFormActions() --}}
        {{-- It's crucial for Filament to properly manage the submit button --}}

        <button  type="submit">
            Submit
        </button>

        {{-- <button :actions="$this->getFormActions()"> Submit Conversion</button> --}}
    </form>
</x-filament-panels::page>
