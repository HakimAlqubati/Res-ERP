<?php

namespace App\Providers;

use App\Models\Task;
use App\Observers\TaskObserver;
use Filament\Support\Assets\Css;
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
        Task::observe(TaskObserver::class);

        FilamentAsset::register([
            Css::make('main', ''),
        ]);
        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn(): View => view('filament.login_extra')
        );

        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(Task::class, \App\Policies\TaskPolicy::class);

    }
}
