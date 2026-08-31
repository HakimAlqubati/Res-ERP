<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\Actions\ApprovePurchaseReturnAction;
use App\Modules\Stock\PurchaseReturns\Actions\CancelPurchaseReturnAction;
use App\Modules\Stock\PurchaseReturns\Actions\CreatePurchaseReturnDraftAction;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Return')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status === PurchaseReturn::STATUS_DRAFT && !$this->record->cancelled)
                ->requiresConfirmation()
                ->modalHeading('Approve Purchase Return')
                ->modalDescription('Approving this return will deduct the products from inventory and create a supplier debit note. Are you sure you want to proceed?')
                ->action(function (ApprovePurchaseReturnAction $action) {
                    try {
                        $action->execute($this->record, (int) auth()->id());

                        Notification::make()
                            ->title('Success')
                            ->body('Purchase return has been approved successfully.')
                            ->success()
                            ->send();

                        $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Approval Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel Return')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => !$this->record->cancelled)
                ->form([
                    Textarea::make('cancel_reason')
                        ->label('Cancellation Reason')
                        ->required(),
                ])
                ->action(function (array $data, CancelPurchaseReturnAction $action) {
                    try {
                        $action->execute($this->record, $data['cancel_reason'], (int) auth()->id());

                        Notification::make()
                            ->title('Cancelled')
                            ->body('Purchase return has been cancelled.')
                            ->warning()
                            ->send();

                        $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Cancellation Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $action = app(CreatePurchaseReturnDraftAction::class);
        $dto = CreatePurchaseReturnDTO::fromRequest($data, (int) auth()->id(), (int) $record->id);

        return $action->execute($dto);
    }
}
