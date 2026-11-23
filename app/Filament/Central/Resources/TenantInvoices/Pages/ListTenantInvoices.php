<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices\Pages;

use App\Filament\Central\Resources\TenantInvoices\TenantInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantInvoices extends ListRecords
{
    protected static string $resource = TenantInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
