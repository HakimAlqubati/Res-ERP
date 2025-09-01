<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .arrow-icon {
            margin-left: 5px;
            font-size: 14px;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>

    <div class="text-right mb-4">
        <button onclick="printReport()" class="btn btn-print">
            {{ __('Print Report') }}
        </button>
    </div>

    <table class="w-full text-sm text-left pretty reports" id="report-table">
        <thead>
            <tr class="header_report">
                <th rowspan="2">{{ __('Employee') }}</th>
                <th colspan="2">{{ __('Shift data') }}</th>
                <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                <th colspan="3">{{ __('Work Hours Summary') }}</th>
            </tr>

            <tr class="fixed-header">
                <th class="internal_cell">{{ __('From') }}</th>
                <th class="internal_cell">{{ __('To') }}</th>
                <th class="internal_cell">{{ __('Check-in') }}</th>

                <!-- Sortable columns with up and down arrows -->
                <th class="internal_cell cursor-pointer" onclick="sortTable('checkin_status')">
                    {{ __('Status') }}
                    <span id="checkin-status-arrow" class="arrow-icon">&#x2195;</span> <!-- Up/Down arrow -->
                </th>
                <th class="internal_cell">{{ __('Check-out') }}</th>
                <th class="internal_cell cursor-pointer" onclick="sortTable('checkout_status')">
                    {{ __('Status') }}
                    <span id="checkout-status-arrow" class="arrow-icon">&#x2195;</span> <!-- Up/Down arrow -->
                </th>

                <th class="internal_cell">{{ __('Supposed') }}</th>
                <th class="internal_cell">{{ __('Total Hours Worked') }}</th>
                <th class="internal_cell">{{ __('Approved') }}</th>
            </tr>
        </thead>

        <tbody id="report-body">
            @foreach ($report_data as $empId => $data_)
                @php
                    $date = array_keys($data_)[0];
                    $data = array_values($data_);
                @endphp

                <tr>
                    <td>{{ $data[0]['employee_name'] }}</td>
                    @if (count($data[0]['periods']) > 0 && !isset($data[0]['leave']))
                        <td colspan="9">
                            <table>
                                @foreach ($data[0]['periods'] as $item)
                                    <tr>
                                        <td
                                            class="internal_cell">{{ $item['start_at'] }}</td>
                                        <td
                                            class="internal_cell">{{ $item['end_at'] }}</td>

                                        @if (isset($item['attendances']['checkin']))
                                            <td
                                                class="internal_cell">{{ $item['attendances']['checkin'][0]['check_time'] }}</td>
                                            <td
                                                class="internal_cell status_cell">{{ $item['attendances']['checkin'][0]['status'] }}</td>
                                        @endif
                                        @if (isset($item['attendances']['checkout']))
                                            <td
                                                class="internal_cell">{{ $item['attendances']['checkout']['lastcheckout']['check_time'] }}</td>
                                            <td
                                                class="internal_cell status_cell">{{ $item['attendances']['checkout'][0]['status'] }}</td>
                                            <td
                                                class="internal_cell">{{ $item['attendances']['checkout'][0]['supposed_duration_hourly'] }}</td>
                                            <td
                                                class="internal_cell">{{ $item['attendances']['checkout']['lastcheckout']['approved_overtime'] }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    @elseif (isset($data[0]['leave']))
                        <td
                            colspan="9">{{ $data[0]['leave']['transaction_description'] }}</td>
                    @else
                        <td colspan="9">No periods available</td>
                    @endif
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <th colspan="7" class="text-right font-bold">{{ __('Total') }}</th>
                <td class="text-center">{{ $totalSupposed }}</td>
                <td class="text-center">{{ $totalWorked }}</td>
                <td class="text-center">{{ $totalApproved }}</td>
            </tr>
        </tfoot>
    </table>

    <script>
        let sortDirection = {
            checkin_status: 'asc',
            checkout_status: 'asc'
        };

        function sortTable(column) {
            const tbody = document.getElementById('report-body');
            const rows = Array.from(tbody.rows);
            const isAsc = sortDirection[column] === 'asc';

            // Toggle sorting direction
            sortDirection[column] = isAsc ? 'desc' : 'asc';

            // Update arrow direction
            const arrowElement = document.getElementById(`${column}-arrow`);
            if (arrowElement) {
                arrowElement.innerHTML = isAsc ? '&#x2193;' : '&#x2191;'; // Down/Up arrow
            } else {
                console.error(`Arrow element with ID ${column}-arrow not found!`);
            }

            // Sort rows based on column
            rows.sort((a, b) => {
                const cellA = a.querySelector(`td:nth-child(${getColumnIndex(column)})`);
                const cellB = b.querySelector(`td:nth-child(${getColumnIndex(column)})`);

                const textA = cellA ? cellA.innerText : '';
                const textB = cellB ? cellB.innerText : '';

                if (isAsc) {
                    return textA.localeCompare(textB, undefined, {
                        numeric: true
                    });
                } else {
                    return textB.localeCompare(textA, undefined, {
                        numeric: true
                    });
                }
            });

            // Re-append the sorted rows back to the tbody
            rows.forEach(row => tbody.appendChild(row));
        }

        // Helper function to determine column index based on column name
        function getColumnIndex(column) {
            switch (column) {
                case 'checkin_status':
                    return 4; // 'Check-in Status' column
                case 'checkout_status':
                    return 6; // 'Check-out Status' column
                default:
                    return 0; // Default to first column (Employee Name)
            }
        }
    </script>
</x-filament-panels::page>
