<?php

namespace App\Providers\Filament;

use App\Filament\Central\Pages\CentralDashboard;
use App\Filament\Pages\Auth\CustomLogin;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\EnsureCentralDomain;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CentralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('central')
            ->path('central')
            ->authGuard('web')
            ->login(CustomLogin::class)
            ->strictAuthorization()
            ->brandName('Workbench Central')
            ->favicon(asset('storage/logo/default.png'))
            ->brandLogo(asset('storage/logo/default.png'))
            ->darkModeBrandLogo(asset('storage/logo/default-wb.png'))
            ->brandLogoHeight('3.0rem')
            ->defaultThemeMode(ThemeMode::System)
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'primary' => '#03381c',
            ])
            ->font('Poppins')
            ->discoverResources(in: app_path('Filament/Central/Resources'), for: 'App\\Filament\\Central\\Resources')
            ->discoverPages(in: app_path('Filament/Central/Pages'), for: 'App\\Filament\\Central\\Pages')
            ->pages([
                CentralDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Central/Widgets'), for: 'App\\Filament\\Central\\Widgets')
            ->widgets([])
            ->middleware([
                EnsureCentralDomain::class,
            ], isPersistent: true)
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
            ->authMiddleware([
                Authenticate::class,
                CheckUserActive::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.partials.current-time')->render()
            );
    }
}
