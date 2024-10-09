<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Notifications\Livewire\Notifications;
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

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::Center);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar', 'en']); // also accepts a closure
        });

        FilamentAsset::register([
            // Js::make('custom-script', __DIR__ . '/../../tune.js'),
            Js::make('custom-script', ''),
            Css::make('main', ''),
            Css::make('keypad', ''),
            Js::make('example-local-script', asset('js/tune.js')),
        ]);
        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn(): View => view('filament.login_extra')
        );

        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        // Gate::policy(Task::class, \App\Policies\TaskPolicy::class);

    }
}
