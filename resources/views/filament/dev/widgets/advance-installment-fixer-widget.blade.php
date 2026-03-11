<x-filament-widgets::widget>
    <x-filament::section>

        <x-slot name="heading">
            🏦 Advance Installment Fixer
        </x-slot>

        <x-slot name="description">
            Approved advance-request applications that have no installments generated yet.
        </x-slot>

        {{-- ── Actions ─────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-3 mb-5">
            <x-filament::button
                wire:click="refresh"
                color="gray"
                icon="heroicon-m-arrow-path">
                Refresh
            </x-filament::button>

            @if(count($pendingApplications) > 0)
            <x-filament::button
                wire:click="fixAll"
                wire:confirm="This will generate installments for {{ count($pendingApplications) }} application(s). Continue?"
                color="danger"
                icon="heroicon-m-wrench-screwdriver">
                Fix All ({{ count($pendingApplications) }})
            </x-filament::button>
            @endif
        </div>

        {{-- ── Results summary ─────────────────────────────────────────── --}}
        @if($fixedCount > 0 || $failedCount > 0)
        <div class="mb-4 flex gap-3 text-sm font-medium">
            @if($fixedCount > 0)
            <span class="inline-flex items-center gap-1 text-success-600 dark:text-success-400">
                <x-heroicon-m-check-circle class="w-4 h-4" />
                {{ $fixedCount }} fixed
            </span>
            @endif
            @if($failedCount > 0)
            <span class="inline-flex items-center gap-1 text-danger-600 dark:text-danger-400">
                <x-heroicon-m-x-circle class="w-4 h-4" />
                {{ $failedCount }} failed (see logs)
            </span>
            @endif
        </div>
        @endif

        {{-- ── Table ───────────────────────────────────────────────────── --}}
        @if(count($pendingApplications) === 0)
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-4">
            <x-heroicon-m-check-badge class="w-5 h-5 text-success-500" />
            All approved advance applications have installments. Nothing to fix.
        </div>
        @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">App ID</th>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Months</th>
                        <th class="px-4 py-3">Starts From</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($pendingApplications as $i => $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-mono font-semibold text-primary-600 dark:text-primary-400">
                            #{{ $row['id'] }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                            {{ $row['employee'] }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ number_format($row['amount'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $row['months'] }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $row['starts_from'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>