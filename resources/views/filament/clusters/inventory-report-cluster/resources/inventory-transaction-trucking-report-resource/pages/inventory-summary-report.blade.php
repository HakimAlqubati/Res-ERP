<x-filament::page>
    {{-- ✅ Category Filter --}}
    <form method="GET" class="mb-6 flex items-center gap-6">
        <div class="flex flex-col">

            <label class="block mb-1 font-bold text-lg">Select Category:</label>

            <select name="category_id" onchange="this.form.submit()" class="border p-2 rounded w-1/3">
                <option value="">-- All Categories --</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($selectedCategory == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ✅ Show Without Zero Checkbox --}}
        <div class="flex items-center mt-6">
            <label class="inline-flex items-center space-x-3 text-lg font-medium">
                <input type="checkbox" name="show_without_zero" value="1" onchange="this.form.submit()"
                    @if (request('show_without_zero')) checked @endif
                    class="form-checkbox h-6 w-6 text-indigo-600 border-gray-300 rounded">
                <span>Show only items with abnormal inventory movements</span>
            </label>
        </div>
    </form>
    {{-- ✅ Inventory Summary --}}
    {{-- @if (!is_null($selectedCategory) && count($products)) --}}
    <h3 class="text-lg font-semibold mb-4">
        Inventory Summary for Category: {{ $categories->find($selectedCategory)?->name }}
    </h3>

    <div class="overflow-x-auto">

        <table class="table-auto w-full text-sm border border-gray-200">

            <thead class="bg-gray-100 text-center">
                <tr>
                    <th class="px-4 py-2">Item Code</th>
                    <th class="px-4 py-2">Product Name</th>
                    <th class="px-4 py-2">Unit</th>
                    <th class="px-4 py-2">Opening Stock</th>
                    <th class="px-4 py-2">Total Orders to Date</th>
                    <th class="px-4 py-2">Stock Today</th>
                    <th class="px-4 py-2">Discrepancy
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($reportData as $row)
                    <tr class="text-center odd:bg-white even:bg-gray-50 hover:bg-indigo-50 transition duration-150">

                        <td class="px-4 py-2">{{ $row['product_code'] }}</td>
                        <td class="px-4 py-2">{{ $row['product_name'] }}</td>
                        <td class="px-4 py-2">{{ $row['unit_name'] }}</td>
                        <td class="px-4 py-2">{{ $row['opening_stock'] }}</td> {{-- Opening Stock (placeholder) --}}
                        <td class="px-4 py-2">{{ $row['total_orders'] }}</td> {{-- Total Orders (placeholder) --}}
                        <td class="px-4 py-2">{{ $row['remaining_qty'] }}</td>
                        <td class="px-4 py-2">{{ $row['calculated_stock'] }}</td>
                    </tr>
                @endforeach
                {{-- @endforeach --}}
            </tbody>
        </table>
        @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $products->appends(request()->query())->links() }}
                {{-- 🔽 Select Dropdown for perPage --}}
                <form method="GET" class="mt-2">
                    {{-- إعادة تمرير باقي الفلاتر --}}
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    <input type="hidden" name="show_without_zero" value="{{ request('show_without_zero') }}">

                    <label for="per_page" class="mr-2 text-sm font-medium">Items per page:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()"
                        class="border p-1 rounded text-sm">
                        @foreach ([5, 10, 15, 20, 50, 100] as $option)
                            <option value="{{ $option }}" @selected(request('per_page', 15) == $option)>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
    </div>
    {{-- @else
        <div class="text-center text-gray-600 mt-8">
            No products found in this category.
        </div>
    @endif --}}
</x-filament::page>
