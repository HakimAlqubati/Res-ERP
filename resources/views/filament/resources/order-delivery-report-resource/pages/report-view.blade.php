<x-filament::page>
    <x-filament::card>
        <x-slot name="header"> تقرير التسليم والفوترة حسب الفرع </x-slot>

        <table class="w-full text-sm text-left rtl:text-right">
            <thead>
                <tr>
                    <th>الفرع</th>
                    <th>إجمالي التسليم</th>
                    <th>إجمالي المدفوع</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report as $row)
                    <tr>
                        <td>{{ $row['branch'] }}</td>
                        <td>{{ formatMoneyWithCurrency($row['do_total']) }}</td>
                        <td>{{ formatMoneyWithCurrency($row['invoiced_total']) }}</td>
                        <td>{{ formatMoneyWithCurrency($row['balance']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
