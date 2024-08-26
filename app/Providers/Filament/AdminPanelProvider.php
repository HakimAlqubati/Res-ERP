<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderCluster\Resources\OrderResource;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\Shield\RoleResource;
use App\Filament\Resources\SystemSettingResource;
use App\Filament\Resources\UserResource;
use Coolsam\Modules\ModulesPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
        ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
            $menu =  $builder->items([
                NavigationItem::make(__('lang.dashboard'))
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                    ->url(fn (): string => Dashboard::getUrl()), 
                
            ])
            ->groups([
                NavigationGroup::make(__('menu.order_ms'))
                    ->items([
                        ...MainOrdersCluster::getNavigationItems(),
                        ...ReportOrdersCluster::getNavigationItems(),
                        // NavigationItem::make('test')
                        // ->icon('heroicon-o-home')
                        // ->childItems([
                        //     ...OrderResource::getNavigationItems(),
                            

                        // ])
                        // ,
                        
                    ])
                    ,
                NavigationGroup::make(__('menu.inventory_ms'))
                    ->items([
                        ...ProductUnitCluster::getNavigationItems(),
                        ...InventoryCluster::getNavigationItems(),
                    ]),
                NavigationGroup::make(__('menu.hr_ms'))
                    ->items([
                        ...HRCluster::getNavigationItems(), 
                    ]),
                NavigationGroup::make(__('lang.user_and_roles'))
                    ->items([
                        ...UserResource::getNavigationItems(),
                        ...RoleResource::getNavigationItems(),
                    ]),
                NavigationGroup::make(__('lang.system_settings'))
                    ->items([
                        ...SystemSettingResource::getNavigationItems()
                    ]),
                ]);
               return $menu;
        })
        ->id('admin')
            ->default()
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ;

    }
}
