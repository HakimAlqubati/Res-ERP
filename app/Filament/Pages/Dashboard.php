<?php
namespace App\Filament\Pages;

use App\Filament\Widgets\CircularWidget;
use App\Filament\Widgets\QuickLinksWidget;
use App\Filament\Widgets\TaskWidget;
use App\Models\CustomTenantModel;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\Multitenancy\Contracts\IsTenant;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getTitle(): string | Htmlable
    {
        return '';
    }
    public function getColumns(): int|array
    {
        return 2;
    }
    public function getWidgets(): array
    {  
        if (! isSuperAdmin() && ! isFinanceManager() && ! isSystemManager() && ! isSuperVisor()
            && ! isBranchManager() && ! isHR()
        ) {
            return [];
        }
        $currentTenant = app(IsTenant::class)::current();

        $widgets = [];
        // $widgets[] = BranchConsumptionChart::class;
        $widgets[] = QuickLinksWidget::class;
        // $widgets[] = TopProductsChart::class;
        $modules = is_array($currentTenant?->modules)
            ? $currentTenant->modules
            : (is_string($currentTenant?->modules) ? json_decode($currentTenant->modules, true) : null);
        if (
            is_null($currentTenant) ||
            (is_array($modules) &&
                in_array(CustomTenantModel::MODULE_HR, $modules))
        ) {
            // $widgets[] = CircularWidget::class;
            // $widgets[] = TaskWidget::class;
        }

        return $widgets;

        return [
            CircularWidget::class,
            TaskWidget::class,
        ];
    }
}