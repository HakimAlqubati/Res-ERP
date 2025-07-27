<x-filament-panels::page>

    <div class="bg-white p-8 shadow-lg rounded-lg border border-gray-200 w-full">
        {{-- Header --}}
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-extrabold text-[#0d7c66]">INVOICE</h1>
                <p class="text-gray-600 text-sm">Invoice #{{ $record->id }}</p>
                <p class="text-gray-600 text-sm">Date: {{ $record->sale_date }}</p>
            </div>

            <div>
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" class="h-16">
            </div>
        </div>

        {{-- Client & Branch Info --}}
        <div class="grid grid-cols-2 gap-6 text-sm text-gray-700 mb-8">
            <div>
                <h3 class="font-semibold text-[#0d7c66] mb-2">Branch Info</h3>
                <p><strong>Branch:</strong> {{ $record->branch->name }}</p>
                <p><strong>Store:</strong> {{ $record->store->name }}</p>
            </div>
            <div>
                <h3 class="font-semibold text-[#0d7c66] mb-2">Payment Summary</h3>
                <p><strong>Total:</strong> {{ formatMoneyWithCurrency($record->total_amount) }}</p>
                <p><strong>Paid:</strong> {{ formatMoneyWithCurrency($record->total_paid) }}</p>
                <p><strong>Remaining:</strong> {{ formatMoneyWithCurrency($record->remaining_amount) }}</p>
            </div>
        </div>

        {{-- Items Table --}}
        <table id="invoice-table" class="w-full text-sm border border-collapse mb-6">
            <thead class="bg-[#0d7c66] text-white">
                <tr class="text-left">
                    <th class="border p-2">Product</th>
                    <th class="border p-2">Unit</th>
                    <th class="border p-2 text-center">Quantity</th>
                    <th class="border p-2 text-right">Unit Price</th>
                    <th class="border p-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td class="border p-2">{{ $item->product->name }}</td>
                        <td class="border p-2">{{ $item->unit->name ?? '-' }}</td>
                        <td class="border p-2 text-center">{{ $item->quantity }}</td>
                        <td class="border p-2 text-right">{{ formatMoneyWithCurrency($item->unit_price) }}</td>
                        <td class="border p-2 text-right">{{ formatMoneyWithCurrency($item->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Action Buttons --}}
        <div class="flex justify-end gap-4 mt-6">
            <x-filament::button onclick="window.print()">
                🖨️ Print
            </x-filament::button>
            <x-filament::button color="gray" onclick="exportTableToExcel('invoice-table', 'Invoice_{{ $record->id }}')">
                📄 Export Excel
            </x-filament::button>
        </div>
    </div>

    {{-- JS Excel Export --}}
    <script>
        function exportTableToExcel(tableID, filename = '') {
            const dataType = 'application/vnd.ms-excel';
            const tableSelect = document.getElementById(tableID);
            const tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            filename = filename ? filename + '.xls' : 'invoice.xls';

            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename;
            downloadLink.click();
        }
    </script>

</x-filament-panels::page>
