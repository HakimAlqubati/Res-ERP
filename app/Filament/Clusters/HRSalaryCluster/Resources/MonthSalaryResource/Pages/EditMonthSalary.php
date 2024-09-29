<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthSalary extends EditRecord
{
    protected static string $resource = MonthSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
