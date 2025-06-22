<x-filament::page>
    <div class="container py-4">
        <h2 class="mb-4 text-center text-primary-emphasis text-2xl font-bold">
            📦 Delivery & Invoicing Report
        </h2>

        @if ($report->isEmpty())
            <div class="alert alert-info text-center bg-blue-100 border border-blue-300 p-4 rounded">
                No data available to display in the report.
            </div>
        @else
            <div class="overflow-x-auto rounded shadow-sm">
                <table class="table table-bordered table-hover w-full text-center border border-gray-300">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Branch</th>
                            <th class="px-4 py-2">Delivery Total</th>
                            <th class="px-4 py-2">Invoiced Total</th>
                            <th class="px-4 py-2">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report as $index => $row)
                            <tr class="{{ $row['balance'] > 0 ? 'bg-yellow-100' : '' }}">
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $row['branch'] }}</td>
                                <td class="px-4 py-2">{{ formatMoneyWithCurrency($row['do_total']) }}</td>
                                <td class="px-4 py-2">{{ formatMoneyWithCurrency($row['invoiced_total']) }}</td>
                                <td class="px-4 py-2">{{ formatMoneyWithCurrency($row['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament::page>
