<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Resources\StockInventoryQuickLinkResource\Pages\ListStockInventoryQuickLinks;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Resources\StockInventoryQuickLinkResource\Pages;
use App\Models\Orders\OrderReport;
use Filament\Resources\Resource;

class StockInventoryQuickLinkResource extends Resource
{
    protected static ?string $model = OrderReport::class;
    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 0;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
}