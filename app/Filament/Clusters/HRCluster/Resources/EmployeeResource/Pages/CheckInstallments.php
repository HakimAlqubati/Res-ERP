<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages;

use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Resources\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
class CheckInstallments extends Page
{

    protected static string $resource = EmployeeResource::class;
    protected static ?string $cluster = HRCluster::class;
    protected static string $view = 'filament.clusters.h-r-cluster.resources.employee-resource.pages.check-installments';

    public Employee $employee;

    public function mount(int $employeeId)
    {
        $this->employee = Employee::findOrFail($employeeId);
    }

    protected function getTableQuery()
    {
        $query = \App\Models\EmployeeAdvanceInstallment::where('employee_id', $this->employee->id);
    
        return $query->get();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('installment_number')->label('Installment Number')->sortable(),
            TextColumn::make('amount')->label('Amount')->money('MYR')->sortable(),
            TextColumn::make('due_date')->label('Due Date')->date()->sortable(),
        ];
    }
}
