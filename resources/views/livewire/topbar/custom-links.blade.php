<div class="flex items-center gap-x-6">



    <div class="relative" x-data="{ open: false }">
        {{-- الزر --}}
        <button @click="open = !open"
            class="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-primary-600 transition">
            <span class="text-[15px]">📦 Inventory</span>
            <svg class="w-4 h-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- القائمة --}}
        <div x-show="open" @click.outside="open = false" x-transition
            class="absolute left-0 mt-2 w-80 rounded-xl shadow-xl bg-white border border-gray-200 z-50 p-4 space-y-3">

            {{-- كل عنصر منسق مثل الـ x-filament::link --}}
            <a href="{{ route('filament.admin.inventory-management.resources.stock-issue-orders.index') }}"
                class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-gray-50 transition border border-gray-100">
                <div class="flex items-center gap-3">
                    <x-filament::icon name="heroicon-o-arrow-up-tray" size="md" />
                    <span class="text-sm font-medium text-gray-800">Stock Issue Orders</span>
                </div>
                <span
                    class="inline-flex items-center justify-center px-2 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                    {{ \App\Models\StockIssueOrder::count() }}
                </span>
            </a>

            <a href="{{ route('filament.admin.inventory-management.resources.stock-supply-orders.index') }}"
                class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-gray-50 transition border border-gray-100">
                <div class="flex items-center gap-3">
                    <x-filament::icon name="heroicon-o-arrow-down-tray" size="md" />
                    <span class="text-sm font-medium text-gray-800">Stock Supply Orders</span>
                </div>
                <span
                    class="inline-flex items-center justify-center px-2 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                    {{ \App\Models\StockSupplyOrder::count() }}
                </span>
            </a>

            <a href="{{ route('filament.admin.inventory-management.resources.missing-inventory-products-reports.index') }}"
                class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-gray-50 transition border border-gray-100">
                <div class="flex items-center gap-3">
                    <x-filament::icon name="heroicon-m-exclamation-triangle" size="md" />
                    <span class="text-sm font-medium text-gray-800">Unaudited Products</span>
                </div>
                <span
                    class="inline-flex items-center justify-center px-2 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                    Report
                </span>
            </a>

            <a href="{{ route('filament.admin.inventory-management.resources.stock-inventories.index') }}"
                class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-gray-50 transition border border-gray-100">
                <div class="flex items-center gap-3">
                    <x-filament::icon name="heroicon-m-clipboard-document-check" size="md" />
                    <span class="text-sm font-medium text-gray-800">Stocktakes</span>
                </div>
                <span
                    class="inline-flex items-center justify-center px-2 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                    {{ \App\Models\StockInventory::count() }}
                </span>
            </a>

        </div>
    </div>


    <div class="relative" x-data="{ open: false }">
        {{-- الزر --}}
        <button @click="open = !open"
            class="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-primary-600 transition">
            <span class="text-[15px]">📦 Inventory</span>
            <svg class="w-4 h-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
 
    </div>




    {{-- عرض الوقت الحالي --}}
    <div class="ml-auto text-sm text-gray-600 font-medium time flex items-center gap-x-2">
        📅 <span id="current-date">--/--/----</span>
        - <span id="current-day">---</span>
        🕒 <span id="current-time">--:--:--</span>
    </div>
</div>

<style>
    .time {
        border: 1px solid #d1d5db;
        padding: 6px 10px;
        border-radius: 6px;
        background-color: #f9fafb;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>

@push('scripts')
    <script>
        function updateClock() {
            const now = new Date();

            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            const dateString = now.toLocaleDateString('en-GB', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });

            const dayString = now.toLocaleDateString('en-US', {
                weekday: 'long'
            });

            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
            document.getElementById('current-day').textContent = dayString;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
@endpush
