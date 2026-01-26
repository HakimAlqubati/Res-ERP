<?php

return [
    App\Modules\HR\Attendance\Providers\AttendanceServiceProvider::class,
    App\Modules\HR\Payroll\Providers\PayrollServiceProvider::class,
    App\Providers\AWS\AwsServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\DevPanelProvider::class,
    App\Providers\InventoryServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\WarningsServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Mccarlosen\LaravelMpdf\LaravelMpdfServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
];
