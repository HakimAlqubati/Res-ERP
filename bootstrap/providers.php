<?php

return [
    App\Providers\AWS\AwsServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\InventoryServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Mccarlosen\LaravelMpdf\LaravelMpdfServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    App\Providers\WarningsServiceProvider::class,

    // Modules
    App\Modules\HR\Attendance\Providers\AttendanceServiceProvider::class,
];
