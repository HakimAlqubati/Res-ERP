<x-filament::card class="flex justify-center items-center h-full">
    <audio id="notification-tone" src="{{ asset('audio/notify.mp3') }}" preload="auto"></audio>
    {{-- {{ dd(request()->session()->all()) }} --}}
    <div class="flex justify-center items-center h-full">
        <img src="{{ asset('storage/logo/default.png') }}" style="height: 9.5rem;" alt="">
    </div>

    <div style="padding-left:150px;" class="flex items-center justify-center h-full space-x-4">

        {{-- Fingerprint Button Section --}}
        <div class="flex items-center">
            <form wire:submit.prevent="submit">
                <x-filament::icon-button id="play-tone-btn" class="btn_fingerprint" icon="heroicon-o-finger-print"
                    tag="button" label="Submit" type="submit" size="xl" style="height: 9.5rem; width: 9.5rem;"
                    color="success" />
        </div>

        {{-- Time Display --}}
        <div id="time-display" class="flex items-center justify-center text-lg font-semibold text-gray-600">
            <!-- The time will be dynamically updated here -->
        </div>

        {{-- Form Section --}}
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>
        </form>


    </div>
</x-filament::card>


<script>
    // Function to update the time
    function updateTime() {
        const timeDisplay = document.getElementById('time-display');
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        timeDisplay.textContent = `${hours}:${minutes}:${seconds}`;
    }

    // Update the time every second
    setInterval(updateTime, 1000);

    // Initialize the time immediately when the page loads
    updateTime();
</script>