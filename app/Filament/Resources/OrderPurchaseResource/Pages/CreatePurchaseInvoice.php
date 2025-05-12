<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data['purchaseInvoiceDetails']);
        return $data;
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (\Illuminate\Database\QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (ValidationException $e) {
            // This is already handled by Filament normally, but optional
            Notification::make()
                ->title('Validation Failed')
                ->body('Please check your inputs.')
                ->warning()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unexpected Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
