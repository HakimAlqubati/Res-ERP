<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;
use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;

class EmployeeOvertimeResource extends Resource
{
    protected static ?string $model = EmployeeOvertime::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    // public static function getCluster(): ?string
    // {
    //     if(isStuff()){
    //        return HRAttendanceReport::class;
    //    }
    //     return HRAttenanceCluster::class;
    // }
    
    // protected static ?string $label = 'Staff Overtime';
    // protected static ?string $pluralModelLabel = 'Staff Overtime';
    // protected static ?string $pluralLabel = 'Staff Overtime';

    public static function getModelLabel(): string
    {
        return isStuff() ? 'My Overtime': 'Staff Overtime';
    }
    public static function getPluralLabel(): ?string
    {
        return isStuff() ? 'My Overtime': 'Staff Overtime';
    }
    protected static ?int $navigationSort = 10;
    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                [

                    Fieldset::make()
                        ->columns(3)
                        ->label('Set branch employees & the Date')
                        ->schema([

                            Select::make('branch_id')->label('Branch')
                                ->helperText('Select to populate the branch employees')
                                ->required()->searchable()
                                ->live()
                                ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees = Employee::where('branch_id', $state)->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    $employeesWithOvertime = [];
                                    foreach ($employees as $employeeData) {
                                        // Fetch the employee using the employee_id from the provided data
                                        $employee = Employee::find($employeeData['employee_id']);

                                        // dd($employee);
                                        // Ensure the employee exists before calling the overtime calculation
                                        if ($employee) {
                                            // Calculate overtime for the specified date
                                            $overtimeResults = $employee->calculateEmployeeOvertime($employee, $get('date'));

                                            // Only add to the results if there are overtime records
                                            if (!empty($overtimeResults)) {
                                                $employeesWithOvertime[] = [
                                                    'employee_id' => $employee->id,
                                                    'overtime_details' => $overtimeResults,
                                                    'overtime_hours' => $overtimeResults[0]['overtime_hours'],
                                                    'start_time' => $overtimeResults[0]['overtime_start_time'],
                                                    'end_time' => $overtimeResults[0]['overtime_end_time'],
                                                ];
                                            }
                                        }
                                    }

                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                            'start_time' => $employee['start_time'],
                                            'end_time' => $employee['end_time'],

                                            'notes' => null,
                                            // 'hours' => 55,
                                            'hours' => $employee['overtime_hours'],
                                        ];
                                    }, $employeesWithOvertime));
                                })
                            ,
                            DatePicker::make('date')
                                ->label('Overtime Date')
                                ->default(date('Y-m-d'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees = Employee::where('branch_id', $get('branch_id'))->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    $employeesWithOvertime = [];
                                    foreach ($employees as $employeeData) {
                                        // Fetch the employee using the employee_id from the provided data
                                        $employee = Employee::find($employeeData['employee_id']);

                                        // Ensure the employee exists before calling the overtime calculation
                                        if ($employee) {
                                            // Calculate overtime for the specified date
                                            $overtimeResults = $employee->calculateEmployeeOvertime($employee, $state);

                                            // Only add to the results if there are overtime records
                                            if (!empty($overtimeResults)) {
                                                $employeesWithOvertime[] = [
                                                    'employee_id' => $employee->id,
                                                    'overtime_details' => $overtimeResults,
                                                    'overtime_hours' => $overtimeResults[0]['overtime_hours'],
                                                    // 'start_time' => $overtimeResults[0]['overtime_start_time'],
                                                    // 'end_time' => $overtimeResults[0]['overtime_end_time'],
                                                    'start_time' => $overtimeResults[0]['check_in_time'],
                                                    'end_time' => $overtimeResults[0]['check_out_time'],
                                                ];
                                            }
                                        }
                                    }

                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                            'start_time' => $employee['start_time'],
                                            'end_time' => $employee['end_time'],

                                            'notes' => null,
                                            'hours' => $employee['overtime_hours'],
                                        ];
                                    }, $employeesWithOvertime));
                                })
                          
                        ]),
                 
                    Repeater::make('employees')
                        ->label('')
                        ->required()
                        ->columnSpanFull()
                        ->schema(

                            [
                                Grid::make()->columns(4)->schema([
                                    Select::make('employee_id')->live()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule,  Get $get,$state) {
                                            return $rule->where('employee_id',$state)
                                        ->where('date',$get('../../date'))
                                        ;
                                        }
                                        )
                                        ->relationship('employee', 'name')
                                        ->validationMessages([
                                            'unique'=>'This overtime has been recorded'
                                            ])
                                        ->required(),
                                    TimePicker::make('start_time')->disabled()
                                    ->dehydrated()
                                        ->label('Checkin')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $end = Carbon::parse($get('end_time'));
                                            $start = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        })
                                    ,

                                    TimePicker::make('end_time')->disabled()
                                    ->dehydrated()
                                        ->label('Checkout')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $start = Carbon::parse($get('start_time'));
                                            $end = Carbon::parse($state); // Parse the start time

                                            // Calculate the difference in hours
                                            $hours = round($start->diffInHours($end), 1);

                                            // Set the result in the hours_as_default field
                                            $set('hours', $hours);
                                        })
                                    ,
                                    TextInput::make('hours')->label('Overtime Hours')->required()->minValue(0.5),
                                ]),
                                Grid::make()->columns(2)->schema([
                                    TextInput::make('notes')
                                        ->label('Notes')->columnSpanFull()
                                        ->nullable(),
                                ]),
                            ])

                    ,
                ]
            )
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
        ->striped()
        ->defaultSort('id','desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('')
                    ->sortable()
                    ->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault:true),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault:false),

                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault:false),

                TextColumn::make('start_time')
                    ->label('Checkin')
                    ->sortable()->toggleable(isToggledHiddenByDefault:true),

                TextColumn::make('end_time')
                    ->label('Checkout')
                    ->sortable()->toggleable(isToggledHiddenByDefault:true),

                TextColumn::make('hours')
                    ->label('Hours')
                    ->sortable()->toggleable(isToggledHiddenByDefault:false),
                IconColumn::make('approved')->toggleable(isToggledHiddenByDefault:false)
                    ->boolean()->alignCenter(true)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                TextColumn::make('approvedBy.name')
                    ->label('Approved by')
                    ->wrap()->toggleable(isToggledHiddenByDefault:false)
                ,
                TextColumn::make('approved_at')->wrap()
                    ->label('Approved at')->toggleable(isToggledHiddenByDefault:false)
                ,

            ])
            ->selectable()
            ->filters([
                Tables\Filters\TrashedFilter::make()->visible(fn():bool=> isSuperAdmin())
                
                ,
                SelectFilter::make('branch_id')
                    ->label('Branch')->multiple()
                    ->options(Branch::where('active', 1)->get()->pluck('name', 'id')),
                SelectFilter::make('employee_id')
                    ->searchable()
                    ->multiple()
                    ->label('Employee')
                    ->options(function (Get $get) {
                        return Employee::query()
                        // ->where('branch_id', $get('branch_id'))
                            ->pluck('name', 'id');
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('Edit')->visible(fn():bool=>(isSuperAdmin() || isBranchManager()))
                ->form(function($record){
                    return [
                        TextInput::make('hours')->default($record->hours),
                    ];
                })->action(function($record,$data){
// dd($data['hours'],$data,$record);
                    return $record->update(['hours'=>$data['hours']]);
                }),
                Action::make('Approve')
                    ->databaseTransaction()
                    ->label(function ($record) {
                        if ($record->approved == 1) {
                            return 'Rollback approved';
                        } else {
                            return 'Approve';
                        }
                    })
                    ->icon(function ($record) {
                        if ($record->approved == 1) {
                            return 'heroicon-o-x-mark';
                        } else {
                            return 'heroicon-o-check-badge';
                        }
                    })->color(function ($record) {
                    if ($record->approved == 1) {
                        return 'gray';
                    } else {
                        return 'info';
                    }
                })
                    ->button()
                    ->requiresConfirmation()
                    ->size(ActionSize::Small)
                    ->hidden(function ($record) {
                        // if ($record->approved == 1) {
                        //     return true;
                        // }
                        if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                            return false;
                        }
                        return true;
                    })
                    ->action(function (Model $record) {
                        if ($record->approved == 1) {
                            $record->update(['approved' => 0, 'approved_by' => null,'approved_at'=> null]);
                        } else {
                            $record->update(['approved' => 1, 'approved_by' => auth()->user()->id,'approved_at'=> now()]);
                        }
                    }),

                    ActionGroup::make([
                        Tables\Actions\DeleteAction::make(),
                        Tables\Actions\ForceDeleteAction::make()->visible(fn():bool=> (isSuperAdmin())),
                        Tables\Actions\RestoreAction::make()->visible(fn():bool=> (isSuperAdmin())),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()->visible(fn():bool=> (isSuperAdmin())),
                    Tables\Actions\RestoreBulkAction::make()->visible(fn():bool=> (isSuperAdmin())),
                    BulkAction::make('Approve')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-badge')
                        ->action(fn(Collection $records) => $records->each->update(['approved' => 1, 'approved_by' => auth()->user()->id,'approved_at'=> now()]))
                        ->hidden(function () {
                            if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                                return false;
                            }
                            return true;
                        })
                    ,
                    BulkAction::make('Rollback approved')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->action(fn(Collection $records) => $records->each->update(['approved' => 0, 'approved_by' => null,'approved_at'=> null]))
                        ->hidden(function () {
                            if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                                return false;
                            }
                            return true;
                        })
                    ,
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeOvertimes::route('/'),
            'create' => Pages\CreateEmployeeOvertime::route('/create'),
            // 'edit' => Pages\EditEmployeeOvertime::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
        return static::can('create');
    }

    
    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    
    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

}
