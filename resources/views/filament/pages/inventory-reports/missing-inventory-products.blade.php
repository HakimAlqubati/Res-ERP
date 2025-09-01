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
        <table class="w-full text-sm text-left pretty table-striped reports" id="report-table">
            <thead class="fixed-header">
                {{-- رأس احترافي --}}
                <tr class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                    <th colspan="{{ $storeId == 'all' ? 3 : 2 }}" class="no_border_right_left text-center">
                        <h3 class="text-lg font-bold">
                            Products Not Inventoried ({{ $startDate }} - {{ $endDate }})
                            <br>
                            <span class="text-sm font-normal">Store: {{ $store }}</span>
                        </h3>
                    </th>
                    <th colspan="1" class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                        style="text-align: center;">
                        <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                            class="logo-left circle-image" style="max-height: 60px;">
                    </th>
                </tr>

                <tr>
                    <th>Product Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Remaining Qty</th>
                    @if ($storeId === 'all')
                        <th>In Store</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $product)
                    <tr @class([
                        'qty_zero' => $product->remaining_qty <= 0, // Tailwind class for light red background
                    ])>
                        <td>{{ $product->code ?? '-' }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name ?? '—' }}</td>
                        <td>{{ $product->remaining_qty . ' ' . $product->smallest_unit_name }}</td>
                        @if ($storeId == 'all')
                            <td>{{ $product->store_name }}</td>
                        @endif

                    </tr>
                @endforeach
            </tbody>
        </table>
        <!-- Pagination Links -->
        <div class="mt-4">
            <div class="paginator_container">
                @if (isset($reportData) && $reportData instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $reportData->links() }}
                @endif
            </div>


            <x-per-page-selector />
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
