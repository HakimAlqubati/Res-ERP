<x-filament::page>
    {{-- Print Button --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{-- Filters --}}
    {{ $this->getTableFiltersForm() }}

    {{-- Report Table --}}
    @if (!empty($reportData))
        <div id="reportContent">
            <table class="table-auto w-full text-sm border border-gray-200 pretty  reports" id="report-table">
                <thead class="bg-gray-100 text-center">
                    <x-filament-tables::row class="header_report">
                        <th colspan="5" class="text-center font-bold text-lg">📊 Trial Balance Report</th>
                    </x-filament-tables::row>
                    <x-filament-tables::row>
                        <th>Account Name</th>
                        <th>Account Code</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp

                    @foreach ($reportData as $row)
                        @php
                            $totalDebit += $row['debit'];
                            $totalCredit += $row['credit'];
                        @endphp
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $row['account_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['account_code'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ number_format($row['debit'], 2) }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ number_format($row['credit'], 2) }}</x-filament-tables::cell>
                            <x-filament-tables::cell>
                                <strong>{{ number_format($row['balance'], 2) }}</strong>
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach

                    <x-filament-tables::row class="font-bold bg-gray-100">
                        <x-filament-tables::cell colspan="2">Total</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ number_format($totalDebit, 2) }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ number_format($totalCredit, 2) }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ number_format($totalDebit - $totalCredit, 2) }}</x-filament-tables::cell>
                    </x-filament-tables::row>
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <h3>No data available for the selected date range.</h3>
        </div>
    @endif

    {{-- JavaScript for printing --}}
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
