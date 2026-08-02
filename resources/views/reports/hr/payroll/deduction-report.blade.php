<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        /* Print-specific styles */
        @media print {
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

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 10px;
                font-size: 12px;
                color: #000;
            }

            th {
                background-color: #ddd;
            }

            td {
                background-color: #fff;
            }
        }

        .btn-primary {
            border: 1px solid green;
            border-radius: 5px;
            padding: 0px 10px 0px 10px;
            min-width: 150px;
        }

        .btn-refresh {
            border: 1px solid green;
            border-radius: 5px;
            padding: 0px 10px 0px 10px;
            min-width: 150px;
            margin-top: 5px;
        }

        .pretty th,
        .pretty td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .pretty th {
            /* Attendance report specific green */
            color: #0b7a5a;
            font-weight: bold;
        }

        .pretty th,
        .pretty td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .pretty thead th {
            color: #0b7a5a;
            font-weight: bold;
            background-color: #fff;
        }

        .pretty tbody tr:nth-child(odd) td {
            background-color: #f0fdf4;
            color: #111827;
        }

        .pretty tbody tr:nth-child(even) td {
            background-color: #ffffff;
            color: #111827;
        }

        .pretty tfoot td {
            background-color: #eaeaea;
            color: #111827;
            font-weight: bold;
        }
    </style>

    @if ($reportData && ($reportData['employee_id'] || !empty($reportData['branch_ids'])))
    @php
    $employee = \App\Models\Employee::find($reportData['employee_id']);
    $branches = !empty($reportData['branch_ids']) ? \App\Models\Branch::whereIn('id', $reportData['branch_ids'])->get() : collect();

    $avatarUrl = $employee ? $employee->avatar_image : url('/storage/workbench.png');
    $displayName = $employee ? $employee->name : ($branches->isNotEmpty() ? ($branches->count() > 2 ? __('Multiple Branches') : $branches->pluck('name')->join(' & ') . ' - ' . __('Branch')) : __('All Employees'));
    @endphp

    <table class="w-full text-sm text-left pretty" id="report-table">
        <thead class="fixed-header" style="top:64px;">
            <tr class="header_report">
                <th colspan="2" style="padding: 12px 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        {{-- Left: Buttons --}}
                        <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0;">
                            <button type="button" onclick="event.preventDefault(); exportToExcel();" class="btn btn-primary">
                                &#128200; {{ __('Export Excel') }}
                            </button>
                        </div>

                        {{-- Center: Avatar + Name --}}
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex: 1;">
                            @if ($employee && $employee->avatar_image)
                            <img src="{{ $employee->avatar_image }}" alt="{{ $employee->name }}"
                                style="width: 90px; height: 90px; border-radius: 12px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.18);">
                            @endif
                            <span style="font-size: 16px; font-weight: bold;">{{ $displayName }}</span>
                        </div>

                        {{-- Right: Dates --}}
                        <div style="text-align: center; flex-shrink: 0; line-height: 1.8;">
                            <span style="font-weight: 600;">{{ __('From Date') . ': ' . $reportData['from_date'] }}</span>
                            <br>
                            <span style="font-weight: 600;">{{ __('To Date') . ': ' . $reportData['to_date'] }}</span>
                        </div>

                        {{-- Far Right: Logo --}}
                        <div style="flex-shrink: 0; text-align: center;">
                            <img class="circle-image" src="{{ url('/storage/workbench.png') }}" alt="Logo" style="width: 70px; height: 70px; object-fit: contain;">
                        </div>
                    </div>
                </th>
            </tr>
            <tr>
                <th style="width: 70%; text-align: left; direction: ltr; padding-left: 1.5rem;">{{ __('Deduction Description') }}</th>
                <th style="width: 30%; text-align: center;">{{ __('Amount') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($reportData['employees_deductions'] as $empData)
            <!-- Employee Header Row -->
            <tr>
                <td colspan="2" style="background-color: #e5e7eb !important; color: #111827 !important; font-weight: bold; text-align: center; padding: 12px; font-size: 1.1em;">
                    {{ $empData['employee_name'] }} - ({{ $empData['branch_name'] ?? __('Unknown Branch') }})
                </td>
            </tr>

            @foreach ($empData['monthly_deductions'] as $monthData)
            <!-- Month Header Row -->
            <tr>
                <td colspan="2" style="background-color: #f0fdf4 !important; color: #0b7a5a !important; font-weight: bold; text-align: left; padding-left: 15px;">
                    {{ $monthData['month_name'] }}
                </td>
            </tr>
            @foreach ($monthData['deductions_list'] as $item)
            <tr>
                <td style="text-align: left; direction: ltr; padding-left: 1.5rem;">{{ __($item['deduction_name']) }}</td>
                <td style="text-align: right; font-weight: bold;">{{ formatMoneyWithCurrency($item['deduction_amount']) }}</td>
            </tr>
            @endforeach
            <!-- Month Footer Row -->
            <tr>
                <td style="text-align: right; font-weight: bold; background-color: #fafafa !important; color: #111827 !important;">
                    {{ __('Total for :month', ['month' => $monthData['month_name']]) }}
                </td>
                <td style="text-align: right; font-weight: bold; background-color: #fafafa !important; color: #d9534f !important;">
                    {{ formatMoneyWithCurrency($monthData['month_total']) }}
                </td>
            </tr>
            @endforeach

            <!-- Employee Footer Row -->
            <tr>
                <td style="text-align: right; font-weight: bold; background-color: #d1d5db !important; color: #111827 !important;">
                    {{ __('Total Deductions for Staff') }} ({{ $empData['employee_name'] }} - {{ $empData['branch_name'] ?? __('Unknown Branch') }})
                </td>
                <td style="text-align: right; font-weight: bold; background-color: #d1d5db !important; color: #d9534f !important;">
                    {{ formatMoneyWithCurrency($empData['total_deductions']) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align: center; padding: 2rem; color: #6b7280; font-weight: bold;">
                    {{ __('No deductions found for the selected period.') }}
                </td>
            </tr>
            @endforelse
        </tbody>

        @if(count($reportData['employees_deductions']) > 0)
        <tfoot>
            <tr>
                <td style="text-align: right; font-weight: bold; background-color:#eaeaea; color:#000;">{{ __('Grand Total Deductions') }}</td>
                <td style="text-align: right; font-weight: bold; background-color:#eaeaea; color:#d9534f;">
                    {{ formatMoneyWithCurrency($reportData['grand_total']) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    @else
    <div class="please_select_message_div" style="text-align: center; padding: 40px;">
        <h1 class="please_select_message_text" style="font-size: 1.5rem; color: #555;">{{ __('Please select an Employee and a Date Range') }}</h1>
    </div>
    @endif


    {{-- Excel Export Script --}}
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            if (typeof XLSX === 'undefined') {
                if (!document.getElementById('xlsx-library-script')) {
                    var script = document.createElement('script');
                    script.id = 'xlsx-library-script';
                    script.src = 'https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js';
                    script.onload = function() {
                        performExcelExport();
                    };
                    script.onerror = function() {
                        var fallback = document.createElement('script');
                        fallback.src = 'https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js';
                        fallback.onload = function() {
                            performExcelExport();
                        };
                        fallback.onerror = function() {
                            alert('تعذر تحميل مكتبة الإكسل. يرجى التأكد من اتصالك بالإنترنت والتحقق من عدم حجب روابط الـ CDN.');
                        };
                        document.head.appendChild(fallback);
                    };
                    document.head.appendChild(script);
                }
                return;
            }
            performExcelExport();
        }

        function performExcelExport() {
            var elt = document.getElementById('report-table');
            var clone = elt.cloneNode(true);

            // Clean buttons before export
            var buttons = clone.querySelectorAll('button');
            buttons.forEach(function(btn) {
                btn.remove();
            });

            var wb = XLSX.utils.table_to_sheet(clone, {
                raw: true
            });
            var wscols = [{
                wch: 50
            }, {
                wch: 20
            }];
            wb['!cols'] = wscols;

            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Deductions Report");
            
            var reportTitle = "{{ preg_replace('/[^A-Za-z0-9_\-]/', '_', $reportData['report_title'] ?? 'Report') }}";
            var fromDate = "{{ $reportData['from_date'] ?? '' }}";
            var toDate = "{{ $reportData['to_date'] ?? '' }}";
            var fileName = "Deductions_" + reportTitle + "_" + fromDate + "_to_" + toDate + ".xlsx";
            
            XLSX.writeFile(workbook, fileName);
        }
    </script>
</x-filament-panels::page>