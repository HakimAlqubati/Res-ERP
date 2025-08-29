<x-filament::page>
    <div class="text-xl font-semibold mb-4">
        Showing details for: <span class="text-blue-600">{{ $category }}</span>
    </div>
    @if (count($adjustments) > 0)
        <table class="w-full text-sm text-left pretty reports table-striped border">
            <thead class="fixed-header">
                <tr>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Notes</th>
                    <th>Date</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adjustments as $item)
                    <tr>
                        <td>{{ $item['product'] }}</td>
                        <td>{{ $item['unit'] }}</td>
                        <td>{{ $item['notes'] }}</td>
                        <td>{{ $item['date'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ $item['unit_price'] }}</td>

                        <td>{{ $item['price'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tbody>
                <tr>
                    <td colspan="6" class="text-right font-bold">Final Total
                        Price</td>

                    <td class="font-bold">{{ $totalPrice }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="text-center text-gray-500 mt-10">No details found.</div>
    @endif
</x-filament::page>
