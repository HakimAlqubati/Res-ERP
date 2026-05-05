<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource;
use App\Models\ApplicationTransaction;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveBalance extends CreateRecord
{
    protected static string $resource = LeaveBalanceResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
       
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
 
}
