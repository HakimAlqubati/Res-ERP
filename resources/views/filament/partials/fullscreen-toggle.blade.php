<div x-data="filamentFullscreen()" class="ms-2">
    {{-- "Fullscreen" button --}}
    <div x-show="!is" x-cloak>
        <x-filament::icon-button icon="heroicon-o-arrows-pointing-out" color="primary" size="sm" label="Fullscreen"
            tooltip="Fullscreen" x-on:click="toggle" class="pulse" />
    </div>

    {{-- "Exit Fullscreen" button --}}
    <div x-show="is" x-cloak>
        <x-filament::icon-button icon="heroicon-o-arrows-pointing-in" color="primary" size="sm"
            label="Exit Fullscreen" tooltip="Exit Fullscreen" x-on:click="toggle" class="pulse" />
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
                            (el.requestFullscreen ||
                                el.webkitRequestFullscreen ||
                                el.msRequestFullscreen)
                            .call(el);
                        } else {
                            const exit = document.exitFullscreen ||
                                document.webkitExitFullscreen ||
                                document.msExitFullscreen;
                            if (exit) exit.call(document);
                        }
                    } catch (e) {
                        console.error('Fullscreen toggle failed', e);
                    }
                },
            }))
        })
    </script>

    <style>
        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.9;
            }
        }

        .pulse {
            animation: pulse 4.8s infinite ease-in-out;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            40% {
                transform: scale(1.4);
                opacity: 0.85;
            }

            60% {
                transform: scale(1.5);
                opacity: 0.8;
            }
        }

 
    </style>
@endonce
