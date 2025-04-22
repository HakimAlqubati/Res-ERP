<x-filament::page>
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (!empty($reportData))
        <div id="reportContent">
            <table class="table-auto w-full text-sm border border-gray-300 pretty reports" id="report-table">
                <thead class="bg-gray-100 text-center">
                    <x-filament-tables::row class="header_report">
                        <th colspan="3" class="text-center font-bold text-lg">📊 Balance Sheet Report</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $section => $accounts)
                        <x-filament-tables::row class="bg-gray-200">
                            <x-filament-tables::cell colspan="3"><strong>{{ $section }}</strong></x-filament-tables::cell>
                        </x-filament-tables::row>
                        @php $sectionTotal = 0; @endphp
                        @foreach ($accounts as $acc)
                            @php $sectionTotal += $acc['balance']; @endphp
                            <x-filament-tables::row>
                                <x-filament-tables::cell>{{ $acc['name'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $acc['code'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right">{{ number_format($acc['balance'], 2) }}</x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                        <x-filament-tables::row class="font-bold">
                            <x-filament-tables::cell colspan="2">Total {{ $section }}</x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right">{{ number_format($sectionTotal, 2) }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <h3>No financial data available for the selected period.</h3>
        </div>
    @endif

    <script>
        document.getElementById("printReport").addEventListener("click", function () {
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
