<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-center space-x-4 space-x-reverse">
                <x-heroicon-o-finger-print class="w-12 h-12" />
                <div class="text-center">
                    <h2 class="text-2xl font-bold">نظام تسجيل الحضور الذكي</h2>
                    <p class="text-sm opacity-90 mt-1">الإصدار 2.0 - مباشر من Service Class</p>
                </div>
            </div>
        </div>

        {{-- Current Date & Time Display --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow text-center">
                <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">التاريخ</div>
                <div class="text-lg font-semibold" id="current-date">{{ now()->format('Y-m-d') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow text-center">
                <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">الوقت</div>
                <div class="text-lg font-semibold" id="current-time">{{ now()->format('H:i:s') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow text-center">
                <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">اليوم</div>
                <div class="text-lg font-semibold">{{ now()->locale('ar')->translatedFormat('l') }}</div>
            </div>
        </div>

        {{-- Form Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex justify-center">
                    <x-filament::button
                        type="submit"
                        size="xl"
                        icon="heroicon-o-finger-print"
                        color="success"
                        class="w-full md:w-auto px-12 py-4">
                        <span class="text-lg">تسجيل البصمة</span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Info Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-700 rounded-lg p-4">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <x-heroicon-o-information-circle class="w-6 h-6 text-info-600 dark:text-info-400 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-info-900 dark:text-info-100 mb-1">ملاحظة</h3>
                        <p class="text-sm text-info-700 dark:text-info-300">
                            يمكنك استخدام رقم الموظف أو رمز RFID لتسجيل الحضور
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-700 rounded-lg p-4">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-success-900 dark:text-success-100 mb-1">مرتبط مباشرة</h3>
                        <p class="text-sm text-success-700 dark:text-success-300">
                            هذه الصفحة مربوطة مباشرة مع AttendanceServiceV2
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Update time every second
        setInterval(function() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', {
                hour12: false
            });
            document.getElementById('current-time').textContent = time;
        }, 1000);
    </script>
    @endpush
</x-filament-panels::page>