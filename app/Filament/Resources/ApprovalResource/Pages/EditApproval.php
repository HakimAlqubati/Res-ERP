<?php

namespace App\Filament\Resources\ApprovalResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApproval extends EditRecord
{
    protected static string $resource = ApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
