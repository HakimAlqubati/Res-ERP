<x-filament::page>
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-lg shadow bg-white border border-gray-200">
        <h2 class="text-2xl font-bold mb-4 text-primary-700">🧾 Returned Order #{{ $order->id }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
            <p><span class="font-semibold">📅 Date:</span> {{ $order->returned_date->format('Y-m-d') }}</p>
            <p><span class="font-semibold">🏢 Branch:</span> {{ $order->branch->name ?? '-' }}</p>
            <p><span class="font-semibold">🏬 Store:</span> {{ $order->store->name ?? '-' }}</p>
            <p><span class="font-semibold">🧑‍💼 Created By:</span> {{ $order->creator->name ?? '-' }}</p>
            <p><span class="font-semibold">✅ Approved By:</span> {{ $order->approver->name ?? '-' }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg shadow border border-gray-200">
        <table class="w-full text-sm text-left bg-white pretty reports" id="report-table">
            <thead class="bg-gray-100 text-gray-800 font-semibold border-b">
                <tr>
                    <th class="px-4 py-2 border-r">#</th>
                    <th class="px-4 py-2 border-r"> Product Code</th>
                    <th class="px-4 py-2 border-r"> Product</th>
                    <th class="px-4 py-2 border-r"> Unit</th>
                    <th class="px-4 py-2 border-r"> Quantity</th>
                    <th class="px-4 py-2">📝 Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($order->details as $index => $detail)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-4 py-2 border-r">{{ $index + 1 }}</td>
                        <td class="px-4 py-2 border-r">{{ $detail->product->code ?? '-' }}</td>
                        <td class="px-4 py-2 border-r">{{ $detail->product->name ?? '-' }}</td>
                        <td class="px-4 py-2 border-r">{{ $detail->unit->name ?? '-' }}</td>
                        <td class="px-4 py-2 border-r">{{ number_format($detail->quantity, 2) }}</td>
                        <td class="px-4 py-2">{{ $detail->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>
