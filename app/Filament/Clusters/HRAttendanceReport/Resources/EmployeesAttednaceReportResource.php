<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeesAttednaceReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $slug = 'employees-attendance-report';
    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Attendance by branch';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No data')
            ->columns([
                TextColumn::make('employee_name')->label('Employee name'),
                TextColumn::make('employee_no')->label('Employee number'),
                TextColumn::make('department_name')->label('Department'),
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                Filter::make('filter_date')->label('')->form([
                    DatePicker::make('date')
                        ->label('Date')->default(\Carbon\Carbon::now()->startOfMonth()->toDateString()),
                ]),

            ], FiltersLayout::AboveContent)
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeesAttednaceReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // $query = static::getModel()::query();
        // $query->select('employee_id');
        // return $query;
        $query = Employee::query();
        $query->select('hr_employees.id as employee_id','hr_employees.name as employee_name','hr_employees.employee_no as employee_no','departments.name as department_name')
        ->join('departments','hr_employees.department_id','=','departments.id')
        ;
        return $query;
    }

}
