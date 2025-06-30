<x-filament::page>
    <div class="text-xl font-semibold mb-4">
        Showing details for: <span class="text-blue-600">{{ $category }}</span>
    </div> 
    @if (count($adjustments) > 0)
        <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
            <thead class="fixed-header">
                <tr>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Notes</th>
                    <th>Date</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adjustments as $item)
                    <x-filament-tables::row>
                        <x-filament-tables::cell>{{ $item['product'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $item['unit'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $item['notes'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $item['date'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $item['quantity'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $item['price'] }}</x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>
            <tbody>
                <x-filament-tables::row>
                    <x-filament-tables::cell colspan="5" class="text-right font-bold">Final Total Price</x-filament-tables::cell>
                    
                    <x-filament-tables::cell class="font-bold">{{ $totalPrice }}</x-filament-tables::cell>
                </x-filament-tables::row>
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="text-center text-gray-500 mt-10">No details found.</div>
    @endif
</x-filament::page>
