<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\MonthSalary;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class EmployeeSearchWidget extends BaseWidget
{
    // protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getTableHeading(): string
    {
        return 'My Salaries'; // Set your desired heading here
    }

    public static function canView(): bool
    {
     if(isStuff()){
        return true;
     }
     return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function(){
                $query = MonthSalary::query()->with('details')
                ->with(['details' => function ($q) {
                    $q->where('employee_id', auth()->user()->employee->id);
                }]) // Eager load 'details' with a condition
                ->whereHas('details', function ($q) {
                    $q->where('employee_id', auth()->user()->employee->id);
                }) ;

                $query->where('branch_id',auth()->user()->employee->branch_id);
                return $query;
            })
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
           ->columns([
            TextColumn::make('month'),
            TextColumn::make('details.net_salary')->label('Net salary')
           ])
            ->striped()
            ->filters([
            
            ])
            ->actions([
            Action::make('salarySlip')->button()->color('success')
            ->action(function($record){
                $employeeId = auth()->user()->employee->id;
                return generateSalarySlipPdf_($employeeId, $record->id);
            })
            ])
        ;
    }

}
