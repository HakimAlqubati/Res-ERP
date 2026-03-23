<?php

namespace App\Modules\Docs\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DocsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load the module's views using a dedicated 'docs::' namespace
        $this->loadViewsFrom(__DIR__ . '/../Views', 'docs');

        // Load the module's routes cleanly
        Route::middleware('web')
            ->group(__DIR__ . '/../Routes/web.php');
    }
}
