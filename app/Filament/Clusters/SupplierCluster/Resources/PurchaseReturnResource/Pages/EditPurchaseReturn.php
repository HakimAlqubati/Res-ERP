<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Modules\Stock\PurchaseReturns\Actions\CreatePurchaseReturnDraftAction;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status !== \App\Models\PurchaseReturn::STATUS_DRAFT || $this->record->cancelled) {
            Notification::make()
                ->title('Cannot Edit Return')
                ->body('Only draft purchase returns can be edited.')
                ->danger()
                ->send();

            $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            PurchaseReturnResource::getApproveAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
            PurchaseReturnResource::getCancelAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['details'] = $this->record->getFormDetails();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->status !== \App\Models\PurchaseReturn::STATUS_DRAFT || $record->cancelled) {
            Notification::make()
                ->title('Cannot Edit Return')
                ->body('Approved or cancelled purchase returns cannot be modified.')
                ->danger()
                ->send();

            $this->halt();
        }

        $rawState = $this->form->getRawState();
        $data['details'] = $rawState['details'] ?? $data['details'] ?? [];

        $action = app(CreatePurchaseReturnDraftAction::class);
        $dto = CreatePurchaseReturnDTO::fromRequest($data, (int) auth()->id(), (int) $record->id);

        try {
            return $action->execute($dto);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Validation Failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
