<div>
    {{-- زر التحذير مع العدّاد واللمبة --}}
    <x-filament::icon-button icon="heroicon-o-exclamation-triangle" label="Warnings" tooltip="Warning Notifications"
        size="sm" color="danger" x-on:click="$dispatch('open-modal', { id: 'warnings-modal' })" class="relative">


        @if (count($warnings ?? []))
            <span class="ml-1 text-xs font-bold text-danger-600">
                {{ count($warnings) }}11
            </span>
        @endif

        @if (count($warnings ?? []))
            <span
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full
                       bg-danger-600 text-white text-[10px] flex items-center justify-center font-bold">
                {{ count($warnings) }}
            </span>
        @endif

        {{-- لمبة التحذير --}}
        <span class="absolute -bottom-1 -left-1 flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-danger-600"></span>
        </span>
    </x-filament::icon-button>

    {{-- المودال --}}
    <x-filament::modal id="warnings-modal" width="lg" alignment="center">
        <x-slot name="heading">
            Warnings
        </x-slot>

        <x-slot name="description">
            آخر التنبيهات في النظام. حاول ما تتجاهلها مثل رسائل والدتك.
        </x-slot>

        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse ($warnings ?? [] as $warn)
                @php
                    $color =
                        $warn['level'] === 'critical'
                            ? 'text-red-600 dark:text-red-500'
                            : ($warn['level'] === 'warning'
                                ? 'text-amber-600 dark:text-amber-500'
                                : 'text-gray-500 dark:text-gray-400');
                    $dot =
                        $warn['level'] === 'critical'
                            ? 'bg-red-600'
                            : ($warn['level'] === 'warning'
                                ? 'bg-amber-500'
                                : 'bg-gray-400');
                @endphp

                <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 dark:border-white/10">
                    <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $dot }}"></div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                {{ $warn['title'] }}
                            </span>
                            <span class="text-[10px] text-gray-400">{{ $warn['time'] }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                            {{ $warn['detail'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-3 py-10 text-center text-sm text-gray-500">
                    لا توجد تحذيرات حالياً. تمثيل السلام النفسي مؤقتًا.
                </div>
            @endforelse
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <span class="text-[11px] text-gray-400">
                    Total: {{ count($warnings ?? []) }}
                </span>

                <div class="flex gap-2">
                    {{-- غلق المودال --}}
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'warnings-modal' })">
                        Close
                    </x-filament::button>

                    {{-- امسح الكل: عدّل href/onclick حسب منطقك --}}
                    <x-filament::button color="danger" tag="a" href="#">
                        Clear all
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </x-filament::modal>

</div>
