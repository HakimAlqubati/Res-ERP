<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CircularWidget;
use App\Filament\Widgets\EmployeeSearchWidget;
use App\Filament\Widgets\TaskWidget;
use App\Models\CustomTenantModel;
use Spatie\Multitenancy\Contracts\IsTenant;

class Dashboard extends \Filament\Pages\Dashboard

{
    public function getColumns(): int | string | array
    {
        return 2;
    }
    public function getWidgets(): array
    {
        $currentTenant = app(IsTenant::class)::current();
        ($currentTenant && is_array($currentTenant->modules) && in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules))
            ||
            is_null($currentTenant);

        $widgets = [];

        $modules = json_decode($currentTenant?->modules, true);
        if (
            is_null($currentTenant) ||
            (is_array($modules) &&
                in_array(CustomTenantModel::MODULE_HR, $modules))
        ) {
            $widgets[] = CircularWidget::class;
            $widgets[] = TaskWidget::class;
        }
        return $widgets;

        return [
            CircularWidget::class,
            TaskWidget::class,
        ];
    }
}
