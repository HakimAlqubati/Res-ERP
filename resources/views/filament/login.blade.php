<x-filament-panels::page.simple>
    <div class="flex min-h-screen items-center justify-center px-4 body_login">
        <div class="flex w-full max-w-4xl rounded-lg overflow-hidden relative">

            <!-- Left Section (Logo & Branding) -->
            <div class="hidden md:flex flex-col justify-center items-center flex-1 p-8 relative">
                <!-- Logo Positioned to the Left -->
                <img src="{{ asset('storage/logo/default.png') }}" alt="Logo" id="logo" class="absolute left-4 top-4 w-28">

                <h3 id="slogan">
                    Empowering Growth with <br> 
                    Enterprise-Level Solutions for All
                </h3>
            </div>

            <!-- Right Section (Login Form) -->
            <div class="flex-1 p-8">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                <x-filament-panels::form id="form" wire:submit="authenticate" class="space-y-4">
                    {{ $this->form }}

                    <x-filament-panels::form.actions 
                        :actions="$this->getCachedFormActions()" 
                        :full-width="$this->hasFullWidthFormActions()" 
                        class="w-full"
                    />
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>
        </div>
    </div>

    <style>
        .body_login {
            background: linear-gradient(0deg, rgba(34, 193, 195, 1) 0%, rgba(253, 187, 45, 1) 100%) !important;
        }

        .fi-simple-header {
            display: none !important;
        }

        #slogan {
            margin-top: 100px;
            color: bisque;
            font-family: cursive;
            font-size: 1.6em;
            font-weight: bold;
            text-shadow: #3f6212 2px 2px 5px;
            text-align: left;
        }

        #logo {
            max-width: 44%;
            position: absolute;
            left: 20px;  /* Moves the logo to the left inside the left section */
            top: 20px;
        }
    </style>
</x-filament-panels::page.simple>
