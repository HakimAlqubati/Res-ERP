@php
    $records = isset($this) && method_exists($this, 'getTableRecords') ? $this->getTableRecords() : null;
    $recordsMap = [];

    if ($records) {
        foreach ($records as $record) {
            $key = isset($this) && method_exists($this, 'getTableRecordKey')
                ? (string) $this->getTableRecordKey($record)
                : (string) $record->getKey();

            $recordsMap[$key] = [
                'advance_amount' => (float) ($record->advance_amount ?? 0),
                'monthly_deduction_amount' => (float) ($record->monthly_deduction_amount ?? 0),
                'paid_amount' => (float) ($record->paid_amount ?? 0),
                'remaining_total' => (float) ($record->remaining_total ?? 0),
            ];
        }
    }

    $filteredQuery = isset($this) && method_exists($this, 'getFilteredTableQuery')
        ? $this->getFilteredTableQuery()
        : null;

    $totalsRow = $filteredQuery
        ? (clone $filteredQuery)
            ->reorder()
            ->selectRaw('
                COALESCE(SUM(advance_amount), 0) as advance_amount,
                COALESCE(SUM(monthly_deduction_amount), 0) as monthly_deduction_amount,
                COALESCE(SUM(COALESCE(advance_amount, 0) - COALESCE(remaining_total, 0)), 0) as paid_amount,
                COALESCE(SUM(remaining_total), 0) as remaining_total
            ')
            ->toBase()
            ->first()
        : null;

    $allTotals = [
        'advance_amount' => (float) ($totalsRow->advance_amount ?? 0),
        'monthly_deduction_amount' => (float) ($totalsRow->monthly_deduction_amount ?? 0),
        'paid_amount' => (float) ($totalsRow->paid_amount ?? 0),
        'remaining_total' => (float) ($totalsRow->remaining_total ?? 0),
    ];

    $currency = function_exists('settingWithDefault') ? settingWithDefault('currency_symbol', 'RM') : 'RM';
@endphp

<div
    wire:key="{{ (isset($this) ? $this->getId() : 'employee-advance-report') }}.total-amounts-header.{{ count($recordsMap) }}"
    x-cloak
    x-show="getSelectedRecordsCount()"
    x-data="{
        recordsMap: @js($recordsMap),
        allTotals: @js($allTotals),
        currency: @js($currency),
        tick: 0,
        formatMoney(amount) {
            return this.currency + ' ' + (Number(amount) || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        isColumnVisible(name, defaultVisible = true) {
            const cols = this.$wire?.tableColumns;
            if (Array.isArray(cols) && cols.length) {
                const found = cols.find(c => c.name === name);
                if (found !== undefined) {
                    return Boolean(found.isToggled) && !Boolean(found.isHidden);
                }
            }
            return defaultVisible;
        },
        selectedTotal(field) {
            const _t = this.tick;
            const count = this.getSelectedRecordsCount();
            if (!count) return 0;

            if (this.isTrackingDeselectedRecords) {
                let deselectedSum = 0;
                this.deselectedRecords.forEach(key => {
                    deselectedSum += Number(this.recordsMap[key]?.[field] || 0);
                });
                return Math.max(0, Number(this.allTotals[field] || 0) - deselectedSum);
            }

            let total = 0;
            const records = Array.isArray(this.entangledSelectedRecords)
                ? this.entangledSelectedRecords
                : Array.from(this.selectedRecords || []);

            records.forEach(key => {
                total += Number(this.recordsMap[key]?.[field] || 0);
            });

            return total;
        }
    }"
    @change.window="if ($event.target.matches('.fi-ta-record-checkbox, .fi-checkbox-input')) { tick++ }"
    class="flex flex-wrap items-center gap-2"
>
    <div
        x-show="isColumnVisible('advance_amount', false)"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold shadow-xs transition-all duration-200"
    >
        <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">{{ __('lang.advance_amount') }}:</span>
        <span class="font-bold tracking-tight text-emerald-700 dark:text-emerald-300 whitespace-nowrap" x-text="formatMoney(selectedTotal('advance_amount'))"></span>
    </div>

    <div
        x-show="isColumnVisible('monthly_deduction_amount', true)"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold shadow-xs transition-all duration-200"
    >
        <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">{{ __('lang.monthly_deduction') }}:</span>
        <span class="font-bold tracking-tight text-emerald-700 dark:text-emerald-300 whitespace-nowrap" x-text="formatMoney(selectedTotal('monthly_deduction_amount'))"></span>
    </div>

    <div
        x-show="isColumnVisible('paid_amount', false)"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold shadow-xs transition-all duration-200"
    >
        <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">Paid Amount:</span>
        <span class="font-bold tracking-tight text-emerald-700 dark:text-emerald-300 whitespace-nowrap" x-text="formatMoney(selectedTotal('paid_amount'))"></span>
    </div>

    <div
        x-show="isColumnVisible('remaining_total', true)"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold shadow-xs transition-all duration-200"
    >
        <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">{{ __('lang.remaining') }}:</span>
        <span class="font-bold tracking-tight text-emerald-700 dark:text-emerald-300 whitespace-nowrap" x-text="formatMoney(selectedTotal('remaining_total'))"></span>
    </div>
</div>
