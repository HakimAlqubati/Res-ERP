<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Modules\Stock\PurchaseReturns\Actions\CreatePurchaseReturnDraftAction;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PurchaseReturnResource::getApproveAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
            PurchaseReturnResource::getCancelAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $action = app(CreatePurchaseReturnDraftAction::class);
        $dto = CreatePurchaseReturnDTO::fromRequest($data, (int) auth()->id(), (int) $record->id);

        return $action->execute($dto);
    }
}
