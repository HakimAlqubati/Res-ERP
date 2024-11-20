<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;

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
            Actions\Action::make('exportExcel')->button()
            ->color('info')
            ->icon('heroicon-o-arrow-down-on-square-stack')
            ->action(function(){
                return MonthSalaryResource::exportExcel($this->record);
            }),
            Actions\Action::make('bulk_salary_slip')
            ->button()->label('Bulk salary slip')
            ->color('primary') // Use primary color for bulk action
            ->icon('heroicon-o-archive-box-arrow-down') // Icon for bulk salary slips                
            ->form(function ($record) {
                $employeeIds = $record?->details->pluck('employee_id')->toArray();
                $employeeOptions = Employee::whereIn('id', $employeeIds)
                    ->select('name', 'id')
                    ->pluck('name', 'id')
                    ->toArray();
        
                return [
                    Hidden::make('month')->default($record?->month),
                    CheckboxList::make('employee_ids')
                        ->required()->columns(3)
                        ->label('Select Employees')
                        ->options($employeeOptions) // Use the employee options
                        ->default(array_keys($employeeOptions)) // Pre-check all employees
                        ->helperText('Select up to 10 employees to generate their payslips'),
                ];
            })
        
            ->action(function ($record, $data) {
                $employeeIds = $data['employee_ids'];
                // dd($employeeIds);
                $zipFileName = 'salary_slips.zip';
                $zipFilePath = storage_path('app/public/' . $zipFileName);
                $zip = new \ZipArchive();

                if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    foreach ($employeeIds as $employeeId) {
                        $pdfContent = generateSalarySlipPdf($employeeId, $record->id); // Generate the PDF content
                        $employeeName = Employee::find($employeeId)->name;
                        $fileName = 'salary-slip_' . $employeeName . '.pdf';

                        // Add the PDF content to the ZIP archive
                        $zip->addFromString($fileName, $pdfContent);
                    }

                    // Close the ZIP archive
                    $zip->close();

                    // Provide the ZIP file for download
                    return response()->download($zipFilePath)->deleteFileAfterSend(true);
                } else {
                    throw new \Exception('Could not create ZIP file.');
                }

            }),
        Actions\Action::make('salary_slip')
            ->button()->label('Salary slip')
            ->color('success') // Use secondary color for single employee action
          ->icon('heroicon-o-document-arrow-down') // Icon for employee salary slip

            ->form(function ($record) {
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
