<x-filament::page>
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 text-white">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if ($account)
        <div id="reportContent">
            <h2 class="text-lg font-bold mb-4">Account: {{ $account->code }} - {{ $account->name }}</h2>

            <table class="table-auto w-full text-sm border border-gray-200 pretty reports" id="report-table">
                <thead class="bg-gray-100 text-center">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $balance = 0; @endphp
                    @foreach ($entries as $entry)
                        @php
                            $balance += $entry['debit'] - $entry['credit'];
                        @endphp
                        <tr>
                            <td>{{ $entry['date'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="text-right">{{ number_format($entry['debit'], 2) }}</td>
                            <td class="text-right">{{ number_format($entry['credit'], 2) }}</td>
                            <td class="text-right font-bold">{{ number_format($balance, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <h3>Please select an account and date range to view the ledger.</h3>
        </div>
    @endif

    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>
</x-filament::page>
