<x-filament-panels::page>

    <x-filament-tables::table class="w-full text-sm text-left pretty  ">
        <thead>


            <x-filament-tables::row>
                <th>
                    <p>
                        Employee no: {{ $employee_data?->employee_no }}
                    </p>
                </th>
                <th>
                    <p>
                        Employee name: {{ $employee_data?->name }}
                    </p>
                </th>
                <th>
                    <p>
                        Branch: {{ $employee_data?->branch?->name }}
                    </p>
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th>{{ 'Task id' }}</th>
                <th>{{ 'Rating value' }}</th>
                <th>{{ 'Rater comment' }}</th>
            </x-filament-tables::row>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <x-filament-tables::row>
                    <x-filament-tables::cell> {{ $item->task_id }} </x-filament-tables::cell>

                    <x-filament-tables::cell> {{ $item->rating_value }} /10</x-filament-tables::cell>

                    <x-filament-tables::cell> {{ $item->ratter_comment }} </x-filament-tables::cell>

                </x-filament-tables::row>
            @endforeach
        </tbody>

    </x-filament-tables::table>


</x-filament-panels::page>
