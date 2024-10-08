<x-filament::card class="flex justify-center items-center h-full">
    <audio id="notification-tone" src="{{ asset('audio/notify.mp3') }}" preload="auto"></audio>
    {{-- <h1 id="play-tone-btn">CLICK</h1> --}}
    <div class="flex justify-center items-center h-full">
        <img src="{{ asset('storage/logo/default.png') }}" style="height: 9.5rem;" alt="">
    </div>
    {{-- <script>
        playTone();
    </script> --}}
    <form wire:submit.prevent="submit">
        <div class="flex justify-center items-center h-full">
            <x-filament::section>
                {{ $this->form }}

                <div class="flex justify-center items-center h-full" style="padding: 5px;">
                    <x-filament::icon-button id="play-tone-btn" class="btn_fingerprint" icon="heroicon-o-finger-print" tag="button" label="Submit" type="submit"
                        size="xl" style="height: 9.5rem; width: 100%;" color="success" />
                </div>
            </x-filament::section>
        </div>


    </form>
</x-filament::card>
