<x-filament-panels::page>
    {{$this->form}}
    <x-filament::button wire:click="calculate" color="primary" size="lg" icon="heroicon-o-calculator">
        Calculate
    </x-filament::button>
    @if($this->result)
    <div style="margin-top: 2rem;">
        <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 2rem; color: var(--fi-color-gray-900, inherit);" class="dark:text-white">Results</h2>

        <!-- Row 1: 3 Columns -->
        <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 3rem;">

            {{-- worked_days --}}
            <div style="flex: 1 1 250px; background-color: rgba(var(--gray-900), var(--tw-bg-opacity, 1)); @if(auth()->user()->theme !== 'dark') background-color: white; @endif border-radius: 1rem; padding: 2rem; border: 1px solid rgba(var(--gray-200), var(--tw-border-opacity, 1)); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-gray-500 dark:text-gray-400">Worked Days</div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 3.5rem; font-weight: 900;" class="text-primary-600 dark:text-primary-400">{{ $this->result['analysis']['worked_days'] }}</span>
                    <span style="font-size: 1.125rem; font-weight: 700;" class="text-gray-500 dark:text-gray-400">Days</span>
                </div>
            </div>

            {{-- earned_leave_days --}}
            <div style="flex: 1 1 250px; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-gray-500 dark:text-gray-400">Earned Leave</div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 3.5rem; font-weight: 900;" class="text-primary-600 dark:text-primary-400">{{ $this->result['analysis']['earned_leave_days'] }}</span>
                    <span style="font-size: 1.125rem; font-weight: 700;" class="text-gray-500 dark:text-gray-400">Days</span>
                </div>
            </div>

            {{-- payable_days --}}
            <div style="flex: 1 1 250px; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-gray-500 dark:text-gray-400">Payable Days</div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 3.5rem; font-weight: 900;" class="text-success-600 dark:text-success-400">{{ $this->result['result']['payable_days'] }}</span>
                    <span style="font-size: 1.125rem; font-weight: 700;" class="text-gray-500 dark:text-gray-400">Days</span>
                </div>
            </div>
        </div>

        <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem;" class="text-gray-900 dark:text-white">Deduction Details</h3>

        <!-- Row 2: 4 Columns -->
        <div style="display: flex; flex-wrap: wrap; gap: 1.5rem;">

            {{-- overtime_days --}}
            <div style="flex: 1 1 200px; border-radius: 1rem; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem;" class="text-gray-500 dark:text-gray-400">Overtime (Days)</div>
                <div style="font-size: 2.25rem; font-weight: 800;" class="text-gray-900 dark:text-white">{{ $this->result['result']['overtime_days'] }}</div>
            </div>

            {{-- leave_penalty --}}
            <div style="flex: 1 1 200px; border-radius: 1rem; padding: 2rem; border-left: 6px solid #f59e0b; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem;" class="text-gray-500 dark:text-gray-400">Leave Penalty</div>
                <div style="font-size: 2.25rem; font-weight: 800; color: #f59e0b;">{{ $this->result['result']['leave_penalty'] }}</div>
            </div>

            {{-- final_absent_penalty --}}
            <div style="flex: 1 1 200px; border-radius: 1rem; padding: 2rem; border-left: 6px solid #ef4444; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                <div style="font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem;" class="text-gray-500 dark:text-gray-400">Absent Penalty</div>
                <div style="font-size: 2.25rem; font-weight: 800; color: #ef4444;">{{ $this->result['result']['final_absent_penalty'] }}</div>
            </div>

            {{-- total_deduction_days --}}
            <div style="flex: 1 1 200px; border-radius: 1rem; padding: 2rem; border-left: 6px solid #dc2626; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1), 0 2px 4px -1px rgba(239, 68, 68, 0.06);" class="bg-danger-50 border border-danger-200 dark:bg-red-900/10 dark:border-red-900/30">
                <div style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem; color: #ef4444;">Total Deduction</div>
                <div style="font-size: 3rem; font-weight: 900; color: #dc2626; line-height: 1;">{{ $this->result['result']['total_deduction_days'] ?? 0 }}</div>
            </div>

        </div>
    </div>
    @endif
</x-filament-panels::page>