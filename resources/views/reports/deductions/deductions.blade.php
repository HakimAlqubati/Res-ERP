<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        .td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        th {
            width: 50%;
        }

        .employee-name {
            text-align: center;
            margin: 20px 0;
            font-size: 1.5em;
            color: #2c3e50;
            font-weight: bold;
            padding: 10px;
            background: linear-gradient(to right, #45a049, #45a099);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px #45a049;
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
    </style>
    {{-- @if (isset($employeeId) && is_numeric($employeeId) && isset($year) && is_numeric($year)) --}}
    @if (isset($year) &&
            is_numeric($year) &&
            ((isset($branchId) && is_numeric($branchId)) || (isset($employeeId) && is_numeric($employeeId))))
        <div class="text-right mb-4">
            <button onclick="printReport()" class="btn btn-print">
                {{ __('Print Report') }}
            </button>
        </div>
        <div id="report-table">
            <div class="employee-name">
                {{ $branch ? $branch->name : $employee?->name }}
            </div>


            <h1>Deductions Summary</h1>



            <h3>Current Month Deductions - {{ $last_month_name }}</h3>

            <x-filament-tables::table>
                <thead>
                    <x-filament-tables::row>
                        <th>Deduction Name</th>
                        <th>Deduction Amount</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @forelse ($lastMonthDeductions as $deduction)
                        <x-filament-tables::row>
                            <x-filament-tables::cell
                                class='td'>{{ $deduction['deduction_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class='td'>{{ number_format($deduction['deduction_amount'], 2) }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @empty
                        <x-filament-tables::row>
                            <x-filament-tables::cell class='td' colspan="2">No deductions found for the last
                                month.</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforelse
                </tbody>
            </x-filament-tables::table>



            <h2>Total Deductions (Year To Date)</h2>
            <x-filament-tables::table>
                <thead>
                    <x-filament-tables::row>
                        <th>Deduction Name</th>
                        <th>Total Deduction Amount</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @forelse ($totalDeductions as $deduction)
                        <x-filament-tables::row>
                            <x-filament-tables::cell
                                class='td'>{{ $deduction['deduction_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class='td'>{{ number_format($deduction['deduction_amount'], 2) }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @empty
                        <x-filament-tables::row>
                            <x-filament-tables::cell class='td' colspan="2">No deductions
                                found.</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforelse
                </tbody>
            </x-filament-tables::table>
        </div>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an (Employee / Branch) and Year') }}</h1>
        </div>
    @endif
</x-filament-panels::page>

<script>
    function printReport() {
        // Hide the print button and modal while printing
        const printButton = document.querySelector('button[onclick="printReport()"]');
        const modal = document.querySelector('.fixed.inset-0');

        if (printButton) printButton.style.display = 'none';
        if (modal) modal.style.display = 'none';

        window.print();

        // Restore visibility after printing
        if (printButton) printButton.style.display = 'block';
        if (modal) modal.style.display = 'flex';
    }
</script>
