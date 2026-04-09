<?php

namespace App\Modules\HR\AttendanceReports\Providers;

use App\Modules\HR\AttendanceReports\AttendanceReportManager;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
use Illuminate\Support\ServiceProvider;

class AttendanceReportServiceProvider extends ServiceProvider
{
    /**
     * Register attendance report unified bindings.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            AttendanceReportInterface::class,
            AttendanceReportManager::class
        );
    }

    /**
     * Bootstrap any module services if needed.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
