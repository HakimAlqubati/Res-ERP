<x-filament-panels::page.simple>
    <!-- Desktop Slogan and Logo (Fixed Left) -->
    <div class="hidden lg:block">
      
    </div>

    <!-- Mobile Header (Centered) -->
    <div class="erp-auth-container lg:hidden">
        <div class="erp-logo-wrapper">
            <img src="{{ asset('default.png') }}" alt="Workbench ERP Logo" class="erp-logo fi-logo fi-logo-light">
            <img src="{{ asset('default-wb.png') }}" alt="Workbench ERP Logo" class="erp-logo fi-logo fi-logo-dark">
        </div>
       
    </div>

    <style>
        body {
            /* Full screen gradient background using brand color */
            background: #0d7c66 !important;
            background: linear-gradient(135deg, #0d7c66 0%, #064e3b 100%) !important;
        }

        .dark body {
            background: #064e3b !important;
            background: linear-gradient(135deg, #022c22 0%, #064e3b 100%) !important;
        }

        .erp-auth-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .erp-logo-wrapper {
            margin-bottom: 1.5rem;
        }

        .erp-logo {
            height: 80px;
            width: auto;
            object-fit: contain;
        }

        .erp-subtitle {
            font-size: 0.875rem;
            color: #e2e8f0;
            text-align: center;
        }

        @media screen and (min-width: 1024px) {
            main {
                position: absolute; 
                right: 10%;
                top: 50%;
                transform: translateY(-50%);
            }

            main:before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(20, 184, 166, 0.4); /* Accent color for tilted box */
                backdrop-filter: blur(4px);
                border-radius: 12px;
                z-index: -9;
                box-shadow: -20px 30px 10px rgba(0, 0, 0, 0.1);
                -webkit-transform: rotate(7deg);
                -moz-transform: rotate(7deg);
                -o-transform: rotate(7deg);
                -ms-transform: rotate(7deg);
                transform: rotate(7deg);
            }

            .dark main:before {
                background: rgba(15, 118, 110, 0.5);
            }

            .erp-logo-fixed {
                position: fixed;
                left: 10%;
                top: 25%;
            }

            .erp-logo-fixed .erp-logo {
                height: 100px;
                filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
            }

            #slogan {
                position: fixed;
                left: 10%;
                top: 25%;
                margin-top: 130px;
                color: #f8fafc;
                font-family: system-ui, -apple-system, sans-serif;
                font-size: 2.5em;
                font-weight: bold;
                line-height: 1.3;
                text-shadow: rgba(0, 0, 0, 0.4) 2px 2px 6px;
            }
        }
    </style>

    {{ $this->content }}
</x-filament-panels::page.simple>
