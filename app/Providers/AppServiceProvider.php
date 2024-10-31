<?php

namespace App\Providers;

use App\Notifications\Notification as NotificationsNotification;
use App\Notifications\NotificationAttendance;
use App\Notifications\NotificationAttendanceCheck;
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
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        // $this->app->bind(BaseNotification::class, NotificationAttendanceCheck::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');


        // NotificationAttendance::configureUsing(function (NotificationAttendance $notification): void {
        //     $notification->view('filament.notifications.notification');
        // });
        
        
        
        // Notifications::alignment(Alignment::Start);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar', 'en']); // also accepts a closure
        });

        FilamentAsset::register([
            // Js::make('custom-script', __DIR__ . '/../../tune.js'),
            // Js::make('custom-script', ''),
            // Css::make('main', ''),
            // Css::make('keypad', ''),
            // Js::make('example-local-script', asset('js/tune.js')),
        ]);
        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn(): View => view('filament.login_extra')
        );

        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        // Gate::policy(Task::class, \App\Policies\TaskPolicy::class);

    }
}
