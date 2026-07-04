<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        .batch-report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .batch-report-table th,
        .batch-report-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            text-align: left;
        }

        .batch-report-table thead th {
            background-color: #f9fafb;
            font-weight: 600;
            white-space: nowrap;
        }

        .batch-report-table .product-group-row {
            background-color: #f3f4f6;
        }

        .batch-report-table .product-group-row td {
            font-weight: 700;
            font-size: 0.875rem;
            color: #1f2937;
            padding: 6px 12px;
        }

        .batch-report-table .batch-current-row {
            background-color: #f0fdf4;
        }

        .batch-report-table .footer-row  {
            position: sticky;
            bottom: 0;
            background: white;
            font-weight: 700;
            color: #0d7c66;
            z-index: 10;
        }

        .batch-report-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .batch-report-table .shortage-row {
            background-color: #fef2f2;
        }
        
        .batch-report-table .shortage-row:hover {
            background-color: #fee2e2;
        }

        .fi-tabs {
            display: none !important;
        }

        /* Dark mode */
        .dark .batch-report-table th,
        .dark .batch-report-table td {
            border-color: #374151;
        }

        .dark .batch-report-table thead th {
            background-color: #1f2937;
            color: #e5e7eb;
        }

        .dark .batch-report-table .product-group-row {
            background-color: #374151;
        }

        .dark .batch-report-table .product-group-row td {
            color: #e5e7eb;
        }

        .dark .batch-report-table .batch-current-row {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .dark .batch-report-table .shortage-row {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .dark .batch-report-table .footer-row {
            background: #1f2937;
        }

        .dark .batch-report-table tbody tr:hover {
            background-color: #1f2937;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    @if ($storeId && $compoundProductId)
        @if ($reportResult && $reportResult->count() > 0)

            <div id="reportContent">
                <table class="batch-report-table" id="report-table">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                            <th colspan="5" class="no_border_right_left" style="text-align: center;">
                                <h3>Recipe Ingredients Stock Report</h3>
                                @if($compoundProduct)
                                    <p style="margin-top: 5px; font-weight: normal;">
                                         <strong>{{ $compoundProduct->code }} - {{ $compoundProduct->name }}</strong>
                                    </p>
                                @endif
                            </th>
                            <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}" style="text-align: center;">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                    class="logo-left circle-image" style="display: inline-block;">
                            </th>
                        </tr>

                        <tr>
                            <th>Code</th>
                            <th>Component Name</th>
                            <th>Unit</th>
                            <th>Recipe Qty</th>
                            <th>Waste %</th>
                            <th>Required Qty</th>
                            <th>Qty in Stock</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reportResult as $component)
                            <tr class="{{ $component['has_shortage'] ? 'shortage-row' : '' }}">
                                <td>{{ $component['product_code'] }}</td>
                                <td>{{ $component['product_name'] }}</td>
                                <td>{{ $component['unit_name'] ?? '-' }}</td>
                                <td>{{ $component['recipe_quantity'] }}</td>
                                <td>{{ $component['waste_percentage'] }}%</td>
                                <td>{{ formatQunantity($component['required_quantity_for_one_unit']) }}</td>
                                <td style="font-weight: 600;" class="{{ $component['has_shortage'] ? 'text-danger-600 dark:text-danger-400' : '' }}">
                                    {{ formatQunantity($component['available_balance']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No components found for this compound product.</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please select a Store and a Compound Product</h1>
        </div>
    @endif

    {{-- Print --}}
    <script>
        document.getElementById("printReport")?.addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent")?.innerHTML;
            if (!printContent) return;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    {{-- Export Excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById("exportExcel")?.addEventListener("click", function() {
            const table = document.querySelector("#reportContent table");
            if (!table) return;

            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Components Stock Report"
            });
            XLSX.writeFile(wb, "components_stock_report.xlsx");
        });
    </script>
 
</x-filament::page>
