 
<x-filament::dropdown placement="bottom-end" teleport shift class="rtl:[&>*]:text-right">
    <x-slot name="trigger">
        <x-filament::icon-button icon="heroicon-o-rocket-launch" 
        label="Quick Links"
         tooltip="Quick Links" size="sm"
            color="primary" />
    </x-slot>

    <x-filament::dropdown.list class="min-w-60">
        <div class="px-3 pt-2 pb-1">
            <div class="text-xs font-medium text-gray-500"></div>
        </div>

        @foreach ($links as $link)
            <x-filament::dropdown.list.item :href="$link['href']" tag="a" :icon="$link['icon']" color="gray">
                {{ $link['label'] }}
            </x-filament::dropdown.list.item>
        @endforeach

        <div class="px-3 py-2 border-t border-gray-100 dark:border-white/10">
            <div class="text-[11px] text-gray-400">
                {{-- Tips: Customize items in <code>QuickLinks::$links</code> or load from DB/role. --}}
            </div>
        </div>
    </x-filament::dropdown.list>
</x-filament::dropdown>
