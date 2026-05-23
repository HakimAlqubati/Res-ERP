<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Resources\StockInventoryQuickLinkResource\Pages\ListStockInventoryQuickLinks;
use App\Models\Orders\OrderReport;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;

class StockInventoryQuickLinkResource extends Resource
{
    protected static ?string $model = OrderReport::class;

    protected static ?string $cluster = InventoryManagementCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): ?string
    {
        return '';

        return 'Quick Links';
    }

    public static function getModelLabel(): string
    {
        return 'Quick Links';
    }

    public static function getPluralLabel(): ?string
    {
        return '';
    }

    // public static function getPluralModelLabel(): string
    // {
    //     return '';
    // }
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockInventoryQuickLinks::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
