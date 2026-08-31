<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn(): bool => $this->record->status === PurchaseReturn::STATUS_DRAFT && ! $this->record->cancelled),
            PurchaseReturnResource::getApproveAction()
                ->after(fn() => $this->fillForm()),
            PurchaseReturnResource::getCancelAction()
                ->after(fn() => $this->fillForm()),
        ];
    }
}
