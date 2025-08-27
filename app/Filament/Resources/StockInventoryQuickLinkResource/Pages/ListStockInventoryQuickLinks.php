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
}