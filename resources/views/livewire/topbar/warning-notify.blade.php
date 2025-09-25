<div>
    {{-- زر التحذير مع العدّاد واللمبة --}}
    {{-- زر التحذير مع العدّاد واللمبة --}}
    <x-filament::icon-button icon="heroicon-o-exclamation-triangle" tooltip="Warning Notifications" size="lg"
        color="danger" x-on:click="$dispatch('open-modal', { id: 'warnings-modal' })" class="relative" label="">

        @if (count($warnings ?? []))
            <span
                class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs font-bold text-danger-600">
                {{ count($warnings ?? 0) }}
            </span>

            <span
                class="pointer-events-none absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full
                   bg-danger-600 text-white text-[10px] flex items-center justify-center font-bold">
                {{ count($warnings ?? []) }}
            </span>

            <span class="pointer-events-none absolute -bottom-1 -left-1 flex h-3 w-3">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-danger-600"></span>
            </span>
        @endif
    </x-filament::icon-button>



    {{-- المودال --}}
    <x-filament::modal id="warnings-modal" width="2xl" alignment="center">
        <div x-data="{ level: 'all' }" class="relative">

            {{-- Header: لاصق مع أيقونة وشريط معلومات --}}
            <div
                class="sticky top-0 z-10 -mx-6 px-6 pt-5 pb-4 bg-white/80 dark:bg-gray-900/70 backdrop-blur border-b border-gray-100 dark:border-white/10">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-9 w-9 rounded-2xl bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center shadow-sm">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-base font-semibold text-gray-900 dark:text-gray-100">Warnings</div>
                            {{-- <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                آخر التنبيهات في النظام. حاول ما تتجاهلها مثل رسائل والدتك.
                            </div> --}}
                        </div>
                    </div>

                    {{-- Counters صغيرة --}}
                    <div class="hidden sm:flex items-center gap-2 text-[11px]">
                        <span
                            class="px-2 py-1 rounded-full bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300">
                            Total: {{ count($warnings ?? []) }}
                        </span>
                    </div>
                </div>

                {{-- فلاتر المستوى --}}
                @php
                    $countCritical = collect($warnings ?? [])
                        ->where('level', 'critical')
                        ->count();
                    $countWarning = collect($warnings ?? [])
                        ->where('level', 'warning')
                        ->count();
                    $countInfo = collect($warnings ?? [])
                        ->whereNotIn('level', ['critical', 'warning'])
                        ->count();
                @endphp

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button"
                        class="px-3 py-1.5 text-xs rounded-full border border-gray-200 dark:border-white/10"
                        :class="level === 'all' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' :
                            'text-gray-600 dark:text-gray-300'"
                        @click="level = 'all'">
                        All <span class="ms-2 text-[10px] opacity-70">{{ count($warnings ?? []) }}</span>
                    </button>

                    <button type="button"
                        class="px-3 py-1.5 text-xs rounded-full border border-red-200/60 dark:border-red-900/40"
                        :class="level === 'critical' ? 'bg-red-600 text-white' : 'text-red-600 dark:text-red-400'"
                        @click="level = 'critical'">
                        Critical <span class="ms-2 text-[10px] opacity-70">{{ $countCritical }}</span>
                    </button>

                    <button type="button"
                        class="px-3 py-1.5 text-xs rounded-full border border-amber-200/60 dark:border-amber-900/40"
                        :class="level === 'warning' ? 'bg-amber-500 text-white' : 'text-amber-600 dark:text-amber-400'"
                        @click="level = 'warning'">
                        Warning <span class="ms-2 text-[10px] opacity-70">{{ $countWarning }}</span>
                    </button>

                    <button type="button"
                        class="px-3 py-1.5 text-xs rounded-full border border-gray-200 dark:border-white/10"
                        :class="level === 'info' ? 'bg-gray-800 text-white' : 'text-gray-600 dark:text-gray-300'"
                        @click="level = 'info'">
                        Info <span class="ms-2 text-[10px] opacity-70">{{ $countInfo }}</span>
                    </button>
                </div>
            </div>

            {{-- Body: قائمة البطاقات --}}
            <div class="space-y-2 max-h-[60vh] overflow-y-auto py-4">
                @forelse ($warnings ?? [] as $warn)
                    @php
                        $isCritical = $warn['level'] === 'critical';
                        $isWarning = $warn['level'] === 'warning';
                        $dot = $isCritical ? 'bg-red-600' : ($isWarning ? 'bg-amber-500' : 'bg-gray-400');
                        $chip = $isCritical
                            ? 'text-red-700 bg-red-50 border-red-200 dark:text-red-300 dark:bg-red-950/30 dark:border-red-900/40'
                            : ($isWarning
                                ? 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-300 dark:bg-amber-950/30 dark:border-amber-900/40'
                                : 'text-gray-600 bg-gray-50 border-gray-200 dark:text-gray-300 dark:bg-white/5 dark:border-white/10');
                        $levelForX = $isCritical ? 'critical' : ($isWarning ? 'warning' : 'info');
                    @endphp

                    <div x-show="level === 'all' || level === '{{ $levelForX }}'" x-transition
                        class="group relative p-3 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-gray-900/60 hover:bg-gray-50 dark:hover:bg-gray-900/80 shadow-sm">
                        {{-- خط جانبي بسيط حسب المستوى --}}
                        <span
                            class="absolute inset-y-0 start-0 w-1 rounded-s-xl
                        {{ $isCritical ? 'bg-red-500' : ($isWarning ? 'bg-amber-400' : 'bg-gray-300') }}">
                        </span>

                        <div class="ps-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                        {{ $warn['title'] }}
                                    </span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $chip }}">
                                        {{ ucfirst($warn['level'] ?? 'info') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-400">{{ $warn['time'] }}</span>
                                    {{-- زر تفاصيل اختياري: يوسع الوصف لو طويل --}}
                                    <button type="button"
                                        class="opacity-0 group-hover:opacity-100 transition text-[11px] px-2 py-1 rounded-md border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300">
                                        Details
                                    </button>
                                </div>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-5">
                                {{ $warn['detail'] }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div
                            class="mx-auto h-12 w-12 rounded-2xl bg-gray-100 dark:bg-white/10 flex items-center justify-center">
                            <x-filament::icon icon="heroicon-o-bell-slash" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="mt-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                            No Warnings </div>
                      
                    </div>
                @endforelse
            </div>

            {{-- Footer: لاصق مع إجراءات سريعة --}}
            <div
                class="sticky bottom-0 -mx-6 px-6 py-3 bg-white/80 dark:bg-gray-900/70 backdrop-blur border-t border-gray-100 dark:border-white/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <span>Selected: 0</span> {{-- لو بتضيف تحديد لاحقًا --}}
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button color="gray"
                            x-on:click="$dispatch('close-modal', { id: 'warnings-modal' })">
                            Close
                        </x-filament::button>
                        <x-filament::button color="gray" tag="a" href="#">
                            Mark all as read
                        </x-filament::button>
                        <x-filament::button color="danger" tag="a" href="#">
                            Clear all
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::modal>

    <style>
        svg {
            /* height: 180px;
            width: 180px; */
        }
    </style>
</div>
