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

    {{-- Report --}}
    @if (!empty($revenues) || !empty($expenses))
        <div id="reportContent">
            <table class="table-auto w-full text-sm border border-gray-300 pretty reports" id="report-table">
                <thead class="bg-gray-100 text-center">
                    <x-filament-tables::row class="header_report">
                        <th colspan="3" class="text-center font-bold text-lg">📊 Profit & Loss Report</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    {{-- Revenues --}}
                    <x-filament-tables::row class="bg-green-100">
                        <x-filament-tables::cell colspan="3"><strong>Revenues</strong></x-filament-tables::cell>
                    </x-filament-tables::row>
                    @foreach ($revenues as $rev)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $rev['name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $rev['code'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class="text-right">{{ number_format($rev['amount'], 2) }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                    <x-filament-tables::row class="font-bold">
                        <x-filament-tables::cell colspan="2">Total Revenue</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="text-right">{{ number_format($totals['total_revenue'], 2) }}</x-filament-tables::cell>
                    </x-filament-tables::row>

                    {{-- Expenses --}}
                    <x-filament-tables::row class="bg-red-100">
                        <x-filament-tables::cell colspan="3"><strong>Expenses</strong></x-filament-tables::cell>
                    </x-filament-tables::row>
                    @foreach ($expenses as $exp)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $exp['name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $exp['code'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class="text-right">{{ number_format($exp['amount'], 2) }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                    <x-filament-tables::row class="font-bold">
                        <x-filament-tables::cell colspan="2">Total Expenses</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="text-right">{{ number_format($totals['total_expense'], 2) }}</x-filament-tables::cell>
                    </x-filament-tables::row>

                    {{-- Net Profit --}}
                    <x-filament-tables::row class="font-bold bg-gray-100">
                        <x-filament-tables::cell colspan="2">Net Profit</x-filament-tables::cell>
                        <x-filament-tables::cell class="text-right">
                            {{ number_format($totals['net_profit'], 2) }}
                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <h3>No financial data available for the selected period.</h3>
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
