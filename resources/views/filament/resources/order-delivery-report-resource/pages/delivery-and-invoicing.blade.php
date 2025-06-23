<x-filament::page>

    <div class="container py-4">
        {{-- Flex container for Title and Buttons --}}
        <div class="flex justify-between items-start mb-4">
            

            {{-- Action Buttons --}}
            <div class="flex space-x-2 report-actions">
                {{-- THIS BUTTON IS MODIFIED --}}
                <x-filament::button icon="heroicon-o-arrow-down-tray"
                    onclick="exportTableToCsv('report-table', 'delivery-invoicing-report.csv')">
                    Excel (.csv)
                </x-filament::button>


            </div>
        </div>

        @if ($report->isEmpty())
            <div class="alert alert-info text-center bg-blue-100 border border-blue-300 p-4 rounded">
                No data available to display in the report.
            </div>
        @else
            <div class="overflow-x-auto rounded shadow-sm">
                {{-- The table ID 'report-table' is important for the script --}}
                <x-filament-tables::table class="w-full text-sm text-left pretty reports" id="report-table">
                    <thead>
                        <x-filament-tables::row>
                            <th>#</th>
                            <th>Reseller</th>
                            <th>Delivery Total</th>
                            <th>Invoiced Total</th>
                            <th>Balance</th>
                        </x-filament-tables::row>
                    </thead>
                    <tbody>
                        @foreach ($report as $index => $row)
                            <x-filament-tables::row class="{{ $row['balance'] > 0 ? 'bg-yellow-100' : '' }}">
                                <x-filament-tables::cell>{{ $index + 1 }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $row['branch'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ formatMoneyWithCurrency($row['do_total']) }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ formatMoneyWithCurrency($row['invoiced_total']) }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ formatMoneyWithCurrency($row['balance']) }}</x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                    </tbody>
                </x-filament-tables::table>
            </div>
        @endif
    </div>

    {{-- Add this script section at the end of the file --}}
    @push('scripts')
        <script>
            function exportTableToCsv(tableId, filename) {
                const table = document.getElementById(tableId);
                if (!table) {
                    console.error('Table not found!');
                    return;
                }

                let csv = [];
                // Get headers
                const headers = [];
                table.querySelectorAll('thead th').forEach(header => {
                    headers.push(`"${header.innerText.replace(/"/g, '""')}"`);
                });
                csv.push(headers.join(','));

                // Get rows
                table.querySelectorAll('tbody tr').forEach(row => {
                    const rowData = [];
                    row.querySelectorAll('td').forEach(cell => {
                        // Escape double quotes and wrap in double quotes
                        rowData.push(`"${cell.innerText.replace(/"/g, '""')}"`);
                    });
                    csv.push(rowData.join(','));
                });

                const csvContent = csv.join('\n');

                // Add BOM for UTF-8 to support Arabic characters in Excel
                const bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
                const blob = new Blob([bom, csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');

                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        </script>

        {{-- The print style from before --}}
    @endpush
</x-filament::page>
