<x-filament::page>
    {{-- Category Filter --}}
    <form method="GET" class="mb-6">
        <label class="block mb-2 font-bold">Select Category:</label>
        <select name="category_id" onchange="this.form.submit()" class="border p-2 rounded w-1/3">
            <option value="">-- Select Category --</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($selectedCategory == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($selectedCategory && count($products))
        <div x-data="{ tab: '{{ $products->first()->id ?? '' }}' }">
            {{-- Tabs --}}
            <div class="flex overflow-x-auto whitespace-nowrap border-b border-gray-300 mb-4 gap-2 rtl:space-x-reverse">
                @foreach ($products as $product)
                    <button @click="tab = '{{ $product->id }}'"
                        :class="{
                            'border-b-2 border-primary-600 font-bold text-primary-600': tab === '{{ $product->id }}',
                            'text-gray-500': tab !== '{{ $product->id }}'
                        }"
                        class="px-4 py-2 whitespace-nowrap hover:text-primary-500 transition">
                        {{ $product->name }}
                    </button>
                @endforeach
            </div>

            {{-- Tabs Content --}}
            @foreach ($products as $product)
                <div x-show="tab === '{{ $product->id }}'" class="mt-4" x-cloak>
                    <h3 class="text-lg font-semibold mb-2">Inventory Transactions for: {{ $product->name }}</h3>

                    {{-- Transaction Table --}}
                    @php
                        $transactions = \App\Models\InventoryTransaction::where('product_id', $product->id)
                            ->orderBy('movement_date', 'desc')
                            ->get();
                    @endphp

                    <div class="overflow-x-auto mb-10">
                        <table class="table-auto w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100 text-center">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">Type</th>
                                    <th class="px-4 py-2">Quantity</th>
                                    <th class="px-4 py-2">Unit</th>
                                    <th class="px-4 py-2">Source</th>
                                    <th class="px-4 py-2">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $tx)
                                    <tr class="text-center border-t">
                                        <td class="px-4 py-2">{{ $tx->movement_date }}</td>
                                        <td class="px-4 py-2">{{ $tx->movement_type == 'in' ? 'In' : 'Out' }}</td>
                                        <td class="px-4 py-2">{{ $tx->quantity }}</td>
                                        <td class="px-4 py-2">{{ $tx->unit?->name }}</td>
                                        <td class="px-4 py-2">{{ $tx->formatted_transactionable_type }}
                                            #{{ $tx->transactionable_id }}</td>
                                        <td class="px-4 py-2">{{ $tx->notes }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500 py-4">No transactions found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Inventory Summary (getInventoryForProduct) --}}
                    @php
                        $inventoryService = new \App\Services\MultiProductsInventoryService(storeId: getDefaultStore());
                        $inventorySummary = $inventoryService->getInventoryForProduct($product->id);
                    @endphp

                    <div class="overflow-x-auto">
                        <h4 class="font-bold text-base mb-2">Current Inventory</h4>
                        <table class="table-auto w-full text-sm border border-gray-200">
                            <thead class="bg-gray-100 text-center">
                                <tr>
                                    <th class="px-4 py-2">Unit</th>
                                    <th class="px-4 py-2">Remaining Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventorySummary as $row)
                                    <tr class="text-center border-t even:bg-gray-50">

                                        <td class="px-4 py-2">{{ $row['unit_name'] }}</td>
                                        <td class="px-4 py-2">{{ $row['remaining_qty'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($selectedCategory)
        <div class="text-center text-gray-600 mt-8">
            No products found in this category.
        </div>
    @endif
</x-filament::page>
