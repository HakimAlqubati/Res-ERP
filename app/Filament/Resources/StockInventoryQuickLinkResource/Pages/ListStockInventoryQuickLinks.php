<?php

namespace App\Filament\Resources\StockInventoryQuickLinkResource\Pages;

use App\Filament\Resources\StockInventoryQuickLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockInventoryQuickLinks extends ListRecords
{
    protected static string $resource = StockInventoryQuickLinkResource::class;
    protected string $view = 'filament.resources.stock-issue-order-resource.pages.stock-invetory-quick-links';
    public function getModelLabel(): ?string
    {
        return '';
    }
    // عدد أعمدة منطقة الودجت أعلى الجدول (مثلاً 4 أعمدة)
    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function getColumnStart() {
        return 1;
    }
    public function getColumnSpan(): int|array
    {
        return 3;
    }
}
