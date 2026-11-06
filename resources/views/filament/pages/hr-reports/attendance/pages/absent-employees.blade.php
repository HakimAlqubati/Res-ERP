<x-filament-panels::page>
    <style>
        .pagination-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 18px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-top: 16px;
            font-size: 13px;
        }

        .dark .pagination-wrapper {
            background-color: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .pagination-info {
            color: #4b5563;
            font-size: 13px;
        }

        .dark .pagination-info {
            color: #9ca3af;
        }

        .pagination-info-num {
            font-weight: 700;
            color: #111827;
        }

        .dark .pagination-info-num {
            color: #ffffff;
        }

        .pagination-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pagination-per-page {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-weight: 500;
        }

        .dark .pagination-per-page {
            color: #9ca3af;
        }

        .pagination-select {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #111827;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .pagination-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .dark .pagination-select {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        .pagination-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pagination-btn:hover:not(:disabled) {
            background-color: #f3f4f6;
            border-color: #9ca3af;
            color: #111827;
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .dark .pagination-btn {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }

        .dark .pagination-btn:hover:not(:disabled) {
            background-color: #4b5563;
            color: #ffffff;
        }

        .pagination-badge {
            padding: 6px 14px;
            border-radius: 8px;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }

        .dark .pagination-badge {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }

        @media print {
            .pagination-wrapper, .no-print {
                display: none !important;
            }
        }
    </style>

    {{-- عرض نموذج الفلترة --}}
    {{ $this->getTableFiltersForm() }}

    {{-- التحقق من وجود بيانات الفرع --}}
    @if (!empty($branch_id))
        @php
            $flattenedRows = [];
            $rowNumber = 1;
            if (!empty($report_data)) {
                foreach ($report_data as $item) {
                    $employeeName = $item['employee_name'] ?? 'N/A';
                    $absences = $item['absences'] ?? [];

                    foreach ($absences as $dayData) {
                        $date = $dayData['date'] ?? '-';
                        $periods = $dayData['periods'] ?? [];

                        foreach ($periods as $period) {
                            $flattenedRows[] = [
                                'no' => $rowNumber++,
                                'employee_name' => $employeeName,
                                'date' => $date,
                                'start_time' => $period['start_time'] ?? '-',
                                'end_time' => $period['end_time'] ?? '-',
                            ];
                        }
                    }
                }
            }
        @endphp

        <div x-data="{
            rows: @js($flattenedRows),
            page: 1,
            perPage: 25,
            init() {
                this.$watch('rows', () => { this.page = 1; });
            },
            get totalPages() {
                if (this.perPage === 'all') return 1;
                return Math.ceil(this.rows.length / parseInt(this.perPage)) || 1;
            },
            get paginatedRows() {
                if (this.perPage === 'all') return this.rows;
                const per = parseInt(this.perPage);
                const start = (this.page - 1) * per;
                return this.rows.slice(start, start + per);
            },
            get startRecord() {
                if (this.rows.length === 0) return 0;
                if (this.perPage === 'all') return 1;
                return (this.page - 1) * parseInt(this.perPage) + 1;
            },
            get endRecord() {
                if (this.perPage === 'all') return this.rows.length;
                return Math.min(this.page * parseInt(this.perPage), this.rows.length);
            },
            nextPage() {
                if (this.page < this.totalPages) this.page++;
            },
            prevPage() {
                if (this.page > 1) this.page--;
            }
        }">

            <table class="w-full text-sm text-left pretty reports">
                <thead class="fixed-header" style="top:64px;">
                    <tr class="header_report">
                        <th colspan="2" class="no_border_right_left">
                            <p>{{ __('Employee Absence Report') }}</p>
                        </th>
                        <th colspan="3" class="no_border_right_left">
                            <p>
                                @if($date_from === $date_to)
                                    {{ __('Date: ') . $date_from }}
                                @else
                                    {{ __('Date From: ') . $date_from }} | {{ __('Date To: ') . $date_to }}
                                @endif
                            </p>
                        </th>
                    </tr>
                    <tr>
                        <th>{{ __('No.') }}</th>
                        <th>{{ __('Employee Name') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Start Time') }}</th>
                        <th>{{ __('End Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="rows.length > 0">
                        <template x-for="row in paginatedRows" :key="row.no">
                            <tr>
                                <td x-text="row.no"></td>
                                <td x-text="row.employee_name"></td>
                                <td x-text="row.date"></td>
                                <td x-text="row.start_time"></td>
                                <td x-text="row.end_time"></td>
                            </tr>
                        </template>
                    </template>
                    <template x-if="rows.length === 0">
                        <tr>
                            <td colspan="5" style="text-align: center;">
                                {{ __('No employees absent for this date range.') }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- Pagination Controls --}}
            <template x-if="rows.length > 0">
                <div class="pagination-wrapper no-print">
                    <div class="pagination-info">
                        {{ __('Showing') }} <span class="pagination-info-num" x-text="startRecord"></span>
                        {{ __('to') }} <span class="pagination-info-num" x-text="endRecord"></span>
                        {{ __('of') }} <span class="pagination-info-num" x-text="rows.length"></span>
                        {{ __('results') }}
                    </div>

                    <div class="pagination-actions">
                        <div class="pagination-per-page">
                            <label for="reportPerPageSelect">{{ __('Per page:') }}</label>
                            <select id="reportPerPageSelect" x-model="perPage" @change="page = 1" class="pagination-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all">{{ __('All') }}</option>
                            </select>
                        </div>

                        <template x-if="perPage !== 'all' && totalPages > 1">
                            <div class="pagination-nav">
                                <button type="button" @click="prevPage()" :disabled="page === 1" class="pagination-btn">
                                    ‹ {{ __('Previous') }}
                                </button>

                                <span class="pagination-badge">
                                    <span x-text="page"></span> / <span x-text="totalPages"></span>
                                </span>

                                <button type="button" @click="nextPage()" :disabled="page === totalPages" class="pagination-btn">
                                    {{ __('Next') }} ›
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{ __('Please select a Branch') }}</h1>
        </div>
    @endif
</x-filament-panels::page>