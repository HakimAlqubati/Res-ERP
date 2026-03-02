<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
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

        .btn-print {
            background-color: #4CAF50;
            color: white;
        }

        .btn-print:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }



        .btn-print i,

        .star-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            /* background-color: #ea580c; */
            border-radius: 50%;
            font-size: 10px;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* Default (White Rows / Odd) -> Black Star */
        table tbody tr:nth-child(odd) .star-badge {
            color: black;
        }

        /* Striped (Green Rows / Even) -> White Star */
        table tbody tr:nth-child(even) .star-badge {
            color: white;
        }
    </style>
    <div class="text-right mb-4">


        {{-- <button type="button" class="btn btn-info" onclick="showChartModal()">
            📊 {{ __('Show Charts') }}
        </button> --}}

        {{-- <button onclick="printReport()" class="btn btn-print">
            &#128438; {{ __('Print Report') }}
        </button>
        --}}

    </div>

    @if (isset($employee_id) && is_numeric($employee_id))
    <table class="w-full text-sm text-left pretty reports" id="report-table">
        <thead class="fixed-header" style="top:64px;">
            @php
            $employee = \App\Models\Employee::find($employee_id);
            @endphp
            <tr class="header_report">
                <th colspan="11" style="padding: 12px 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        {{-- Left: Buttons --}}
                        <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0;">
                            <button onclick="exportToExcel()" class="btn btn-primary">
                                &#128200; {{ __('lang.to_excel') }}
                            </button>
                            <button type="button" class="btn btn-secondary btn-refresh" wire:click="refreshData">
                                🔄 {{ __('lang.refresh') }}
                            </button>
                            <div wire:loading wire:target="refreshData" class="inline-block" style="color: #45a049 !important;">
                                <i class="fas fa-spinner fa-spin" style="color: #45a049 !important"></i>
                            </div>
                        </div>

                        {{-- Center: Avatar + Name --}}
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex: 1;">
                            @if ($employee)
                            <img src="{{ $employee->avatar_image }}"
                                alt="{{ $employee->name }}"
                                style="width: 90px; height: 90px; border-radius: 12px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.18);">
                            @endif
                            <span style="font-size: 13px; font-weight: bold;">{{ $employee?->name ?? __('lang.choose_branch') }}</span>
                        </div>

                        {{-- Right: Dates --}}
                        <div style="text-align: center; flex-shrink: 0; line-height: 1.8;">
                            <span style="font-weight: 600;">{{ __('lang.start_date') . ': ' . $start_date }}</span>
                            <br>
                            <span style="font-weight: 600;">{{ __('lang.end_date') . ': ' . $end_date }}</span>
                        </div>

                        {{-- Far Right: Logo --}}
                        <div style="flex-shrink: 0; text-align: center;">
                            <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                        </div>
                    </div>
                </th>
            </tr>
            <tr>
                <th rowspan="2" style="display: {{ $show_day ? 'table-cell' : 'none' }};">{{ __('lang.day') }}
                </th>
                <th rowspan="2">{{ __('lang.date') }}</th>
                <th colspan="2">{{ __('lang.shift_data') }}</th>
                <th colspan="4">{{ __('lang.checkin_checkout_data') }}</th>
                <th colspan="3">{{ __('lang.work_hours_summary') }}</th>
            </tr>
            <tr>
                <th class="internal_cell">{{ __('lang.from') }}</th>
                <th class="internal_cell">{{ __('lang.to') }}</th>
                <th class="internal_cell">{{ __('lang.check_in') }}</th>
                <th class="internal_cell">{{ __('lang.status') }}</th>
                <th class="internal_cell">{{ __('lang.check_out') }}</th>
                <th class="internal_cell">{{ __('lang.status') }}</th>
                <th class="internal_cell">{{ __('lang.supposed') }}</th>
                <th class="internal_cell">{{ __('lang.total_hours_worked') }}</th>
                <th class="internal_cell">{{ __('lang.approved') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($report_data as $date => $data)
            @php
            $isDate = false;
            try {
            \Carbon\Carbon::parse($date);
            $isDate = true;
            } catch (\Exception $e) {
            $isDate = false;
            }
            if (!$isDate) {
            continue;
            }
            $periods = $data['periods'] ?? [];
            $dayStatus = $data['day_status'] ?? null;

            @endphp

            @if ($dayStatus == 'leave')
            <tr>
                @if ($show_day)
                <td>{{ $data['day_name'] ?? ($data['day'] ?? '') }}</td>
                @endif
                <td>{{ $date }}</td>
                <td colspan="9" class="text-center text-gray-500 font-bold">
                    {{ $data['leave_type'] }}
                </td>
            </tr>
            @elseif (!is_null($dayStatus) && count($periods) > 0)
            {{-- @if (count($periods) > 0) --}}
            @foreach ($periods as $periodIndex => $period)
            @php
            // احصل على كل checkins الرقمية
            $checkIns = collect($period['attendances']['checkin'] ?? [])
            ->filter(fn($v, $k) => is_int($k))
            ->values()
            ->all();
            // أول دخول
            $firstCheckin = $checkIns[0]['check_time'] ?? '-';
            $firstCheckinStatus = $checkIns[0]['status'] ?? '-';
            $firstCheckinStatusLabel = $checkIns[0]['status_label'] ?? '-';

            // آخر خروج (باستخدام lastcheckout حصراً)
            $lastCheckout = $period['attendances']['checkout']['lastcheckout']['check_time'] ?? '-';
            $lastCheckoutStatus =
            $period['attendances']['checkout']['lastcheckout']['status'] ?? '-';
            $lastCheckoutStatusLabel = $period['attendances']['checkout']['lastcheckout']['status_label'] ?? '-';
            @endphp

            <tr>
                {{-- اليوم --}}
                @if ($periodIndex == 0)
                @if ($show_day)
                <td rowspan="{{ count($periods) }}">
                    {{ $data['day_name'] ?? ($data['day'] ?? '') }}
                </td>
                @endif
                <td rowspan="{{ count($periods) }}">
                    {{ $date }}
                </td>
                @endif

                {{-- بيانات الفترة --}}
                <td>
                    {{ $period['start_time'] ?? '-' }}
                </td>
                <td>
                    {{ $period['end_time'] ?? '-' }}
                </td>

                @if ($period['final_status'] == 'absent')
                <td colspan="8">
                    {{ __('lang.absent') }}
                </td>
                @elseif ($period['final_status'] == 'future')
                <td colspan="8">
                    {{ '-' }}
                </td>
                @elseif ($period['final_status'] == 'weekly_leave')
                <td colspan="8">
                    {{ __('lang.weekly_leave') }}
                </td>
                @else
                <td>
                    {{ $firstCheckin }}
                </td>

                <td>
                    {{ /*$firstCheckinStatus */
                    $firstCheckinStatusLabel
                    }}
                </td>

                <td>
                    {{ $lastCheckout }}
                </td>

                <td>

                    {{$lastCheckoutStatusLabel}}
                </td>

                <td>
                    {{ $period['attendances']['checkout']['lastcheckout']['supposed_duration_hourly'] ?? '-' }}

                </td>
                <td>
                    @php
                    $checkOuts = collect($period['attendances']['checkout'] ?? [])
                    ->filter(fn($v, $k) => is_int($k))
                    ->values()
                    ->all();

                    $result = \App\Services\HR\AttendanceHelpers\Reports\AttendanceDetailsCalculator::calculatePeriodDuration($checkIns, $checkOuts);
                    $duration = $result['formatted'];
                    @endphp
                    @if ($duration !== '-')
                    <button
                        class="text-blue-600 font-semibold hover:text-blue-900 transition flex items-center justify-between w-full"
                        wire:click="showDetails('{{ $date }}', {{ $employee_id }}, {{ $period['period_id'] }})"
                        style="cursor:pointer; border:none; background:none; padding:0;"
                        title="Show all check-in/out details">
                        <span class="underline">{{ $duration }}</span>
                        <span class="star-badge">&#9733;</span>
                    </button>
                    @else
                    <span>{{ $duration }}</span>
                    @endif
                </td>
                <td>
                    {{ $period['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? '-' }}
                </td>
                @endif
            </tr>
            @endforeach
            @else
            <tr>
                @if ($show_day)
                <td>{{ $data['day_name'] ?? ($data['day'] ?? '') }}</td>
                @endif
                <td>{{ $date }}</td>
                <td colspan="9" class="text-center text-gray-500 font-bold">
                    {{ __('lang.no_periods') }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="{{ $show_day ? 8 : 7 }}" class="text-center font-bold">{{ __('lang.total') }}</td>
                <td class="text-center">{{ $total_duration_hours }}</td>
                <td class="text-center">{{ $total_actual_duration_hours }}</td>
                <td class="text-center">{{ $total_approved_overtime }}</td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('lang.please_select_employee') }}</h1>
    </div>
    @endif

    {{-- نفس سكريبتات الطباعة والتصدير --}}
    <script>
        function printReport() {
            const printButton = document.querySelector('button[onclick="printReport()"]');
            if (printButton) printButton.style.display = 'none';
            window.print();
            if (printButton) printButton.style.display = 'block';
        }
    </script>

    <script>
        function exportToExcel() {
            // 1. جلب عنصر الجدول
            var elt = document.getElementById('report-table');

            // 2. استنساخ الجدول لتعديله قبل التصدير (اختياري، لتنظيف البيانات)
            // نقوم بعمل نسخة لكي لا نؤثر على العرض الحالي في الصفحة
            var clone = elt.cloneNode(true);

            // (خطوة اختيارية) إزالة الأزرار أو العناصر غير المرغوبة من النسخة قبل التصدير
            // مثلاً: إزالة زر التحديث من الهيدر إذا لم ترغب بظهور كلمة "Refresh" في الاكسل
            var buttons = clone.querySelectorAll('button');
            buttons.forEach(function(btn) {
                // يمكنك حذف الزر او استبداله بنصه فقط
                // btn.remove(); // هذا السطر يحذف الزر تماماً
            });

            // 3. تحويل الجدول (النسخة) إلى Sheet
            // الخيار {raw: true} يحاول الحفاظ على تنسيق الأرقام والنصوص كما هي
            var wb = XLSX.utils.table_to_sheet(clone, {
                raw: true
            });

            // 4. تحسين عرض الأعمدة (Auto fit columns) - اختياري لجمالية التقرير
            // نقوم بحساب عرض تقريبي بناءً على المحتوى
            var wscols = [];
            // يمكنك تحديد عرض ثابت للأعمدة إذا أردت، مثلاً 15 حرف
            // أو تركها افتراضية. الكود التالي يضع عرضاً افتراضياً:
            for (var i = 0; i < 20; i++) { // لعدد تقريبي من الأعمدة
                wscols.push({
                    wch: 15
                });
            }
            wb['!cols'] = wscols;

            // 5. إنشاء ملف العمل وحفظه
            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Attendance Report");
            XLSX.writeFile(workbook, "attendance_report.xlsx");
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        // إعداد Pusher
        var pusher = new Pusher('ff551e5dba18d083602f', {
            cluster: 'ap1'
        });

        // اشترك بالقناة الخاصة للموظف
        var channel = pusher.subscribe('attendance-report');

        // استمع للحدث
        channel.bind('attendance-updated', function(data) {
            console.log("📩 استلمت:", data);
            @this.refreshData();
        });
    </script>




    @if ($showDetailsModal)
    @include('components.hr.attendances-reports.attendance-details-modal', [
    'modalData' => $modalData,
    ])
    @endif

    @include('components.hr.attendances-reports.attendance-stats-chart-modal', [
    'chartData' => $chartData,
    ])
</x-filament-panels::page>