@php
    $records = isset($this) && method_exists($this, 'getTableRecords') ? $this->getTableRecords() : null;
    $payrollNetMap = $records ? $records->pluck('total_net', 'id')->toArray() : [];
    $allTotal = (float) (isset($this) && method_exists($this, 'getFilteredTableQuery') ? ($this->getFilteredTableQuery()?->sum('total_net') ?? 0) : 0);
    $currency = function_exists('settingWithDefault') ? settingWithDefault('currency_symbol', 'RM') : 'RM';
@endphp

<div
    wire:key="{{ (isset($this) ? $this->getId() : 'payroll') }}.total-net-header.{{ count($payrollNetMap) }}"
    x-cloak
    x-show="getSelectedRecordsCount()"
    x-data="{
        netMap: @js($payrollNetMap),
        allTotal: {{ $allTotal }},
        currency: @js($currency),
        tick: 0,
        formatMoney(amount) {
            return this.currency + ' ' + (Number(amount) || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        get selectedTotalNet() {
            // Reference tick and getSelectedRecordsCount to guarantee Alpine reactivity
            const _t = this.tick;
            const count = this.getSelectedRecordsCount();
            if (!count) return 0;

            if (this.isTrackingDeselectedRecords) {
                let deselectedSum = 0;
                this.deselectedRecords.forEach(key => {
                    deselectedSum += Number(this.netMap[key] || 0);
                });
                return Math.max(0, this.allTotal - deselectedSum);
            }

            let total = 0;
            const records = Array.isArray(this.entangledSelectedRecords)
                ? this.entangledSelectedRecords
                : Array.from(this.selectedRecords || []);

            records.forEach(key => {
                total += Number(this.netMap[key] || 0);
            });

            return total;
        }
    }"
    @change.window="if ($event.target.matches('.fi-ta-record-checkbox, .fi-checkbox-input')) { tick++ }"
    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold shadow-xs transition-all duration-200"
>
   
    <span class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">Total Net:</span>
    <span class="font-bold tracking-tight text-emerald-700 dark:text-emerald-300 whitespace-nowrap" x-text="formatMoney(selectedTotalNet)"></span>
</div>
