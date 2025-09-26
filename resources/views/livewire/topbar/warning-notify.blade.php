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
     <x-filament::modal id="warnings-modal" width="3xl" alignment="center">
         @php
             $items = $warnings ?? [];
             $total = count($items);
             $countCritical = collect($items)->where('level', 'critical')->count();
             $countWarning = collect($items)->where('level', 'warning')->count();
             $countInfo = collect($items)
                 ->whereNotIn('level', ['critical', 'warning'])
                 ->count();
         @endphp

         <div x-data="{ level: 'all' }" class="space-y-4">

             {{-- الهيدر --}}
             <x-filament::section class="!p-0">
                 <div class="flex items-start justify-between p-4">


                     <div class="hidden sm:flex items-center gap-2">
                         <x-filament::badge color="gray">Total: {{ $total }}</x-filament::badge>
                         <x-filament::badge color="danger">Critical: {{ $countCritical }}</x-filament::badge>
                         <x-filament::badge color="warning">Warning: {{ $countWarning }}</x-filament::badge>
                         <x-filament::badge>Info: {{ $countInfo }}</x-filament::badge>
                     </div>
                 </div>

                 {{-- فلاتر المستوى (أزرار Filament حقيقية) --}}
                 {{-- فلاتر المستوى --}}
                 <div class="px-4 pb-4 grid grid-cols-1 gap-2">
                     <x-filament::button size="sm" :color="null" :class="'border w-full'"
                         x-bind:class="level === 'all' ? 'fi-color-gray fi-btn-color-gray' : ''"
                         x-on:click="level='all'">
                         All ({{ $total }})
                     </x-filament::button>

                     <x-filament::button size="sm" color="danger" class="w-full"
                         x-bind:variant="level === 'critical' ? 'filled' : 'outlined'" x-on:click="level='critical'">
                         Critical ({{ $countCritical }})
                     </x-filament::button>

                     <x-filament::button size="sm" color="warning" class="w-full"
                         x-bind:variant="level === 'warning' ? 'filled' : 'outlined'" x-on:click="level='warning'">
                         Warning ({{ $countWarning }})
                     </x-filament::button>

                     <x-filament::button size="sm" :color="null" :class="'border w-full'"
                         x-bind:class="level === 'info' ? 'fi-color-gray fi-btn-color-gray' : ''"
                         x-on:click="level='info'">
                         Info ({{ $countInfo }})
                     </x-filament::button>
                 </div>

             </x-filament::section>

             {{-- القائمة --}}
             <div class="space-y-2 max-h-[65vh] overflow-y-auto">
                 @forelse ($items as $w)
                     @php
                         $lvl = $w['level'] ?? 'info';
                         $barColor =
                             $lvl === 'critical'
                                 ? 'bg-danger-500'
                                 : ($lvl === 'warning'
                                     ? 'bg-warning-500'
                                     : 'bg-gray-300');

                         $dotColor =
                             $lvl === 'critical'
                                 ? 'bg-danger-600'
                                 : ($lvl === 'warning'
                                     ? 'bg-warning-600'
                                     : 'bg-gray-400');

                         $badgeColor = $lvl === 'critical' ? 'danger' : ($lvl === 'warning' ? 'warning' : 'gray');
                     @endphp

                     <div x-show="level==='all' || level==='{{ $lvl }}'" x-transition.opacity
                         class="relative rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition overflow-hidden p-3 ps-4">

                         <span class="absolute inset-y-0 start-0 w-1 {{ $barColor }}"></span>

                         <x-filament::section class="p-3 ps-4" style="margin: 5px; width: 100%">
                             <div class="flex items-start justify-between gap-3">
                                 <div class="flex items-center gap-2">
                                     <span class="h-2.5 w-2.5 rounded-full {{ $dotColor }}"></span>
                                     <div class="font-medium text-sm">
                                         {{ $w['title'] ?? 'Untitled' }}
                                     </div>
                                     <x-filament::badge size="xs" :color="$badgeColor">
                                         {{ ucfirst($lvl) }}
                                     </x-filament::badge>
                                 </div>

                                 <div class="flex items-center gap-2">
                                     <span class="text-[11px] text-gray-500">{{ $w['time'] ?? '' }}</span>
                                     <x-filament::button size="xs" color="gray" outlined
                                         x-on:click="$el.closest('.relative').querySelector('[data-details]').classList.toggle('hidden')">
                                         Details
                                     </x-filament::button>
                                 </div>
                             </div>

                             @php
                                 // كان: $detail = $data['detail'];
                                 $detail = $w['detail'] ?? '';
                             @endphp

                             @if (is_array($detail))
                                 <div data-details class="hidden mt-2">
                                     <ul class="text-xs space-y-1">
                                         @foreach ($detail as $p)
                                             <li class="flex items-center justify-between gap-2">
                                                 <span class="truncate">
                                                     {{ $p['name'] ?? '#' . ($p['id'] ?? '?') }}
                                                 </span>
                                                 <span class="shrink-0 tabular-nums">
                                                     {{ $p['remaining'] ?? 0 }} / {{ $p['min'] ?? 0 }}
                                                 </span>
                                             </li>
                                         @endforeach
                                     </ul>
                                 </div>
                             @else
                                 <p data-details class="hidden mt-2 text-xs text-gray-600 dark:text-gray-300">
                                     {{ $detail }}
                                 </p>
                             @endif


                         </x-filament::section>
                     </div>
                 @empty
                     <x-filament::card class="text-center py-10">
                         <x-filament::icon icon="heroicon-o-bell-slash" class="h-8 w-8 text-gray-400 mx-auto" />
                         <div class="mt-2 text-sm font-medium">No Warnings</div>
                         <div class="text-xs text-gray-500">هدوء قبل العاصفة… اغتنمه.</div>
                     </x-filament::card>
                 @endforelse
             </div>

             {{-- الفوتر --}}
             <div class="flex items-center justify-between">
                 <div class="text-[11px] text-gray-500">Total: {{ $total }}</div>
                 <div class="flex gap-2">
                     <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'warnings-modal' })">
                         Close
                     </x-filament::button>
                     <x-filament::button color="gray" outlined>
                         Mark all as read
                     </x-filament::button>
                     <x-filament::button color="danger">
                         Clear all
                     </x-filament::button>
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
