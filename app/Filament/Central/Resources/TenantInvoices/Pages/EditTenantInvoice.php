<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices\Pages;

use App\Filament\Central\Resources\TenantInvoices\TenantInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantInvoice extends EditRecord
{
    protected static string $resource = TenantInvoiceResource::class;

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
}
