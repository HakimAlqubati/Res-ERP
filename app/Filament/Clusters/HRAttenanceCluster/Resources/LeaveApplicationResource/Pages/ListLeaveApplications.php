<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource;
use App\Models\LeaveApplication;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveApplications extends ListRecords
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            LeaveApplication::STATUS_LABEL_PENDING => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', LeaveApplication::STATUS_PENDING)),
            LeaveApplication::STATUS_LABEL_APPROVED => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', LeaveApplication::STATUS_APPROVED)),
            LeaveApplication::STATUS_LABEL_REJECTED => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', LeaveApplication::STATUS_REJECTED)),
        ];
    }
}
