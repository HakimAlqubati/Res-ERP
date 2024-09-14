<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource;
use App\Models\LeaveApplication;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['from_date'] && $data['to_date']) {
            $daysDiff = now()->parse($data['from_date'])->diffInDays(now()->parse($data['to_date'])) + 1;
            $data['days_count'] = $daysDiff;
        } else {
            $data['days_count'] = 0;
        }

        $data['created_by'] = auth()->user()->id;
        $data['status'] = LeaveApplication::STATUS_PENDING;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}
