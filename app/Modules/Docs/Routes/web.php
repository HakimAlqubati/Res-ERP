<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Docs\Controllers\WorkbenchDocsController;

Route::get('/workbench-docs/{section?}', [WorkbenchDocsController::class, 'index'])
    ->name('workbench.docs')
    ->middleware('auth')
    ;

Route::get('/workbench-docs-lang/{locale}', function ($locale) {
    session()->put('docs_locale', in_array($locale, ['ar', 'en']) ? $locale : 'ar');
    return back();
})->name('workbench.docs.lang');