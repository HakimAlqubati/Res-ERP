<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;
}
