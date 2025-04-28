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
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderCluster\Resources\OrderResource;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Clusters\SettingsCluster;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Pages\CustomLogin;
use App\Filament\Pages\Auth\CustomLogin as CustomLogin2;
use App\Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Pages\EmployeeRecords;
use App\Filament\Resources\ApprovalResource;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\Shield\RoleResource;
use App\Filament\Resources\SystemSettingResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VisitLogResource;
use App\Models\CustomTenantModel;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Filament\Support\Colors\Color;
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
            ->brandLogoHeight('3.5rem')
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
                            HRCluster::canAccess() ? HRCluster::getNavigationItems() : [],
                            HRTasksSystem::canAccess() ? HRTasksSystem::getNavigationItems() : [],
                            HRServiceRequestCluster::canAccess() ? HRServiceRequestCluster::getNavigationItems() : [],
                            HRAttenanceCluster::canAccess() ? HRAttenanceCluster::getNavigationItems() : [],
                            HRAttendanceReport::canAccess() ? HRAttendanceReport::getNavigationItems() : [],
                            HRTaskReport::canAccess() ? HRTaskReport::getNavigationItems() : [],
                            HRCircularCluster::canAccess() ? HRCircularCluster::getNavigationItems() : [],
                            HRSalaryCluster::canAccess() ? HRSalaryCluster::getNavigationItems() : [],
                            HRApplicationsCluster::canAccess() ? HRApplicationsCluster::getNavigationItems() : [],
                        ));
                }

                if (
                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_STOCK, $currentTenant->modules))
                    ||
                    is_null($currentTenant)

                ) {

                    $group[] =  NavigationGroup::make(__('menu.supply_and_inventory'))
                        ->items(array_merge(
                            MainOrdersCluster::canAccess() ? MainOrdersCluster::getNavigationItems() : [],
                            ProductUnitCluster::getNavigationItems(),
                            ReportOrdersCluster::getNavigationItems(),
                            SupplierCluster::getNavigationItems(),
                            SupplierStoresReportsCluster::getNavigationItems(),
                            InventoryManagementCluster::getNavigationItems(),
                            InventoryReportCluster::getNavigationItems(),

                        ));
                }

                $group =  array_merge(
                    $group,
                    [
                        NavigationGroup::make(__('lang.user_and_roles'))
                            ->items(array_merge(
                                UserResource::canAccess() ? UserResource::getNavigationItems() : [],
                                RoleResource::canAccess() ? RoleResource::getNavigationItems() : []
                            )),
                        NavigationGroup::make(__('lang.branches'))
                            ->items(array_merge(
                                (isSuperAdmin() || isSystemManager() || isBranchManager()) ? BranchResource::getNavigationItems() : [],
                                //  ProductResource::getNavigationItems(),
                            )),

                        NavigationGroup::make(__('menu.area_management'))
                            ->items(array_merge(
                                (isSuperAdmin() || isSystemManager()) ? AreaManagementCluster::getNavigationItems() : [],
                            )),
                    ]
                );
                if (
                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                    ||
                    is_null($currentTenant)

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
                                ((HRSalarySettingCluster::canAccess())   &&

                                    ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                                    ||
                                    is_null($currentTenant)

                                ) ? HRSalarySettingCluster::getNavigationItems() : [],
                                ((isSuperAdmin() || isSystemManager() || isBranchManager())) && (($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
                                    ||
                                    is_null($currentTenant)) ? HRLeaveManagementCluster::getNavigationItems() : [],
                                ((isSuperAdmin() || isSystemManager() || isFinanceManager()))
                                    ? NotificationSettingResource::getNavigationItems() : [],

                                //    (isSuperAdmin() || isSystemManager()) ? SettingsCluster::getNavigationItems(): [] ,
                            )),
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
            // ->login(CustomLogin2::class)
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::Green,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'primary' => 'rgb(0, 100, 46)',
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
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 1
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])->spa()
            ->databaseNotifications()->globalSearch(false)
            ->databaseTransactions()
            // ->renderHook( name: 'panels::topbar.start', hook: fn (): View => view('livewire.credits'), )
        ;
    }
}
