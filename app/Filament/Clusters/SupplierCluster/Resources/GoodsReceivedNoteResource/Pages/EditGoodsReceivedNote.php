<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Models\GoodsReceivedNote;
use App\Models\User;  
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Traits\HasPriceChangeWarning;
use Filament\Actions\Action;

class EditGoodsReceivedNote extends EditRecord
{
    use HasPriceChangeWarning;
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected bool $wasRejected = false;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->wasRejected = ($this->record->status === GoodsReceivedNote::STATUS_REJECTED);
        $this->checkPriceChanges();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status === GoodsReceivedNote::STATUS_REJECTED) {
            $data['status'] = GoodsReceivedNote::STATUS_CREATED;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->wasRejected) {
            $allowedRoles = setting('grn_approver_role_id', []);
            if (!empty($allowedRoles)) {
                $approvers = User::whereHas('roles', fn($q) => $q->whereIn('id', (array) $allowedRoles))->get();
                foreach ($approvers as $approver) {
                    Notification::make()
                        ->title('GRN Resubmitted: #' . $this->record->grn_number)
                        ->body('The rejected GRN #' . $this->record->grn_number . ' has been updated and resubmitted for review.')
                        ->info()
                        ->actions([
                            Action::make('review')
                                ->label('Review & Approve')
                                ->button()
                                ->url(GoodsReceivedNoteResource::getUrl('create-purchase-invoice', ['record' => $this->record])),
                        ])
                        ->sendToDatabase($approver);
                }
            }

            Notification::make()
                ->title('GRN Resubmitted')
                ->body('The rejected GRN has been successfully updated and resubmitted for approval.')
                ->success()
                ->send();
        }
    }
}
