<x-filament::page>

    <div class="flex justify-end mb-4">


        <button id="printReport"
            class="px-6 py-2 ml-4 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>
    {{ $this->getTableFiltersForm() }}
    <div id="reportContent">
        @if (!empty($reportData))
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <tr class="header_report">
                        @if (!$groupByOrder)
                            <th>Order ID</th>
                        @endif
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Unit ID</th>
                        <th>Unit Name</th>
                        <th>Quantity</th>
                        @if (!$groupByOrder)
                            <th>Created At</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        @if (isset($row['details']))
                            {{-- Grouped --}}
                            @foreach ($row['details'] as $detail)
                                <tr>
                                    @if (!$groupByOrder)
                                        <td>{{ $row['order_id'] }}</td>
                                    @endif
                                    <td>{{ $detail['product_code'] }}</td>
                                    <td>{{ $detail['product_name'] }}</td>
                                    <td>{{ $detail['unit_id'] }}</td>
                                    <td>{{ $detail['unit_name'] }}</td>
                                    <td
                                        class="font-bold">{{ $detail['quantity'] }}</td>
                                    @if (!$groupByOrder)
                                        <td class="font-bold">
                                            {{ date('Y-m-d', strtotime($detail['created_at'])) }}<br>
                                            {{ date('H:i:s', strtotime($detail['created_at'])) }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            {{-- Ungrouped --}}
                            <tr>
                                @if (!$groupByOrder)
                                    <td>{{ $row['order_id'] }}</td>
                                @endif
                                <td>{{ $row['product_code'] }}</td>
                                <td>{{ $row['product_name'] }}</td>
                                <td>{{ $row['unit_id'] }}</td>
                                <td>{{ $row['unit_name'] }}</td>
                                <td
                                    class="font-bold">{{ $row['quantity'] }}</td>


                                @if (!$groupByOrder)
                                    <td class="font-bold">
                                        {{ date('Y-m-d', strtotime($row['created_at'])) }}<br>
                                        {{ date('H:i:s', strtotime($row['created_at'])) }}
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center mt-8">
                <h1 class="text-gray-500">No data available.</h1>
            </div>
        @endif
    </div>

    {{-- Print Script --}}
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
