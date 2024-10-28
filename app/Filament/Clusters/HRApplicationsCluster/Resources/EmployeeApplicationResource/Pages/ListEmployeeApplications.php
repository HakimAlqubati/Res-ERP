<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplication;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeeApplications extends ListRecords
{
    protected static string $resource = EmployeeApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            EmployeeApplication::APPLICATION_TYPE_NAMES[EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
                ->icon('heroicon-o-finger-print')
                ->badge(EmployeeApplication::query()->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST)->count())
                ->badgeColor('warning')
                ,
            EmployeeApplication::APPLICATION_TYPE_NAMES[EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
                ->icon('heroicon-m-finger-print')
                ->badge(EmployeeApplication::query()->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST)->count())
                ->badgeColor('warning')
                ,
            EmployeeApplication::APPLICATION_TYPE_NAMES[EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST))
                ->icon('heroicon-m-banknotes')
                ->badge(EmployeeApplication::query()->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST)->count())
                ->badgeColor('warning')
                ,
            EmployeeApplication::APPLICATION_TYPE_NAMES[EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST))
                ->icon('heroicon-o-clock')
                ->badge(EmployeeApplication::query()->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST)->count())
                ->badgeColor('warning')
                ,

        ];
    }

    public function getModelLabel(): ?string
    {
        return 'Request';
    }
}
