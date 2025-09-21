<x-filament::page>

    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .text-center {
            text-align: center;
        }

        /* Print-specific styles */
        @media print {

            /* Hide everything except the table */
            body * {
                visibility: hidden;
            }

            #report-table,
            #report-table * {
                visibility: visible;
            }

            #report-table {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }

            /* Add borders and spacing for printed tables */
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 10px;
                font-size: 12px;
                /* Adjust font size for better readability */
                color: #000;
                /* Black text for headers */
            }

            th {
                background-color: #ddd;
                /* Light gray background for table headers */

            }

            td {
                background-color: #fff;
                /* White background for cells */
            }

        }

        .btn-print,
        .btn-primary {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-print {
            background-color: #4CAF50;
            color: white;
        }

        .btn-print:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .btn-print i,
        .btn-primary i {
            margin-right: 8px;
        }
    </style>
    <div class="flex justify-end mb-4">
        {{-- <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button> --}}
    </div>

    {{ $this->getTableFiltersForm() }}

    {{-- @if (!empty($store)) --}}
    @if (!empty($reportData))
        <table class="w-full text-sm text-left border pretty reports table-striped" id="reportContent">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">


                    <th colspan="{{ empty($store) ? 7 : 6 }}" class="text-left text-xl font-bold px-4 py-2">
                        ({{ $store ?? 'All' }}) To Date {{ $toDate }}
                    </th>
                </tr>
                <tr class="bg-blue-50 text-xs text-gray-700">
                    @if (empty($store))
                        <th class="px-4 py-2">Reseller</th>
                    @endif
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Code</th>
                    <th class="px-4 py-2">Unit</th>
                    <th class="px-4 py-2">DO Qty</th>
                    <th class="px-4 py-2">Invoice Qty</th>
                    <th class="px-4 py-2">Remaining</th>
                    {{-- <th class="px-4 py-2">Price (Est.)</th> --}}
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $row)
                    <tr>
                        @if (empty($store))
                            <td class="border px-4 py-1 text-center">{{ $row['store_name'] }}</td>
                        @endif
                        <td class="border px-4 py-1 text-center">{{ $row['product_name'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $row['product_code'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $row['unit_name'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $row['in_qty'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $row['out_qty'] }}</td>
                        <td class="border px-4 py-1 text-center font-semibold">{{ $row['current_qty'] }}</td>
                        {{-- <td
                                class="border px-4 py-1 text-center">{{ $row['in_price'] }}</td> --}}
                    </tr>
                @endforeach
                @if (!empty($totals))
                    <tr class="bg-gray-100 font-bold">
                        @if (empty($store))
                            <td class="border px-4 py-1 text-center">Total</td>
                        @endif
                        <td class="border px-4 py-1 text-center" colspan="3">Total</td>
                        
                        <td class="border px-4 py-1 text-center">{{ $totals['in_qty'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $totals['out_qty'] }}</td>
                        <td class="border px-4 py-1 text-center">{{ $totals['current_qty'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <div class="please_select_message_div text-center">
            <p class="text-center mt-10 text-gray-500">No data available for this store/date range.</p>
        </div>
    @endif
    {{-- @else
        <div class="please_select_message_div text-center">
            <p class="text-center mt-10 text-gray-500">Please select a reseller.</p>
        </div>
    @endif --}}

    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").outerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>
</x-filament::page>
