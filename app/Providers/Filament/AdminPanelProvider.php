<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\AreaManagementCluster;
use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRCircularCluster;
use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HrClusteReport;
use App\Filament\Clusters\HRLeaveManagementCluster;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalarySettingCluster;
use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRTaskReport;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\InventoryReportsCluster;
use App\Filament\Clusters\InventorySettingsCluster;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\ManufacturingBranchesCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderCluster\Resources\OrderResource;
use App\Filament\Clusters\OrderReportsCluster;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Clusters\ResellersCluster;
use App\Filament\Clusters\SettingsCluster;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Pages\CustomLogin;
use App\Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Pages\EmployeeRecords;
use App\Filament\Pages\InventoryReportLinks;
use App\Filament\Resources\ApprovalResource;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\MonthClosureResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\SettingResource;
// use App\Filament\Resources\Shield\RoleResource;
use App\Filament\Resources\SystemSettingResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VisitLogResource;
use App\Models\CustomTenantModel;
// use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Coolsam\Modules\ModulesPlugin;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
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
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Multitenancy\Contracts\IsTenant;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

            ->brandName('Workbench')
            ->favicon(asset('storage/logo/default.png'))
            ->brandLogo(asset('storage/logo/default.png'))
            ->darkModeBrandLogo(asset('storage/logo/default-wb.png'))
            ->brandLogoHeight('3.0rem')
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                // app(IsTenant::class)::checkCurrent() && app(IsTenant::class)::current()->id
                $currentTenant = app(IsTenant::class)::current();
                if ($currentTenant) {
                    $currentTenant = CustomTenantModel::find($currentTenant->id);
                }

                $group = [];

                if (
                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                    ||
                    is_null($currentTenant)

                ) {
                    $group[] =  NavigationGroup::make(__('menu.hr_ms'))
                        ->items(array_merge(
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ?  HRCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isMaintenanceManager()) ?  HRTasksSystem::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isMaintenanceManager()) ? HRServiceRequestCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isBranchManager() || isSystemManager() || isStuff()) ? HRAttenanceCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ? HRAttendanceReport::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ? HRTaskReport::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isMaintenanceManager()) ? HRCircularCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ? HRSalaryCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) ? HRApplicationsCluster::getNavigationItems() : [],
                            // (isSuperAdmin() || isSystemManager()) ? MonthClosureResource::getNavigationItems() : [],
                        ));
                }

                if (
                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_STOCK, $currentTenant->modules))
                    ||
                    is_null($currentTenant)

                ) {

                    $group[] =  NavigationGroup::make(__('menu.supply_and_inventory'))
                        ->items(array_merge(
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) ?  MainOrdersCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) ?  OrderReportsCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) ?  ResellersCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) ?  ManufacturingBranchesCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager()) ?  ProductUnitCluster::getNavigationItems() : [],
                            //  (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager()) ?  ReportOrdersCluster::getNavigationItems(): [], 
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) ?  SupplierCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager()) ?  SupplierStoresReportsCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager()) ?  InventoryManagementCluster::getNavigationItems() : [],
                            (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager()) ?  InventoryReportLinks::getNavigationItems() : [],
                            // InventoryReportLinks::getNavigationItems(),

                        ));
                }

                $group =  array_merge(
                    $group,
                    [
                        NavigationGroup::make(__('lang.user_and_roles'))
                            ->items(array_merge(
                                (isSuperAdmin() || isSystemManager() || isBranchManager()) ?   UserResource::getNavigationItems() : [],
                                // isSuperAdmin() || isSystemManager() ? RoleResource::getNavigationItems() : []
                            )),
                        NavigationGroup::make(__('lang.branches'))
                            ->items(array_merge(
                                (isSuperAdmin() || isSystemManager() || isBranchManager()) ? BranchResource::getNavigationItems() : [],
                                //  ProductResource::getNavigationItems(),
                            )),

                    ]
                );
                if (
                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                    ||
                    is_null($currentTenant) && 1 < 0

                ) {
                    $group =  array_merge(
                        $group,
                        [
                            NavigationGroup::make('Requests of Visits')
                                ->items(array_merge(
                                    (isSuperAdmin() || isSystemManager()) ? ApprovalResource::getNavigationItems() : [],
                                    //  (isSuperAdmin() || isSystemManager() || isBranchManager()) ? VisitLogResource::getNavigationItems(): [] ,
                                )),


                        ]
                    );
                }
                $group =  array_merge(
                    $group,
                    [
                        NavigationGroup::make('System settings')
                            ->items(array_merge(
                                (isSuperAdmin() || isSystemManager() || isFinanceManager()) ? SettingResource::getNavigationItems() : [],
                                ((isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) &&

                                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                                    ||
                                    is_null($currentTenant) && (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager())

                                ) ? HRSalarySettingCluster::getNavigationItems() : [],
                                ((isSuperAdmin() || isSystemManager() || isBranchManager())) && (($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                                    ||
                                    is_null($currentTenant)) ? HRLeaveManagementCluster::getNavigationItems() : [],
                                ((isSuperAdmin() || isSystemManager() || isFinanceManager()))
                                    ? NotificationSettingResource::getNavigationItems() : [],
                                isSuperAdmin() ? InventorySettingsCluster::getNavigationItems() : [],

                                (isSuperAdmin() || isSystemManager()) ? AreaManagementCluster::getNavigationItems() : [],
                            )),
                        // NavigationGroup::make(__('menu.area_management'))
                        //     ->items(array_merge(
                        //         (isSuperAdmin() || isSystemManager()) ? AreaManagementCluster::getNavigationItems() : [],
                        //     )),
                        NavigationGroup::make('Tenants')
                            ->items(array_merge(
                                (isSuperAdmin() && ((count(explode('.', request()->getHost())) == 1 && env('APP_ENV') == 'local')
                                    || (count(explode('.', request()->getHost())) == 2 && env('APP_ENV') == 'production')
                                )) ? TenantResource::getNavigationItems() : [],
                            ))
                    ]
                );
                $menu =  $builder->items([
                    NavigationItem::make(__('lang.dashboard'))->hidden(function () {
                        if (getCurrentRole() == 17) {
                            return true;
                        }
                        return false;
                    })
                        ->icon('heroicon-o-home')
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.pages.dashboard'))
                        ->url(fn(): string => Dashboard::getUrl()),

                ])
                    ->groups(
                        $group
                    );
                return $menu;
            })
            ->id('admin')
            ->default()
            ->path('admin')
            ->login()
            // ->login(CustomLogin::class) 
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                // 'primary' => Color::Green,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'primary' => '#03381c',
                // 'primary' => 'rgb(99, 102, 241)'
            ])
            // ->font('Inter', provider: GoogleFontProvider::class)
            ->font('Poppins')
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                PagesDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class, 
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
            // ->middleware([
            //     'tenant',
            //     \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            //     \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
            // ])
            // ->middleware(function (Middleware $middleware) {
            //     $middleware
            //         ->group('tenant', [
            //             \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            //             \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
            //         ]);
            // })
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->plugins([
                // FilamentShieldPlugin::make()
                //     ->gridColumns([
                //         'default' => 1,
                //         'sm' => 2,
                //         'lg' => 3
                //     ])
                //     ->sectionColumnSpan(1)
                //     ->checkboxListColumns([
                //         'default' => 1,
                //         'sm' => 2,
                //         'lg' => 4,
                //     ])
                //     ->resourceCheckboxListColumns([
                //         'default' => 1,
                //         'sm' => 2,
                //     ]),
            ])

            ->assets([
                Css::make('main', '')
            ])
            ->spa()
            ->databaseNotifications()
            ->globalSearch(false)
            // ->databaseNotificationsPolling()
            ->databaseTransactions()
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn(): string =>
                view('filament.partials.current-time')->render()
                //     view('filament.partials.topbar-quick-links')->render(),
                // view('filament.partials.topbar-quick-hints')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn(): string =>
                view('filament.partials.quote-ticker')->render()
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn(): string =>
                view('filament.partials.warning-notify')->render()
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn(): string =>
                view('filament.partials.fullscreen-toggle')->render()
                //     view('filament.partials.topbar-quick-links')->render(),
                // view('filament.partials.topbar-quick-hints')->render(),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn(): string =>
                view('filament.partials.topbar-quick-links')->render(),
                // view('filament.partials.topbar-quick-hints')->render(),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn(): string =>
                // view('filament.partials.topbar-quick-links')->render(),
                view('filament.partials.topbar-quick-hints')->render(),
            )
            // ->renderHook(
            //     PanelsRenderHook::SIDEBAR_START, // بداية الشريط الجانبي
            //     fn(): string => view('filament.partials.sidebar-search')->render()
            // )

            // ->renderHook(
            //     PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            //     fn(): string =>
            //     // view('filament.partials.topbar-quick-links')->render(),
            //     view('filament.partials.topbar-notification-bell')->render(),
            // )
            // ->renderHook(
            //     PanelsRenderHook::TOPBAR_LOGO_AFTER,
            //     fn(): string =>
            //     // view('filament.partials.topbar-quick-links')->render(),
            //     view('filament.partials.topbar-inventory-reports-link')->render(),
            // )
            // ->renderHook( name: 'panels::topbar.start', hook: fn (): View => view('livewire.credits'), )
        ;
    }
}
