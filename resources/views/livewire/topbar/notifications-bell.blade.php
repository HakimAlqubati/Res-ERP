<div class="relative">
    <!-- زر الجرس -->
    <button wire:click="$toggle('showDropdown')" class="relative">
        🔔
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <!-- قائمة الإشعارات -->
    <div class="absolute right-0 mt-2 w-64 bg-white border rounded shadow-lg z-50"
         x-show="showDropdown" x-data="{ showDropdown: false }">
        <div class="max-h-80 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="p-2 border-b hover:bg-gray-100 flex justify-between items-center">
                    <div>
                        <strong>{{ class_basename($notification->type) }}</strong>: {{ $notification->data['message'] }}
                        <div class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    <button wire:click="markAsRead('{{ $notification->id }}')" class="text-blue-500 text-xs">Mark read</button>
                </div>
            @empty
                <div class="p-2 text-gray-500">لا توجد إشعارات</div>
            @endforelse
        </div>
    </div>
</div>
