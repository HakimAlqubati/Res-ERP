<?php

namespace App\Modules\Accounting\Providers;

use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
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
        // Load module routes
        if (file_exists(__DIR__ . '/../routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        }

        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../../../../resources/views/accounting/testing', 'accounting-testing');

        // Register Livewire components
        \Livewire\Livewire::component('accounting::account-tree', \App\Modules\Accounting\ForTesting\Livewire\AccountTree::class);
    }
}
