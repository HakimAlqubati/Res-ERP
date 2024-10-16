<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveBalance extends EditRecord
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
