<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            table,
            table * {
                visibility: visible;
            }

            table {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .filament-tables-pagination,
            .filament-header-actions,
            .filament-header,
            .filament-tables-filters,
            .filament::page>form,
            .mb-4.flex.justify-end {
                display: none !important;
            }
        }
    </style>

    @if ($reportData->isNotEmpty())
        <div class="mb-4 flex justify-end gap-2">

            <button onclick="window.print()" class="bg-primary-600 text-white px-4 py-2 rounded shadow">
                🖨️ Print Report
            </button>
            <button onclick="exportTableToExcel('report-table', 'missing_inventory_report')"
                class="bg-primary-600 text-white px-4 py-2 rounded shadow">
                📥 Export to Excel
            </button>
        </div>
        <x-filament-tables::table class="w-full text-sm text-left pretty table-striped reports" id="report-table">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th colspan="4">
                        <h3>Products Not Inventoried Between {{ $startDate }} - {{ $endDate }}
                            - {{ '      ' }}
                            In ({{ $store }})

                        </h3>

                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>Product Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>{{ 'Remaining Qty' }}</th>

                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($reportData as $product)
                    <x-filament-tables::row @class([
                        'qty_zero' => $product->remaining_qty <= 0, // Tailwind class for light red background
                    ])>
                        <x-filament-tables::cell>{{ $product->code ?? '-' }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $product->name }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $product->category->name ?? '—' }}</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $product->remaining_qty . ' ' . $product->smallest_unit_name }}</x-filament-tables::cell>

                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>
        <!-- Pagination Links -->
        <div class="mt-4">
            {{ $reportData->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
        <div class="flex justify-end mb-2">
            <form method="GET">
                <label for="perPage" class="mr-2 font-semibold text-sm">Items per page:</label>
                <select name="perPage" id="perPage" onchange="this.form.submit()"
                    class="border border-gray-300 px-3 py-1 rounded-md text-sm">
                    @foreach ([5, 10, 15, 20, 30, 50, 'all'] as $option)
                        <option value="{{ $option }}" {{ request('perPage', 15) == $option ? 'selected' : '' }}>
                            {{ is_numeric($option) ? $option : 'All' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @else
        <div class="text-center text-gray-500 mt-6">
            <p>No products missing from inventory within the selected dates.</p>
        </div>
    @endif
</x-filament::page>

<script>
    function exportTableToExcel(tableId, filename = '') {
        const downloadLink = document.createElement("a");
        const table = document.getElementById(tableId);
        const tableHTML = table.outerHTML.replace(/ /g, '%20');

        filename = filename ? filename + '.xls' : 'excel_data.xls';

        downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
</script>
