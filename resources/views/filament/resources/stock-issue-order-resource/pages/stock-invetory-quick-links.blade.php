<x-filament-panels::page>

    <style>
        .fi-tabs {
            display: none !important;
        }
    </style>
    <x-filament::card>
        {{-- First row --}}
        <x-filament::fieldset label="">
            <x-filament::grid style="--cols-lg: repeat(2, minmax(0, 1fr));" class="lg:grid-cols-[--cols-lg]">
                {{-- Orders --}}
                <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-issue-orders.index')" class="link" badge-color="danger" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before" tooltip="Go to Stock Issue Orders Page">


                    {{ __('Stock Issue Orders') }}
                    <x-slot name="badge">
                        {{ \App\Models\StockIssueOrder::count() }}
                    </x-slot>

                </x-filament::link>
                {{-- Stock Supply Orders --}}
                <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-supply-orders.index')" class="link" badge-color="success" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before" tooltip="Go to Stock Supply Orders Page">
                    {{ __('Stock Supply Orders') }}
                    <x-slot name="badge">
                        {{ \App\Models\StockSupplyOrder::count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Missing Inventory Products --}}
                <x-filament::link :href="route(
                    'filament.admin.inventory-management.resources.missing-inventory-products-reports.index',
                )" class="link" badge-color="warning" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before"
                    tooltip="Go to Missing Inventory Products Page">
                    {{ __('Unaudited Products') }}
                    <x-slot name="badge">
                        {{'Report'}}
                    </x-slot>
                </x-filament::link>

                {{-- Stocktakes --}}
                <x-filament::link :href="route('filament.admin.inventory-management.resources.stock-inventories.index')" class="link" badge-color="info" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before" tooltip="Go to Stocktake Page">
                    {{ __('Stocktakes') }}
                    <x-slot name="badge">
                        {{ \App\Models\StockInventory::count() }}
                    </x-slot>
                </x-filament::link>

            </x-filament::grid>
        </x-filament::fieldset>

    </x-filament::card>

</x-filament-panels::page>
