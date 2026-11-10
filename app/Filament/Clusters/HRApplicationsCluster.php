<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class HRApplicationsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    public static function getClusterBreadcrumb(): ?string
    {
        return __('lang.requests');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.requests');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\EmployeeApplicationV2::whereHas('employee', function ($q) {
            $q->whereNull('deleted_at');
        })
            ->forBranchManager()
            ->pending()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canAccess(): bool
    {
        return \App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource::canViewAny();
    }

    public function mount(): void
    {
        redirect(\App\Filament\Clusters\HRApplicationsCluster\Pages\RequestsDashboard::getUrl());
    }

    public function getSubNavigation(): array
    {
        return [];
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.h-r-applications.*'))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->sort(static::getNavigationSort())
                ->url(\App\Filament\Clusters\HRApplicationsCluster\Pages\RequestsDashboard::getUrl()),
        ];
    }
}
