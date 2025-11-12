<?php

use Illuminate\Support\Facades\Route;

Route::prefix('docs/api/maintenance')
    ->name('docs.api.maintenance.')
    // ->middleware('auth.basic') // اختياري
    ->group(function () {
        // صفحة توثيق إنشاء المعدات
        Route::view('/equipments', 'docs.api.maintenance.equipments')
            ->name('equipments');
    });

    