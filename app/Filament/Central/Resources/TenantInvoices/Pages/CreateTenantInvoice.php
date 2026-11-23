<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices\Pages;

use App\Filament\Central\Resources\TenantInvoices\TenantInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantInvoice extends CreateRecord
{
    protected static string $resource = TenantInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
