<?php

namespace App\Livewire\Topbar;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoInventoryReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use Livewire\Component;

class QuickLinks extends Component
{
    /**
     * Seed with static items first; you can later load per-role or from DB.
     * Each item: ['label' => 'string', 'href' => 'string', 'icon' => 'heroicon-*']
     */


    public array $links = [];

    public function mount()
    {
        $this->links = [
            [
                'label'       => 'Inventory Report',
                'description' => 'View current stock levels.',
                'icon'        => 'heroicon-o-building-storefront',  
                'href'         => \App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource::getUrl(),
            ],
            [
                'label'       => 'Fifo Inventory',
                'description' => 'Fifo inventory report.',
                'icon'        => 'heroicon-o-archive-box',
                'href'         => \App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoInventoryReportResource::getUrl(),
            ],
        ];
    }
}
