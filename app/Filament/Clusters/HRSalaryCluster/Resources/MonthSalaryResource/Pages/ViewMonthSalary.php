<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
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
            Action::make('exportExcel')->button()
                ->color('info')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->action(function () {
                    return MonthSalaryResource::exportExcel($this->record);
                }),
            Action::make('bulk_salary_slip')
                ->button()->label('Bulk salary slip')
                ->color('primary') // Use primary color for bulk action
                ->icon('heroicon-o-archive-box-arrow-down') // Icon for bulk salary slips                
                ->schema(function ($record) {
                    return MonthSalaryResource::bulkSalarySlipForm($record);
                })

                ->action(function ($record, $data) {
                    $employeeIds = $data['employee_ids'];
                    return MonthSalaryResource::bulkSalarySlip($record,$employeeIds);
                }),
            Action::make('salary_slip')
                ->button()->label('Salary slip')
                ->color('success') // Use secondary color for single employee action
                ->icon('heroicon-o-document-arrow-down') // Icon for employee salary slip

                ->schema(function ($record) {
                    $employeeIds = $record?->details->pluck('employee_id')->toArray();

                    return [
                        Hidden::make('month')->default($record?->month),
                        Select::make('employee_id')
                            ->required()
                            ->label('Employee')
                            ->searchable()
                            ->columns(2)
                            ->options(function () use ($employeeIds) {
                                return Employee::whereIn('id', $employeeIds)
                                    ->select('name', 'id')
                                    ->pluck('name', 'id');
                            })
                            ->allowHtml(),


                    ];
                })
                ->action(function ($record, $data) {
                    $employeeId = $data['employee_id'];
                    return generateSalarySlipPdf_($employeeId, $record->id);
                }),
        ];
    }
}
