<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages;

use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TreeAccount extends Page
{
    protected static string $resource = AccountResource::class;

    protected   string $view = 'filament.clusters.finance-formatting-cluster.resources.accounts.pages.tree-account';

    public function getHeading(): string
    {
        return __('شجرة الدليل المحاسبي');
    }

    public static function getNavigationIcon(): ?string
    {
        return Heroicon::OutlinedRectangleGroup->value;
    }
}
