<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Tables;

use Filament\Tables\Table;

class InventoryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->filters([
            ])
            ->deferFilters(false);
    }
}
