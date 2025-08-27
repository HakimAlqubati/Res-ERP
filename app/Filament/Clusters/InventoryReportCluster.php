<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Redirect;

class InventoryReportCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    public static function getNavigationLabel(): string
    {
        return __('menu.inventory_reports');
    }
    // public static function getClusterBreadcrumb(): ?string
    // {
    //     return null;
    // }
    public function mount(): void
    {
        // توجيه المستخدم إلى مسار آخر عند الوصول إلى هذا الـ Cluster
        Redirect::route('filament.admin.pages.inventory-reports-links'); // استبدل "new.route.name" بالمسار الذي تريد التوجيه إليه
    }
}