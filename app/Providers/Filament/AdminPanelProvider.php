<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRCircularCluster;
use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HrClusteReport;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryReportsCluster;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderCluster\Resources\OrderResource;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Pages\EmployeeRecords;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\Shield\RoleResource;
use App\Filament\Resources\SystemSettingResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\UserResource;
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
       
        ->brandName('Workbench')
        ->favicon(asset('storage/logo/default.png'))
        ->brandLogo(asset('storage/logo/default.png'))
        ->darkModeBrandLogo(asset('storage/logo/default-wb.png'))
        ->brandLogoHeight('3.5rem')
        ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
            $menu =  $builder->items([
                NavigationItem::make(__('lang.dashboard'))->hidden(function(){
                    if(getCurrentRole() == 17){
                        return true;
                    }
                    return false;
                })
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                    ->url(fn (): string => Dashboard::getUrl()), 
                
            ])
            ->groups([
                // NavigationGroup::make(__('menu.order_ms'))
                //     ->items([
                //         ...MainOrdersCluster::getNavigationItems(),
                //         ...ReportOrdersCluster::getNavigationItems(),
                //         // NavigationItem::make('test')
                //         // ->icon('heroicon-o-home')
                //         // ->childItems([
                //         //     ...OrderResource::getNavigationItems(),
                            

                //         // ])
                //         // ,
                        
                //     ])
                //     ,
                // NavigationGroup::make(__('menu.inventory_ms'))
                //     ->items([
                //         ...ProductUnitCluster::getNavigationItems(),
                //         ...InventoryCluster::getNavigationItems(),
                //         ...InventoryReportsCluster::getNavigationItems(),
                //     ]),
                NavigationGroup::make(__('menu.hr_ms'))
                        // ->icon('heroicon-o-user-group')
                        ->items(array_merge(
                         (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ?  HRCluster::getNavigationItems(): [], 
                        (getCurrentRole() != 17) ?  HRTasksSystem::getNavigationItems(): [], 
                         (getCurrentRole() != 17) ? HRServiceRequestCluster::getNavigationItems(): [], 
                         (isSuperAdmin() || isBranchManager() || isSystemManager() || isStuff()) ? HRAttenanceCluster::getNavigationItems(): [], 
                          (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) ? HRAttendanceReport::getNavigationItems(): [], 
                        //   (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) ? EmployeeRecords::getNavigationItems(): [], 
                        //   (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) ? HrClusteReport::getNavigationItems(): [], 
                         (getCurrentRole() != 17) ? HRCircularCluster::getNavigationItems(): [], 
                          (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) ? HRSalaryCluster::getNavigationItems(): [], 
                          (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) ? HRApplicationsCluster::getNavigationItems(): [], 
                        ))
                    // ->items([
                    //     ...HRCluster::getNavigationItems(), 
                    //     ...HRTasksSystem::getNavigationItems(), 
                    //     ...HRServiceRequestCluster::getNavigationItems(), 
                    //     ...HRAttenanceCluster::getNavigationItems(), 
                    //     ...HRAttendanceReport::getNavigationItems(), 
                    //     ...HRCircularCluster::getNavigationItems(), 
                    
                    // ])
                    ,
                NavigationGroup::make(__('lang.user_and_roles'))
                ->items(array_merge(
                    (isSuperAdmin() || isSystemManager() || isBranchManager()) ?   UserResource::getNavigationItems(): [],
                    isSuperAdmin() || isSystemManager() ? RoleResource::getNavigationItems() : []
                ))
                    ,
                NavigationGroup::make(__('lang.branches'))
                    ->items(array_merge(
                     (isSuperAdmin() || isSystemManager() || isBranchManager()) ? BranchResource::getNavigationItems(): [] ,
                    ))
                    ,
                    
                    NavigationGroup::make('System settings')
                        ->items(array_merge(
                         (isSuperAdmin() || isSystemManager() || isFinanceManager()) ? SettingResource::getNavigationItems(): [] ,
                        ))
                        ,
                    NavigationGroup::make('Tenants')
                        ->items(array_merge(
                         (isSuperAdmin() && ((count(explode('.', request()->getHost())) == 1 && env('APP_ENV') == 'local')
                        || (count(explode('.', request()->getHost())) == 2 && env('APP_ENV') == 'production')
                         )) ? TenantResource::getNavigationItems(): [] ,
                        ))
                        ,
                ]);
               return $menu;
        })
        ->id('admin')
            ->default()
            ->path('admin')
            ->login()
            ->defaultThemeMode(ThemeMode::Dark)
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
                        'sm' => 2,
                        'lg' => 3
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
            ->databaseNotifications()
            ->databaseTransactions()
            // ->renderHook( name: 'panels::topbar.start', hook: fn (): View => view('livewire.credits'), )
            ;

    }
}
