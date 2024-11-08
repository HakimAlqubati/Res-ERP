<x-filament-panels::page>


    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" style="padding-top: 200px;">
        <thead>
            <x-filament-tables::row>
                <th>Installment ID</th>
                <th>Installment Amount</th>
                <th>Due Date</th>
                <th>Is Paid</th>
            </x-filament-tables::row>
        </thead>
        <tbody>

            @foreach ($this->getTableQuery() as $item)
                <x-filament-tables::row>

                    <x-filament-tables::cell>
                        {{ $item->id }}
                    </x-filament-tables::cell>
                    <x-filament-tables::cell>
                        {{ $item->installment_amount }}
                    </x-filament-tables::cell>
                    <x-filament-tables::cell>
                        {{ $item->due_date }}
                    </x-filament-tables::cell>
                    <x-filament-tables::cell>
                        {{ $item->is_paid }}
                    </x-filament-tables::cell>

                </x-filament-tables::row>
            @endforeach
        </tbody>
    </x-filament-tables::table>
</x-filament-panels::page>
