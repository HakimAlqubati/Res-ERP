<?php

namespace App\Providers;

use Illuminate\Foundation\AliasLoader;
use Barryvdh\Debugbar\Facades\Debugbar;
use App\Models\CustomTenantModel;
use App\Models\Employee;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoiceDetail;
use App\Models\StockTransferOrder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Notification as NotificationsNotification;
use App\Notifications\NotificationAttendance;
use App\Notifications\NotificationAttendanceCheck;
use App\Observers\EmployeeObserver;
use App\Observers\InventoryTransactionObserver;
use App\Observers\PurchaseInvoiceDetailObserver;
use App\Observers\StockTransferOrderObserver;
use App\Observers\TaskObserver;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Models\StockInventory;
use App\Observers\StockInventoryObserver;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Livewire\DatabaseNotifications;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification as BaseNotification;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Debugbar', Debugbar::class);
        // $this->app->bind(BaseNotification::class, NotificationAttendanceCheck::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');
        CustomTenantModel::observe(TenantObserver::class);
        InventoryTransaction::observe(InventoryTransactionObserver::class);
        StockTransferOrder::observe(StockTransferOrderObserver::class);
        StockInventory::observe(StockInventoryObserver::class);

        // PurchaseInvoiceDetail::observe(PurchaseInvoiceDetailObserver::class);

        // Task::observe(TaskObserver::class);
        // Employee::observe(EmployeeObserver::class);
        // User::observe(UserObserver::class);
        // NotificationAttendance::configureUsing(function (NotificationAttendance $notification): void {
        //     $notification->view('filament.notifications.notification');
        // });



        // Notifications::alignment(Alignment::Start);
        // LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
        //     $switch
        //         ->locales(['ar', 'en']); // also accepts a closure
        // });

        FilamentAsset::register([
            // Js::make('custom-script', __DIR__ . '/../../tune.js'),
            // Js::make('custom-script', ''),
            // Css::make('main', ''),
            // Css::make('keypad', ''),
            // Js::make('example-local-script', asset('js/tune.js')),
        ]);
        // FilamentView::registerRenderHook(
        //     'panels::auth.login.form.after',
        //     fn(): View => view('filament.login_extra')
        // );

        // Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        // Gate::policy(Task::class, \App\Policies\TaskPolicy::class);
        try {
            Storage::extend('google', function ($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (!empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }


                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // your exception handling logic
        }
    }
}
