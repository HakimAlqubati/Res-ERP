<div class="flex items-center gap-x-6">

    {{-- باقي العناصر هنا --}}

    {{-- عرض الوقت الحالي --}}
    <div class="ml-auto text-sm text-gray-600 font-medium time">
        🕒 <span id="current-time">--:--:--</span>
    </div>

</div>

<style>
    .time {
        border: 1px solid;
        padding: 6px;
        border-radius: 5px;
    }
</style>

{{-- سكربت التحديث --}}
@push('scripts')
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true // <-- هذا مهم لعرض AM/PM
            });
            document.getElementById('current-time').textContent = timeString;
        }

        setInterval(updateClock, 1000);
        updateClock(); // أول تشغيل فوري
    </script>
@endpush
