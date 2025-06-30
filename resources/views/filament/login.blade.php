<x-filament-panels::page.simple>
    <div class="min-h-screen flex items-center justify-center px-4]">
        <div class="w-full max-w-7xl flex flex-col md:flex-row items-center justify-center gap-12">

            {{-- Left: Branding --}}
            <div
                class="text-white px-4 md:px-12 w-full md:w-1/2 flex flex-col items-center justify-center text-center space-y-4">
                <div class="logo-wrapper">
                    <img src="{{ asset('storage/logo/default-wb.png') }}" alt="NLT Logo" class="logo w-44 h-auto mb-2">
                </div>
                <div class="slugan">
                    <p class="text-sm md:text-base leading-relaxed text-teal-100 tracking-wide">
                        {{-- <span>
                            Empowering Growth with
                        </span> --}}
                        <br>
                        <span class="text-teal-300 font-medium tracking-wider">Enterprise Solutions. Simplified</span>
                    </p>
                </div>
            </div>

            {{-- Right: Login Form --}}
            <div class="login_container bg-white rounded-2xl shadow-2xl p-6 md:p-10 w-full max-w-sm">
                <h2 class="text-2xl font-semibold text-center text-gray-800 mb-6">Login</h2>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    <div class="flex items-center justify-between mt-4 mb-2 text-sm text-gray-600">
                        {{-- <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.defer="data.remember" class="rounded border-gray-300">
                            Remember Me
                        </label> --}}

                    </div>

                    <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>
        </div>
    </div>

    <style>
        .fi-simple-header {
            display: none !important;
        }

        .fi-form-actions {
            width: 100%;
        }

        input[type="email"],
        input[type="password"],
        .fi-input-wrp {
            border-radius: 9999px !important;
            /* padding-left: 1rem !important;
            padding-right: 1rem !important; */
            height: 2.75rem;
            font-size: 0.95rem;
        }

        button[type="submit"],
        .fi-button {
            background-color: #00a67e !important;
            color: #fff !important;
            border-radius: 10px !important;
            font-weight: 600;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .min-h-screen {
            background: #0A6150 !important;
        }

        .login_container {
            border-radius: 19px;
            max-width: 30rem;
        }

        .login_container h2 {
            font-size: 2.9rem;
            line-height: 4rem;
            color: #07473B;

        }

        /* Style for placeholder text */
        input::placeholder,
        textarea::placeholder {
            color: #00163c;
            /* Tailwind's text-gray-400 */
            font-size: 0.9rem;
            opacity: 1;
            /* Override browser default if faded */
        }

        /* Optional: dark background input field contrast */
        input:focus::placeholder {
            color: #000;
            /* Slightly brighter gray on focus */
        }

        input::placeholder {
            content: "Enter your email";
            /* هذا لا يعمل */
        }

        .slugan p,
        .slugan span {
            letter-spacing: 0.19199rem;
        }

        .slugan p {
            text-align: center;
            color: #15d3ae;
            font-size: 1.08rem;
        }

        .slugan {
            width: 100%;
            padding-right: 25%;
            padding-left: 25%;
        }

        .logo {
            height: 260px;
        }

        .fi-input-wrp-suffix {
            border: none;
        }

        body::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        body {
            -ms-overflow-style: none;
            /* IE & Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .logo-wrapper {
            /* background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(6px);
            padding: 1.5rem;
            border-radius: 2rem; */
            /* box-shadow: 0 12px 20px rgba(0, 0, 0, 0.3); */
            transition: all 0.3s ease;
        }


        @keyframes floatLogo {

            0%,
            100% {
                transform: translateY(0) rotateX(2deg) rotateY(-2deg);
            }

            50% {
                transform: translateY(-8px) rotateX(0deg) rotateY(0deg);
            }
        }

        .logo {
            animation: floatLogo 4s ease-in-out infinite;
        }

        .logo:hover {
            transform: perspective(800px) rotateX(0deg) rotateY(0deg) scale(1.03);
            filter: drop-shadow(0 20px 25px rgba(0, 0, 0, 0.5));
        }
    </style>
</x-filament-panels::page.simple>
