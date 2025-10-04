 <div>
     {{-- زر التحذير مع العدّاد واللمبة --}}
     {{-- زر التحذير مع العدّاد واللمبة --}}
     @php
         $count = isset($warnings) ? count($warnings) : 0;
         $badge = $count > 99 ? '99+' : ($count ?: '');
     @endphp

     <div class="warn-badge inline-block" data-count="{{ $badge }}">
         <x-filament::icon-button icon="heroicon-o-exclamation-triangle" tooltip="Warning Notifications" size="lg"
             color="danger" x-on:click="$dispatch('open-modal', { id: 'warnings-modal' })" class="warning-flash"
             label="" />
     </div>




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
                                     {{-- {{ dd($w) }} --}}
                                     <span class="text-[11px] text-gray-500">{{ $w['time'] ?? '' }}</span>
                                     @if (!empty($w['link']))
                                         {{-- <x-filament::button size="xs" color="primary" tag="a"
                                                :href="$w['link']" target="_blank">
                                                Open
                                            </x-filament::button> --}}
                                     @endif


                                 </div>
                             </div>
                             @php
                                 $detail = $w['detail'] ?? '';
                                 $link = $w['link'] ?? null;
                             @endphp

                             @if (is_array($detail))
                                 <div data-details class="hidden mt-2">
                                     @if ($link)
                                         <a href="{{ $link }}" target="_blank" rel="noopener"
                                             class="wb-desc-link block" title="Open">
                                     @endif

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

                                     @if ($link)
                                         </a>
                                     @endif
                                 </div>
                             @else
                                 @if ($link)
                                     <a data-details href="{{ $link }}" target="_blank" rel="noopener"
                                         class="hidden mt-2 text-xs text-gray-600 dark:text-gray-300 wb-desc-link"
                                         title="Open">
                                         {{ $detail }}
                                     </a>
                                 @else
                                     <p data-details class="hidden mt-2 text-xs text-gray-600 dark:text-gray-300">
                                         {{ $detail }}
                                     </p>
                                 @endif
                             @endif



                         </x-filament::section>
                     </div>
                 @empty
                     <x-filament::card class="text-center py-10">
                         <x-filament::icon icon="heroicon-o-bell-slash" class="h-8 w-8 text-gray-400 mx-auto" />
                         <div class="mt-2 text-sm font-medium">No Warnings</div>
                         {{-- <div class="text-xs text-gray-500">هدوء قبل العاصفة… اغتنمه.</div> --}}
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
                     {{-- <x-filament::button color="gray" outlined>
                         Mark all as read
                     </x-filament::button>
                     <x-filament::button color="danger">
                         Clear all
                     </x-filament::button> --}}
                 </div>
             </div>

         </div>
     </x-filament::modal>


     <style>
         /* وميض للمثلث فقط (الأيقونة) */

         /* حاوية لضبط تموضع البادج */
         .warn-badge {
             position: relative;
         }

         /* البادج الاحترافي عبر ::after */
         .warn-badge::after {
             content: attr(data-count);
             position: absolute;
             top: -6px;
             /* اضبطها حسب حجم الزر */
             right: -6px;
             /* اضبطها حسب حجم الزر */
             min-width: 20px;
             height: 20px;
             padding: 0 6px;
             /* يتكيّف مع خانتين/ثلاث */
             display: grid;
             place-items: center;
             border-radius: 999px;
             font-weight: 700;
             font-size: 11px;
             line-height: 1;
             color: #fff;

             /* تدرّج وعمق للّون */
             background: linear-gradient(180deg, #ef4444, #dc2626);
             box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
             border: 2px solid #fff;
             /* حلقة بيضاء أنيقة حول البادج */

             /* لمعان زجاجي خفيف */
             -webkit-backdrop-filter: saturate(140%) blur(2px);
             backdrop-filter: saturate(140%) blur(2px);

             /* نُخفيه تلقائياً إن ما فيه رقم */
             opacity: 1;
         }

         /* إخفاء البادج إذا لا يوجد رقم */
         .warn-badge[data-count=""],
         .warn-badge:not([data-count]) {}

         .warn-badge[data-count=""]::after,
         .warn-badge:not([data-count])::after {
             display: none;
         }

         /* لو تبغى نَبض خفيف للبادج نفسه (اختياري) */
         /*
@keyframes badge-pop {
  0%, 100% { transform: translate(0,0) scale(1); }
  50% { transform: translate(0,-1px) scale(1.05); }
}
.warn-badge::after { animation: badge-pop 1.8s ease-in-out infinite; }
*/
         @keyframes badge-glow {

             0%,
             100% {
                 box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.0);
             }

             50% {
                 box-shadow:
                     0 0 10px 4px rgba(220, 38, 38, 0.7),
                     0 0 20px 8px rgba(220, 38, 38, 0.4);
             }
         }

         .warn-badge::after {
             content: attr(data-count);
             position: absolute;
             top: -6px;
             right: -6px;
             min-width: 20px;
             height: 20px;
             padding: 0 6px;
             display: grid;
             place-items: center;
             border-radius: 999px;
             font-weight: 700;
             font-size: 11px;
             line-height: 1;
             color: #fff;

             background: linear-gradient(180deg, #ef4444, #dc2626);
             border: 2px solid #fff;

             /* التوهج المتحرك */
             animation: badge-glow 1.2s ease-in-out infinite;
         }

         .wb-desc-link {
             display: inline-block;
             padding: 0.35rem 0.75rem;
             /* مسافة داخلية */
             border-radius: 0.5rem;
             /* زوايا ناعمة */
             font-size: 0.75rem;
             /* حجم صغير مثل باقي النص */
             font-weight: 500;
             line-height: 1.25rem;
             color: #fff;
             /* لون النص أبيض */
             background: linear-gradient(180deg, #3b82f6, #2563eb);
             /* أزرق */
             border: 1px solid #1d4ed8;
             text-decoration: none;
             cursor: pointer;
             transition: all 0.2s ease-in-out;
             box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
         }

         .wb-desc-link:hover {
             background: linear-gradient(180deg, #2563eb, #1d4ed8);
             transform: translateY(-1px);
             box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
         }

         .wb-desc-link:active {
             transform: translateY(0);
             box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
         }
     </style>

     <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

     <script>
         // Enable pusher logging - don't include this in production
         Pusher.logToConsole = true;

         var pusher = new Pusher('ff551e5dba18d083602f', {
             cluster: 'ap1'
         });

         const channel = pusher.subscribe('warnings');
         channel.bind('warnings.updated', function(data) {
             document.getElementById('warn-audio').play();

             window.Livewire?.dispatch?.('warnings:refresh'); // Livewire v3
         });
     </script>

     <audio id="warn-audio" src="/audio/warning.mp3" preload="auto"></audio>


 </div>
