<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DevPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dev')
            ->path('dev')
            ->login()
            ->colors([
                'primary' => '#03381c',
            ])
            ->brandName('Workbench')
            ->favicon(asset('storage/logo/default.png'))
            ->brandLogo(asset('storage/logo/default.png'))
            ->darkModeBrandLogo(asset('storage/logo/default-wb.png'))
            ->brandLogoHeight('3.0rem')
            ->defaultThemeMode(ThemeMode::Dark)

            ->discoverResources(in: app_path('Filament/Dev/Resources'), for: 'App\Filament\Dev\Resources')
            ->discoverPages(in: app_path('Filament/Dev/Pages'), for: 'App\Filament\Dev\Pages')
            ->pages([
                \App\Filament\Dev\Pages\DevDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dev/Widgets'), for: 'App\Filament\Dev\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->sidebarCollapsibleOnDesktop()
               ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn(): string =>
                view('filament.partials.current-time')->render()
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
