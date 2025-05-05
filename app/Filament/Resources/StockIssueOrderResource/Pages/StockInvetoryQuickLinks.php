<?php

namespace App\Filament\Resources\StockIssueOrderResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource;
use Filament\Resources\Pages\Page;

class StockInvetoryQuickLinks extends Page
{
    protected static string $resource = StockIssueOrderResource::class;

    protected static string $view = 'filament.resources.stock-issue-order-resource.pages.stock-invetory-quick-links';
}
