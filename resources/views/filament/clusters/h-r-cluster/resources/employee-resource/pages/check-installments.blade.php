<x-filament-panels::page>


    <table class="w-full text-sm text-left pretty  reports" style="padding-top: 200px;">
        <thead>
            <tr>
                <th>Installment ID</th>
                <th>Installment Amount</th>
                <th>Due Date</th>
                <th>Is Paid</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($this->getTableQuery() as $item)
                <tr>

                    <td>
                        {{ $item->id }}
                    </td>
                    <td>
                        {{ $item->installment_amount }}
                    </td>
                    <td>
                        {{ $item->due_date }}
                    </td>
                    <td>
                        {{ $item->is_paid ? 'Paid' : 'Not Paid' }}
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
</x-filament-panels::page>
