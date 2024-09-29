<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthSalary extends CreateRecord
{
    protected static string $resource = MonthSalaryResource::class;
}
