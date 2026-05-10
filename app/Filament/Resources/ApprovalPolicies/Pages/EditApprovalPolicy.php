<?php

namespace App\Filament\Resources\ApprovalPolicies\Pages;

use App\Filament\Resources\ApprovalPolicies\ApprovalPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApprovalPolicy extends EditRecord
{
    protected static string $resource = ApprovalPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
