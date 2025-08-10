<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;

class RunPayroll extends Page
{
    protected static string $resource = PayrollResource::class; 
    protected static string $view = 'filament.clusters.h-r-salary-cluster.resources.payroll-resource.pages.run-payroll';
    protected static ?string $title = 'Run Payroll';

    
    // public ?array $data = [
    //     'year' => null,
    //     'month' => null,
    //     'branch_id' => null,
    //     'daily_rate_method' => 'working_days',
    //     'daily_hours' => 8,
    //     'overtime_multiplier' => 1.5,
    //     'employee_ids' => [],
    // ];

    // protected function getFormSchema(): array
    // {
    //     return [
    //         Grid::make(3)->schema([
    //             TextInput::make('data.year')->label('Year')->required(),
    //             TextInput::make('data.month')->label('Month')->required(),
    //             Select::make('data.branch_id')->label('Branch')
    //                 ->relationship('branch','name')->searchable()->preload(),
    //             Select::make('data.daily_rate_method')->label('Daily rate method')
    //                 ->options([
    //                     'by30days' => 'By30Days',
    //                     'bymonthdays' => 'ByMonthDays',
    //                     'working_days' => 'ByWorkingDays',
    //                 ])->default('working_days'),
    //             TextInput::make('data.daily_hours')->numeric()->minValue(1)->default(8),
    //             TextInput::make('data.overtime_multiplier')->numeric()->minValue(1)->default(1.5),
    //             Select::make('data.employee_ids')->label('Employees')
    //                 ->relationship('employee','name')->multiple()->searchable()->preload(),
    //         ]),
    //     ];
    // }

    // public function run(): void
    // {
    //     // Dispatch a queued simulation job with $this->data
    //     // ...
    //     // Notification::make()->title('Simulation started')->success()->send();
    // }
}
