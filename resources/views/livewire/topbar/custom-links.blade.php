<div class="flex items-center gap-x-6">

    {{-- باقي العناصر هنا --}}

    {{-- عرض الوقت الحالي --}}
    <div class="ml-auto text-sm text-gray-600 font-medium time flex items-center gap-x-2">
        🕒 <span id="current-time">--:--:--</span>
        📅 <span id="current-date">--/--/----</span>
    </div>

</div>

<style>
    .time {
        border: 1px solid #d1d5db;
        padding: 6px 10px;
        border-radius: 6px;
        background-color: #f9fafb;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>
@push('scripts')
    <script>
        function updateClock() {
            const now = new Date();

            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            const dateString = now.toLocaleDateString('en-GB', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });

            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
@endpush
