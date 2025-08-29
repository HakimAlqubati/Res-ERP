<x-filament::page>
    <div class="container py-4">

        {{-- Action Buttons --}}
        <div class="flex justify-between items-start mb-4">
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
                <table class="w-full text-sm text-left pretty reports" id="report-table">
                    <thead style="top:64px;">
                        <tr>
                            <th>#</th>
                            <th>Reseller</th>
                            <th>Total Sales</th>
                            <th>Total Payments</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report as $index => $row)
                            <tr class="{{ $row['balance'] > 0 ? 'bg-yellow-100' : '' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td>{{ formatMoneyWithCurrency($row['sales']) }}</td>
                                <td>{{ formatMoneyWithCurrency($row['payments']) }}</td>
                                <td>{{ formatMoneyWithCurrency($row['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
