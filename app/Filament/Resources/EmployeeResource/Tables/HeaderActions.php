<?php

namespace App\Filament\Resources\EmployeeResource\Tables;

use App\Exports\EmployeesExport;
use App\Imports\EmployeeImport;
use App\Imports\EmployeeEwalletImport;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Throwable;

class HeaderActions
{
    public static function actions()
    {
        return [
            Action::make('export_employees')
                ->label(__('lang.export_to_excel'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action(function () {
                    $data = Employee::where('active', 1)
                        ->forBranchManager()
                        ->with(['branch', 'manager', 'periods', 'serviceTermination', 'employeeType'])
                        ->get();

                    return Excel::download(new EmployeesExport($data), 'employees.xlsx');
                }),
            Action::make('export_employees_pdf')
                ->label(__('lang.print_as_pdf'))
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->action(function () {
                    $data = Employee::where('active', 1)
                        ->forBranchManager()
                        ->with(['branch', 'manager', 'periods', 'serviceTermination', 'employeeType'])
                        ->get();
                    $pdf = PDF::loadView('export.reports.hr.employees.export-employees-as-pdf', ['data' => $data]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'employees.pdf');
                }),

            Action::make('import_employees')
                ->label(__('lang.import_from_excel'))
                ->icon('heroicon-o-document-arrow-up')
                ->visible(fn (): bool => isSystemManager() || isSuperAdmin())
                ->schema([
                    FileUpload::make('file')
                        ->label(__('lang.select_excel_file')),
                ])
                // ->extraModalFooterActions([
                //     Action::make('downloadexcel')->label(__('Download Example File'))
                //         ->icon('heroicon-o-arrow-down-on-square-stack')
                //         ->url(asset('data/sample_file_imports/Sample import file.xlsx')) // URL to the existing file
                //         ->openUrlInNewTab(),
                // ])
                ->color('success')
                // ->iconButton(Heroicon::AcademicCap)
                ->action(function ($data) {

                    $file = 'public/'.$data['file'];
                    try {
                        // Create an instance of the import class
                        $import = new EmployeeImport;

                        // Import the file
                        Excel::import($import, $file);

                        // Check the result and show the appropriate notification
                        if ($import->getSuccessfulImportsCount() > 0) {
                            showSuccessNotifiMessage("Employees imported successfully {$import->getSuccessfulImportsCount()} rows added.");
                        } else {
                            showWarningNotifiMessage('No employees were added. Please check your file.');
                        }
                    } catch (Throwable $th) {
                        throw $th;
                        showWarningNotifiMessage('Error importing employees');
                    }
                }),

            Action::make('import_ewallet_data')
                ->label(__('Import E-Wallet Data'))
                ->icon('heroicon-o-credit-card')
                ->visible(fn (): bool => isHakimOrAdel())
                ->schema([
                    FileUpload::make('file')
                        ->label(__('lang.select_excel_file')),
                ])
                ->color('info')
                ->action(function ($data) {
                    $file = storage_path('app/public/'.$data['file']);
                    try {
                        $import = new EmployeeEwalletImport;
                        Excel::import($import, $file);

                        if ($import->getUpdatedCount() > 0) {
                            $message = "E-Wallet data imported successfully. {$import->getUpdatedCount()} employees updated.";
                            if ($import->getSkippedCount() > 0) {
                                $message .= " {$import->getSkippedCount()} rows skipped.";
                            }
                            showSuccessNotifiMessage($message);
                        } else {
                            showWarningNotifiMessage('No employees were updated. Please check your file.');
                        }

                        if ($import->hasErrors()) {
                            $errors = implode("\n", array_slice($import->getImportErrors(), 0, 10));
                            Notification::make()
                                ->title('Import Warnings')
                                ->body($errors)
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } catch (Throwable $th) {
                        throw $th;
                    }
                }),
        ];
    }
}
