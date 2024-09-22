<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeOvertime extends CreateRecord
{
    protected static string $resource = EmployeeOvertimeResource::class;
}
