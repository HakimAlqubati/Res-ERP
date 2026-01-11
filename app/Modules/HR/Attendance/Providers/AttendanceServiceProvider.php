<?php

namespace App\Modules\HR\Attendance\Providers;

use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Events\AttendanceRejected;
use App\Modules\HR\Attendance\Events\CheckInRecorded;
use App\Modules\HR\Attendance\Events\CheckOutRecorded;
use App\Modules\HR\Attendance\Events\LateArrivalDetected;
use App\Modules\HR\Attendance\Listeners\LogAttendanceActivity;
use App\Modules\HR\Attendance\Listeners\StoreRejectedAttendance;
use App\Modules\HR\Attendance\Listeners\UpdateWorkDuration;
use App\Modules\HR\Attendance\Repositories\AttendanceRepository;
use App\Modules\HR\Attendance\Services\ShiftResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * مزود خدمات وحدة الحضور
 * 
 * يقوم بتسجيل جميع الـ bindings والـ routes والـ events الخاصة بالوحدة
 */
class AttendanceServiceProvider extends ServiceProvider
{
    /**
     * تسجيل الخدمات
     */
    public function register(): void
    {
        // تسجيل Repository
        $this->app->bind(
            AttendanceRepositoryInterface::class,
            AttendanceRepository::class
        );

        // تسجيل ShiftResolver
        $this->app->bind(
            ShiftResolverInterface::class,
            ShiftResolver::class
        );
    }

    /**
     * تشغيل الخدمات
     */
    public function boot(): void
    {
        // تحميل الـ routes
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        // تسجيل الأحداث والمستمعين
        $this->registerEvents();
    }

    /**
     * تسجيل الأحداث والمستمعين
     */
    protected function registerEvents(): void
    {
        // تسجيل الدخول
        Event::listen(
            CheckInRecorded::class,
            [LogAttendanceActivity::class, 'handleCheckIn']
        );

        // تسجيل الخروج
        // تحديث المدد عند الخروج
        Event::listen(
            CheckOutRecorded::class,
            [UpdateWorkDuration::class, 'handle']
        );

        Event::listen(
            CheckOutRecorded::class,
            [LogAttendanceActivity::class, 'handleCheckOut']
        );

        // اكتشاف التأخير
        // يمكن إضافة listeners إضافية هنا لاحقاً
        // مثل: إرسال إشعارات، تحديث تقارير، إلخ
        Event::listen(LateArrivalDetected::class, function (LateArrivalDetected $event) {
            // حالياً نكتفي بالتسجيل في log
            // يمكن إضافة listeners إضافية عند الحاجة
        });

        // رفض التسجيل
        Event::listen(
            AttendanceRejected::class,
            [StoreRejectedAttendance::class, 'handle']
        );

        Event::listen(
            AttendanceRejected::class,
            [LogAttendanceActivity::class, 'handleRejected']
        );
    }

    /**
     * الخدمات المقدمة من هذا المزود
     */
    public function provides(): array
    {
        return [
            AttendanceRepositoryInterface::class,
            ShiftResolverInterface::class,
        ];
    }
}
