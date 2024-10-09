<div x-data="{ inputValue: @entangle('rfid') }" class="keypad-container">
    <!-- Display the input value -->
    <input type="text" x-model="inputValue" class="keypad-display"
           placeholder="رقم RFID"
           style="color: black !important" {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}" readonly />

    <!-- Keypad buttons -->
    <div class="keypad-grid" style="direction: ltr;">
        @foreach (range(1, 9) as $number)
            <button type="button" class="keypad-button" wire:click="appendToDisplay('{{ $number }}')">{{ $number }}</button>
        @endforeach
        <button type="button" class="keypad-button-clear" style="width: calc(2 * 50%);border-radius:5px;"
                wire:click="clearDisplay">C</button>
        <button type="button" class="keypad-button" wire:click="appendToDisplay('0')">0</button>
    </div>
</div>
