<x-filament-panels::page.simple>
    <style>
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
            color: #64748b;
            text-align: center;
        }

        .dark .erp-subtitle {
            color: #94a3b8;
        }

        @media (min-width: 640px) {
            .erp-logo {
                height: 90px;
            }
            .erp-subtitle {
                font-size: 1rem;
            }
        }
    </style>

    <div class="erp-auth-container">
        <div class="erp-logo-wrapper">
            <!-- Light Mode Logo -->
            <img src="{{ asset('default.png') }}" alt="Workbench ERP Logo" class="erp-logo fi-logo fi-logo-light">
            <!-- Dark Mode Logo -->
            <img src="{{ asset('default-wb.png') }}" alt="Workbench ERP Logo" class="erp-logo fi-logo fi-logo-dark">
        </div>
        
        <p class="erp-subtitle">
            Enterprise Resource Planning System
        </p>
    </div>

    {{ $this->content }}
</x-filament-panels::page.simple>
