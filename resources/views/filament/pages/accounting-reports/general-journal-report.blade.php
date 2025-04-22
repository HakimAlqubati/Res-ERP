<x-filament::page>
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (!empty($entries))
        <div id="reportContent">
            <table class="table-auto w-full text-sm border border-gray-200 pretty reports" id="report-table">
                <thead class="bg-gray-100 text-center">
                    <x-filament-tables::row class="header_report">
                        <th colspan="5" class="text-center font-bold text-lg">📒 General Journal</th>
                    </x-filament-tables::row>
                    <x-filament-tables::row>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        @foreach ($entry->lines as $line)
                            <x-filament-tables::row>
                                <x-filament-tables::cell>{{ $entry->date }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $entry->description }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $line->account?->name }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ number_format($line->debit, 2) }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ number_format($line->credit, 2) }}</x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <h3>No journal entries found for the selected date range.</h3>
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
