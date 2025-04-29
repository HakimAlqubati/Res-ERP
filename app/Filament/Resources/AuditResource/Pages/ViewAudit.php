<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;
    protected static string $view = 'filament.pages.audit-logs.audit-logs';
    protected function getHeaderActions(): array
    {
        return [
          
        ];
    }
}
