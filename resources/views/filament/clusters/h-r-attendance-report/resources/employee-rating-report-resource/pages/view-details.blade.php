<x-filament-panels::page>

    <table class="w-full text-sm text-left pretty  ">
        <thead>


            <tr>
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
            </tr>
            <tr>
                <th>{{ 'Task id' }}</th>
                <th>{{ 'Rating value' }}</th>
                <th>{{ 'Rater comment' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td> {{ $item->task_id }} </td>

                    <td> {{ $item->rating_value }} /10</td>

                    <td> {{ $item->ratter_comment }} </td>

                </tr>
            @endforeach
        </tbody>

    </table>


</x-filament-panels::page>
