<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Models\ServiceRequestLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function afterSave(): void
    {

        $changedFields = $this->record->getChanges();
        // dd($this->record->getPreviousValue(), $this->record->description);
        foreach ($changedFields as $key => $value) {
            if (isset($key) && $key != 'updated_at') {
                $this->record->logs()->create([
                    'created_by' => auth()->user()->id,
                    'description' => 'field ' . $key . ' has been updated to ' . $value,
                    'log_type' => ServiceRequestLog::LOG_TYPE_UPDATED,
                ]);
            }
        }

    }
}
