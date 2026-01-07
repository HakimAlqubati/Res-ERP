<?php

namespace App\Modules\HR\Attendance\Providers;

use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Repositories\AttendanceRepository;
use App\Modules\HR\Attendance\Services\ShiftResolver;
use Illuminate\Support\ServiceProvider;

/**
 * مزود خدمات وحدة الحضور
 * 
 * يقوم بتسجيل جميع الـ bindings والـ routes الخاصة بالوحدة
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
