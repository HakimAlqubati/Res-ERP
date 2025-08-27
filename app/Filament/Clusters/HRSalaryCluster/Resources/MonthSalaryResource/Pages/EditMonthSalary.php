<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthSalary extends EditRecord
{
    protected static string $resource = MonthSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
