<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if ($reportData->isNotEmpty())
        <x-filament-tables::table class="w-full text-sm text-left pretty table-striped reports">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th colspan="3">
                        <h3>Products Not Inventoried Between {{ $startDate }} - {{ $endDate }}</h3>
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>Product Code</th>
                    <th>Name</th>
                    <th>Category</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($reportData as $product)
                    <x-filament-tables::row>
                        <x-filament-tables::cell>{{ $product->code ?? '-' }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $product->name }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $product->category->name ?? '—' }}</x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="text-center text-gray-500 mt-6">
            <p>No products missing from inventory within the selected dates.</p>
        </div>
    @endif
</x-filament::page>
