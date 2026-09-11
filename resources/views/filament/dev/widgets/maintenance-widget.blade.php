<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Maintenance
        </x-slot>

        <div class="flex flex-wrap gap-4">
            <x-filament::button wire:click="optimize" color="warning" icon="heroicon-m-sparkles">
                Optimize:Clear
            </x-filament::button>

            <x-filament::button wire:click="clearView" color="gray" icon="heroicon-m-trash">
                Clear Views
            </x-filament::button>

            <x-filament::button wire:click="clearConfig" color="gray" icon="heroicon-m-cog">
                Clear Config
            </x-filament::button>

            <x-filament::button wire:click="openMaskConfirmation" color="danger" icon="heroicon-m-envelope">
                Mask Employee Emails
            </x-filament::button>

            <x-filament::button wire:click="openSalaryConfirmation" color="warning" icon="heroicon-m-currency-dollar">
                Randomize Salaries
            </x-filament::button>

            <x-filament::button wire:click="updateProductPrices" color="success" icon="heroicon-m-tag">
                Update 311 Product Prices
            </x-filament::button>

        </div>
    </x-filament::section>

    {{-- ─── Mask Employee Emails — Alpine.js Modal ───────────────────────── --}}
    <div
        x-data="{ open: $wire.entangle('showMaskModal') }"
        x-show="open"
        x-cloak
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="$wire.call('cancelMask')"
        ></div>

        {{-- Modal Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 w-full max-w-lg mx-4 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 overflow-hidden"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-envelope" class="h-5 w-5 text-danger-500" />
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Mask Employee Emails
                    </h2>
                </div>
                <button
                    type="button"
                    wire:click="cancelMask"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5">
                {{-- Warning note --}}
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    This will append <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-danger-600 dark:text-danger-400 font-mono text-xs">@test.com</code>
                    to <strong>all employee emails</strong> except the protected list.
                    This action <strong>cannot be undone</strong>.
                </p>

                {{-- Puzzle challenge --}}
                <div class="rounded-xl border border-warning-300 bg-warning-50 dark:bg-warning-900/20 dark:border-warning-700 p-4 text-center space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-400">
                        🧩 Security Puzzle — solve to confirm
                    </p>
                    <p class="text-3xl font-bold tabular-nums text-warning-900 dark:text-warning-100">
                        {{ $puzzleA }} + {{ $puzzleB }} = ?
                    </p>
                </div>

                {{-- Answer input --}}
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="number"
                            wire:model.live="puzzleAnswer"
                            placeholder="Enter your answer…"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <x-filament::button wire:click="cancelMask" color="gray" icon="heroicon-m-x-circle">
                    Cancel
                </x-filament::button>

                <x-filament::button
                    wire:click="confirmMaskEmployeeEmails"
                    color="danger"
                    icon="heroicon-m-check-circle"
                >
                    Confirm &amp; Mask Emails
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- ─── Randomize Employee Salaries — Alpine.js Modal ──────────────────── --}}
    <div
        x-data="{ open: $wire.entangle('showSalaryModal') }"
        x-show="open"
        x-cloak
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="$wire.call('cancelSalary')"
        ></div>

        {{-- Modal Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 w-full max-w-lg mx-4 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 overflow-hidden"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-currency-dollar" class="h-5 w-5 text-warning-500" />
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Randomize Employee Salaries
                    </h2>
                </div>
                <button
                    type="button"
                    wire:click="cancelSalary"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5">
                {{-- Warning note --}}
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    This will assign a <strong>random salary between 1,000 and 2,999</strong> to every employee.
                    This action <strong>cannot be undone</strong>.
                </p>

                {{-- Puzzle challenge --}}
                <div class="rounded-xl border border-warning-300 bg-warning-50 dark:bg-warning-900/20 dark:border-warning-700 p-4 text-center space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-400">
                        🧩 Security Puzzle — solve to confirm
                    </p>
                    <p class="text-3xl font-bold tabular-nums text-warning-900 dark:text-warning-100">
                        {{ $salaryPuzzleA }} + {{ $salaryPuzzleB }} = ?
                    </p>
                </div>

                {{-- Answer input --}}
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="number"
                            wire:model.live="salaryPuzzleAnswer"
                            placeholder="Enter your answer…"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <x-filament::button wire:click="cancelSalary" color="gray" icon="heroicon-m-x-circle">
                    Cancel
                </x-filament::button>

                <x-filament::button
                    wire:click="confirmRandomizeSalaries"
                    color="warning"
                    icon="heroicon-m-check-circle"
                >
                    Confirm &amp; Randomize
                </x-filament::button>
            </div>
        </div>
    </div>

</x-filament-widgets::widget>