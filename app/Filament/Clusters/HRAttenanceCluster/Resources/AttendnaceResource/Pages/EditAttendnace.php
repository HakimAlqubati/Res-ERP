<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use Carbon\Carbon;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendnace extends EditRecord
{
    protected static string $resource = AttendnaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->user()->id;
        $data['day'] = Carbon::parse($data['check_date'])->format('l');
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
