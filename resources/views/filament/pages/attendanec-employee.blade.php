<x-filament::card class="flex justify-center items-center h-full">
    <audio id="notification-tone" src="{{ asset('audio/notify.mp3') }}" preload="auto"></audio>

    <div class="flex justify-center items-center h-full">
        <img src="{{ asset('storage/logo/default.png') }}" style="height: 9.5rem;" alt="">
    </div>

    <div style="padding-left:150px;" class="flex items-center justify-center h-full space-x-4">

         {{-- Fingerprint Button Section --}}
         <div class="flex items-center">
             <form wire:submit.prevent="submit">
            <x-filament::icon-button 
                id="play-tone-btn" 
                class="btn_fingerprint" 
                icon="heroicon-o-finger-print"
                tag="button" 
                label="Submit" 
                type="submit" 
                size="xl" 
                style="height: 9.5rem; width: 9.5rem;"
                color="success" 
            />
        </div>

        {{-- Form Section --}}
            <x-filament::section>
                {{ $this->form }}
            </x-filament::section>
        </form>

       
    </div>
</x-filament::card>
