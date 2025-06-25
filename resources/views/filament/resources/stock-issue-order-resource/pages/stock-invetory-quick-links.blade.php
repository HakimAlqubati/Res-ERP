<x-filament-panels::page>

    {{-- CSS Styles for the new design --}}
    <style>
        /* Hide default tabs if they exist */
        .fi-tabs {
            display: none !important;
        }

        /* Styling for the main link container */
        .link {
            transition: all 0.2s ease-in-out;
            border-radius: 1rem;
            background-color: #0d7c66;
            /* Green background */
            color: #ffffff;
            /* White text and icon */
            display: flex;
            flex-direction: column;
            /* Stack icon and text vertically */
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
            border: 1px solid transparent;
            /* Start with a transparent border */
        }

        /* Hover effect for the link */
        .link:hover {
            background-color: #ffffff;
            /* White background on hover */
            color: #0d7c66;
            /* Green text on hover */
            transform: translateY(-4px);
            font-weight: 700;
            /* Bold text */
            border: 1px solid #0d7c66;
            /* Green border on hover */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        /* Styling for the SVG icon inside the link */
        .link svg {
            width: 5.5rem;
            /* Icon width */
            height: 5.5rem;
            /* Icon height */
            color: #ffffff;
            /* White icon color */
            margin-bottom: 0.5rem;
            /* Space between icon and text */
            transition: color 0.2s ease-in-out;
        }

        /* Change icon color on hover */
        .link:hover svg {
            color: #0d7c66;
            /* Green icon on hover */
        }

        /* Styling for the text label */
        .link_span {
            color: #fff !important;
            /* White text color */
            font-size: 1.125rem;
            /* Text size */
            font-weight: 600;
            transition: color 0.2s ease-in-out;
        }

        .link:hover .link_span {
            color: #0d7c66 !important;
            /* Green text on hover */
        }

        /* Styling for the badge count */
        .badge {
            color: #ffffff;
            /* White badge text color */
            background-color: rgba(0, 0, 0, 0.2);
            /* Slightly darker background for contrast */
            padding: 0.1rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
        }

        .link:hover .badge {
            color: #0d7c66;
            /* Green badge text on hover */
            background-color: #e0f2f1;
            /* Light green background on hover */
        }

        /* Container to hold the text and badge together */
        .link-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            /* Space between text and badge */
        }

        /* === Dark Mode Adjustments === */
        .dark .link {
            box-shadow: none !important;
            border: 1px solid #4a5568 !important;
            /* Darker border for dark mode */
            background-color: #2d3748;
            /* Darker background for dark mode */
        }

        .dark .link:hover {
            background-color: #1a202c !important;
            /* Even darker background on hover */
            color: #38a169;
            /* Lighter green text for dark mode hover */
            border-color: #38a169;
        }

        .dark .link svg {
            color: #ffffff !important;
        }

        .dark .link:hover svg {
            color: #38a169 !important;
            /* Lighter green icon for dark mode hover */
        }

        .dark .link_span {
            color: #ffffff !important;
        }

        .dark .link:hover .link_span {
            color: #38a169 !important;
            /* Lighter green text for dark mode hover */
        }

        .dark .badge {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dark .link:hover .badge {
            color: #38a169;
            background-color: #2d3748;
        }
    </style>


    <x-filament::fieldset>
        {{-- You can change the grid columns here (e.g., repeat(4, ...)) for a different layout --}}
        <x-filament::grid style="--cols-lg: repeat(4, minmax(0, 1fr)); gap: 1.5rem;" class="lg:grid-cols-[--cols-lg]">

            {{-- Stock Issue Orders --}}
            <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-issue-orders.index')" icon="heroicon-o-arrow-up-tray" class="link"
                tooltip="Go to Stock Issue Orders Page">

                <div class="link-text">
                    <span class="link_span">{{ __('Stock Issue Orders') }}</span>
                    <span class="badge">{{ \App\Models\StockIssueOrder::count() }}</span>
                </div>
            </x-filament::link>

            {{-- Stock Supply Orders --}}
            <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-supply-orders.index')" icon="heroicon-o-arrow-down-tray" class="link"
                tooltip="Go to Stock Supply Orders Page">

                <div class="link-text">
                    <span class="link_span">{{ __('Stock Supply Orders') }}</span>
                    <span class="badge">{{ \App\Models\StockSupplyOrder::count() }}</span>
                </div>
            </x-filament::link>

            {{-- Unaudited Products --}}
            <x-filament::link :href="route('filament.admin.inventory-management.resources.missing-inventory-products-reports.index')" icon="heroicon-m-exclamation-triangle" class="link"
                tooltip="Go to Missing Inventory Products Page">

                <div class="link-text">
                    <span class="link_span">{{ __('Unaudited Products') }}</span>
                    <span class="badge">{{ __('Report') }}</span>
                </div>
            </x-filament::link>

            {{-- Stocktakes --}}
            <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-inventories.index')" icon="heroicon-m-clipboard-document-check" class="link"
                tooltip="Go to Stocktake Page">

                <div class="link-text">
                    <span class="link_span">{{ __('Stocktakes') }}</span>
                    <span class="badge">{{ \App\Models\StockInventory::count() }}</span>
                </div>
            </x-filament::link>

        </x-filament::grid>
    </x-filament::fieldset>


</x-filament-panels::page>
