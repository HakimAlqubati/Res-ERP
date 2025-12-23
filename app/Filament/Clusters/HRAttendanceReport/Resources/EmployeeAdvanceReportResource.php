<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Actions\Action;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRTaskReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\EmployeeApplicationV2;
use App\Models\Task;
use App\Models\TaskLog;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceReportResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $slug = 'employee-advance-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTaskReport::class;
    protected static ?string $label = 'Employee advance';
        // protected static bool $shouldRegisterNavigation = false;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    
    public static function table(Table $table): Table
    {

        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('No data')->striped()
            ->columns([
                TextColumn::make('employee_id')->label('Employee id')->searchable(isGlobal: true)->alignCenter( true)->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('employee_no')->label('Emp.No.')->searchable(isGlobal: true)->alignCenter(true)
                ,
                
                TextColumn::make('employee_name')->label('Name')->wrap(true)->limit(15),
                TextColumn::make('advance_id')->label('Advance id')->alignCenter(true)->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('Amount')->label('Amount')->alignCenter(true)
                ->getStateUsing(function($record){
                    $employee = Employee::find($record->employee_id);
                  $advance =  $employee->approved_advance_application->where('id',$record->advance_id)->first()?? null;
                  
                  if($advance){
                        return $advance['details']['detail_advance_amount'] ?? 0;
                    }
                })
                ,
                
                TextColumn::make('detail_number_of_months_of_deduction')->alignCenter(true)
                ->label('#Inst.')
                ->getStateUsing(function($record){
                    $employee = Employee::find($record->employee_id);
                  $advance =  $employee->approved_advance_application->where('id',$record->advance_id)->first()?? null;
                //   dd($advance['details']); 
                  if($advance){
                        return $advance['details']['detail_number_of_months_of_deduction'] ?? 0;
                    }
                })
                ,
                TextColumn::make('detail_monthly_deduction_amount')
                ->label('Inst./Amt.')
                ->alignCenter(true)
                ->getStateUsing(function($record){
                    $employee = Employee::find($record->employee_id);
                  $advance =  $employee->approved_advance_application->where('id',$record->advance_id)->first()?? null;
                  if($advance){
                        return $advance['details']['detail_monthly_deduction_amount'] ?? 0;
                    }
                })
                ,
                TextColumn::make('paid')->alignCenter(true)
                ->label('#Paid')
                ->getStateUsing(function($record){
                  $employee = Employee::find($record->employee_id);
                  $advance =  $employee->approved_advance_application->where('id',$record->advance_id)->first()?? null;
                  if($advance){
                        return $advance['paid']?? null;
                    }
                })
                ,
                TextColumn::make('Due Date')->alignCenter(true)
                ->getStateUsing(function($record){
                    $employee = Employee::find($record->employee_id);
                  $advance =  $employee->approved_advance_application->where('id',$record->advance_id)->first()?? null;
                //   dd($advance); 
                  if($advance){
                        return $advance['details']['detail_deduction_ends_at'] ?? 0;
                    }
                })
                ,
                

            ])
            ->filters([
                        SelectFilter::make('hr_employees.branch_id')->placeholder('Branch')
                        ->label('Branch')
                        ->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                        SelectFilter::make('hr_employees.id')
                        ->placeholder('Employee')
                        ->label('Employee')
                        ->getSearchResultsUsing(fn (string $search): array => Employee::where('name', 'like', "%{$search}%")->limit(5)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Employee::find($value)?->name)
                        ->searchable(),
                        
            ], FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('details')->button()
                
                ->schema(function ($record) {
                    // Retrieve installments for the given advance_id
                    $installments = EmployeeApplicationV2::find($record->advance_id)->advanceInstallments;
        
                  
            // Define the Repeater component
            return [
                Repeater::make('installments')->label('')
                    ->schema([
                        TextInput::make('installment_amount')
                            
                            ->disabled(),

                        DatePicker::make('due_date')
                            
                            ->disabled(),

                        Checkbox::make('is_paid')
                            
                            ->disabled()->inline(false),

                        DatePicker::make('paid_date')
                            
                            ->disabled(),
                    ])
                    ->defaultItems(count($installments)) // Set default rows based on installment count
                   
                    ->columns(4) // Number of columns per row
                    ->default(array_map(function ($installment) {
                        // dd($installment['installment_amount']);
                        return [
                            'installment_amount' => $installment['installment_amount'],
                            'due_date' => $installment['due_date'],
                            'is_paid' => (bool) $installment['is_paid'],
                            'paid_date' => $installment['paid_date'],
                        ];
                    }, $installments->toArray())),
            ];
                })->modalHeading('Installment Details')
                ->disabledForm()->modalSubmitAction(false)->modalCancelAction(false)
            ])
            ;
    }
 
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAdvanceReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Employee::select(
            'hr_employees.branch_id as branch_id','hr_employees.id as employee_id','hr_employees.employee_no as employee_no',
            'hr_employees.name as employee_name',
            'hr_employee_applications.id as advance_id',
            // DB::raw('SUM(TIME_TO_SEC(hr_task_logs.total_hours_taken)) as total_spent_seconds')

        )->join('hr_employee_applications',  'hr_employees.id','=','hr_employee_applications.employee_id')
        ->where('hr_employee_applications.application_type_id',3)
        ->where('hr_employee_applications.status',EmployeeApplicationV2::STATUS_APPROVED)

        // ->where('hr_task_logs.log_type', TaskLog::TYPE_MOVED)
        // ->whereJsonContains('hr_task_logs.details->to', Task::STATUS_CLOSED, '!=')
        ;

        $query = $query->groupBy('hr_employees.id','hr_employees.branch_id','hr_employees.employee_no','hr_employees.name','hr_employee_applications.id');

        // dd($query->toSql());
        return $query;
        // return $query->orderBy('hr_tasks.id','desc');
        

    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
