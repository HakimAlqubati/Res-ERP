<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Table\EmployeeApplicationTable;
use App\Models\EmployeeApplicationV2;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeeApplications extends ListRecords
{
    protected static string $resource = EmployeeApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
                ->icon('heroicon-o-finger-print')
                ->badge(EmployeeApplicationV2::query()->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST)->count())
                ->badgeColor('warning'),
            EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
                ->icon('heroicon-m-finger-print')
                ->badge(EmployeeApplicationV2::query()->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST)->count())
                ->badgeColor('warning'),
            EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST))
                ->icon('heroicon-m-banknotes')
                ->badge(EmployeeApplicationV2::query()->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST)->count())
                ->badgeColor('warning'),
            EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST] => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST))
                ->icon('heroicon-o-clock')
                ->badge(EmployeeApplicationV2::query()->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)->count())
                ->badgeColor('warning'),

        ];
    }

    public function getModelLabel(): ?string
    {
        return 'Request';
    }

    

    public function table(Table $table): Table
    {
        // هنا نرسل التاب الحالي للـ Table Helper
        return EmployeeApplicationTable::configure(
            table: $table,
            activeTab: $this->activeTab,   // بدون refresh – هذه Livewire state
        );
    }
}
