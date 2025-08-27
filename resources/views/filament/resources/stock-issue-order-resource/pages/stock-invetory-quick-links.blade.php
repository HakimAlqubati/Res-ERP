<x-filament::widget>
    <div wire:ignore>
        <style>
            .quick-link {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 168px;
                height: 168px;
                gap: .5rem;
                text-align: center;
                border-radius: 1rem;
                background-color: #0d7c66;
                color: #fff;
                transition: all .2s ease;
                border: 1px solid transparent;
            }
            .quick-link:hover {
                transform: translateY(-4px);
                border-color: #0d7c66;
                color: #0d7c66;
                background-color: #fff;
            }
            .quick-link svg {
                width: 4.5rem;
                height: 4.5rem;
                color: #ffffff;
            }
            .quick-link:hover svg { color: #0d7c66; }
            .quick-link .label { font-size: 1.05rem; font-weight: 700; }
            .quick-link .badge { font-weight: 700; font-size: 0.9rem; }

            .dark .quick-link {
                background-color: #0d7c66;
                color: #fff;
                border-color: rgba(255, 255, 255, .25);
            }
            .dark .quick-link:hover {
                background-color: rgba(255, 255, 255, .08);
                color: #0d7c66;
                border-color: #0d7c66;
            }
            .dark .quick-link svg { color: #fff; }

            .tile-grid {
                display: grid;
                gap: 1rem;
                justify-content: center; /* تبقي البلاطات محاذية في المنتصف */
            }

            /* يمكنك استخدام أحد الخيارين:
               1) مقاسات ثابتة كما في مثالك (grid-3 / grid-6)
               2) شبكة مرنة auto-fit لملء الصفوف تلقائياً (مفعّلة أدناه) */
            .auto-grid {
                grid-template-columns: repeat(auto-fit, minmax(168px, 1fr));
            }

            @media (min-width: 1024px) {
                .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .grid-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
            }
        </style>

        <x-filament::card>
            {{-- Inventory Section --}}
            <x-filament::fieldset label="{{ __('Inventory Management') }}">
                {{-- اختر واحدة من الطبقتين أدناه:
                     - استخدام auto-grid (مرن) → أزل تعليق السطر الأول
                     - استخدام grid-6 (ثابت كما في مثالك) → أزل تعليق السطر الثاني --}}
                <div class="tile-grid auto-grid">
                {{-- <div class="tile-grid grid-6"> --}}

                    <a href="{{ route('filament.admin.inventory-management.resources.stock-issue-orders.index') }}" class="quick-link" title="Stock Issue Orders">
                        <x-heroicon-o-arrow-up-tray />
                        <div class="label">{{ __('Stock Issue Orders') }}</div>
                        <div class="badge">{{ \App\Models\StockIssueOrder::count() }}</div>
                    </a>

                    <a href="{{ route('filament.admin.inventory-management.resources.stock-supply-orders.index') }}" class="quick-link" title="Stock Supply Orders">
                        <x-heroicon-o-arrow-down-tray />
                        <div class="label">{{ __('Stock Supply Orders') }}</div>
                        <div class="badge">{{ \App\Models\StockSupplyOrder::count() }}</div>
                    </a>

                    <a href="{{ route('filament.admin.inventory-management.resources.missing-inventory-products-reports.index') }}" class="quick-link" title="Unaudited Products">
                        <x-heroicon-m-exclamation-triangle />
                        <div class="label">{{ __('Unaudited Products') }}</div>
                        <div class="badge">{{ __('Report') }}</div>
                    </a>

                    <a href="{{ route('filament.admin.inventory-management.resources.stock-inventories.index') }}" class="quick-link" title="Stocktakes">
                        <x-heroicon-m-clipboard-document-check />
                        <div class="label">{{ __('Stocktakes') }}</div>
                        <div class="badge">{{ \App\Models\StockInventory::count() }}</div>
                    </a>

                    <a href="{{ route('filament.admin.inventory-management.resources.stock-transfer-orders.index') }}" class="quick-link" title="Stock Transfer Orders">
                        <x-heroicon-o-arrows-right-left />
                        <div class="label">{{ __('Stock Transfer Orders') }}</div>
                        <div class="badge">{{ \App\Models\StockTransferOrder::count() }}</div>
                    </a>

                </div>
            </x-filament::fieldset>
        </x-filament::card>
    </div>
</x-filament::widget>
