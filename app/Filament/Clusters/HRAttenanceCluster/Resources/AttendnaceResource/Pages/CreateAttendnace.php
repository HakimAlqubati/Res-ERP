<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendnace extends CreateRecord
{
    protected static string $resource = AttendnaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        $data['day'] = \Carbon\Carbon::parse($data['check_date'])->format('l');
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
