<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;

class ViewMonthSalary extends ViewRecord
{
    protected static string $resource = MonthSalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
            Actions\Action::make('exportExcel')->button()
            ->color('info')
            ->icon('heroicon-o-arrow-down-on-square-stack')
            ->action(function(){
                return MonthSalaryResource::exportExcel($this->record);
            }),
            Actions\Action::make('salary_slip')
            ->button()
            ->color('success')
            ->icon('heroicon-o-newspaper')
                ->form(function () {
                    $employeeIds = $this->record?->details->pluck('employee_id')->toArray();

                    return [
                        Hidden::make('month')->default($this->record?->month),
                        Select::make('employee_id')
                            ->required()
                            ->label('Employee')->searchable()
                            ->helperText('Search employee to get his payslip')
                            ->options(Employee::whereIn('id', $employeeIds)
                            ->select('name', 'id')->limit(2)->pluck('name', 'id')),
                    ];
                })
                ->action(function ($record, $data) {
                    $month = $data['month'];
                    $employeeId = $data['employee_id'];

                    // Generate the URL using the route with parameters
                    $url = url("/to_test_salary_slip/{$employeeId}/{$month}");

                    // Redirect to the generated URL
                    return redirect()->away($url);
                    
                }),
        ];
    }
}
