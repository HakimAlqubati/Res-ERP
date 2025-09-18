<div x-data="filamentFullscreen()" class="ms-2">
    {{-- "Fullscreen" button --}}
    <div x-show="!is" x-cloak>
        <x-filament::icon-button
            icon="heroicon-o-arrows-pointing-out"
            color="gray"
            size="sm"
            label="Fullscreen"
            tooltip="Fullscreen"
            x-on:click="toggle"
        />
    </div>

    {{-- "Exit Fullscreen" button --}}
    <div x-show="is" x-cloak>
        <x-filament::icon-button
            icon="heroicon-o-arrows-pointing-in"
            color="gray"
            size="sm"
            label="Exit Fullscreen"
            tooltip="Exit Fullscreen"
            x-on:click="toggle"
        />
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('filamentFullscreen', () => ({
                is: !!document.fullscreenElement,

                init() {
                    // Update state on entering/exiting fullscreen
                    document.addEventListener('fullscreenchange', () => {
                        this.is = !!document.fullscreenElement;
                    });
                },

                async toggle() {
                    try {
                        if (!document.fullscreenElement) {
                            const el = document.documentElement;
                            (el.requestFullscreen
                                || el.webkitRequestFullscreen
                                || el.msRequestFullscreen).call(el);
                        } else {
                            const exit = document.exitFullscreen
                                || document.webkitExitFullscreen
                                || document.msExitFullscreen;
                            if (exit) exit.call(document);
                        }
                    } catch (e) {
                        console.error('Fullscreen toggle failed', e);
                    }
                },
            }))
        })
    </script>
@endonce
