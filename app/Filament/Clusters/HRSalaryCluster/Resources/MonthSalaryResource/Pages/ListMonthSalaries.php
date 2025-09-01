<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthSalaries extends ListRecords
{
    protected static string $resource = MonthSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->disabled(fn(): bool => Attendance::query()->count() === 0)
                ->label(fn(): string => Attendance::query()->count() == 0 ? 'No attendance data' : 'New Payroll'),
        ];
    }
}
