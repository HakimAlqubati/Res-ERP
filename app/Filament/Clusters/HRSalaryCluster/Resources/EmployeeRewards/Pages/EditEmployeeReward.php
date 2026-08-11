<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\EmployeeRewardResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeReward extends EditRecord
{
    protected static string $resource = EmployeeRewardResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        abort_if($this->getRecord()->status === \App\Models\EmployeeReward::STATUS_APPROVED, 403, 'This reward has already been approved and cannot be edited.');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
