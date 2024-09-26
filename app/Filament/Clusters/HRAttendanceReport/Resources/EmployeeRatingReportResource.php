<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\ListEmployeeRatingReports2;
use App\Models\Attendance;
use App\Models\TaskRating;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeRatingReportResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $slug = 'rating-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Task performance rating';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->emptyStateHeading('No data')
            ->columns([
                TextColumn::make('employee_no')->label('Employee no')->searchable(isIndividual: true, isGlobal: true)->alignCenter(false),
                TextColumn::make('employee_name')->label('Employee name')->searchable(isIndividual: true, isGlobal: true),
                TextColumn::make('count_task')->label('Number of tasks')->alignCenter(true),

                TextColumn::make('rating_value')->label('Rating value')->alignCenter(true),

            ])
            ->filters([
                // SelectFilter::make('employee_id')->label('Employee')->options(Employee::where('active', 1)
                //     ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                // Filter::make('date_range')
                //     ->form([
                //         DatePicker::make('start_date')
                //             ->label('Start Date'),
                //         DatePicker::make('end_date')
                //             ->label('End Date'),
                //     ])

            ], FiltersLayout::AboveContent)
            ->actions([
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListEmployeeRatingReports2::route('/'),
            // 'create' => Pages\CreateEmployeeAttednaceReport::route('/create'),
            // 'edit' => Pages\EditEmployeeAttednaceReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = TaskRating::select('hr_employees.id as employee_id', DB::raw('SUM(hr_task_rating.rating_value) as rating_value')
            , DB::raw('count(hr_task_rating.task_id) as count_task')
            , 'hr_employees.name as employee_name', 'hr_employees.employee_no as employee_no')
            ->join('hr_employees', 'hr_task_rating.employee_id', '=', 'hr_employees.id')
            ->groupBy('hr_employees.id', 'hr_employees.name', 'hr_employees.employee_no');
        return $query;
        // $query = static::getModel()::query();
        $query = TaskRating::query()
            ->select(
                'hr_employees.id AS employee_id',
                'hr_employees.name AS employee_name',
                'hr_employees.employee_no AS employee_no',
                'hr_tasks.id AS task_id',
                'hr_task_rating.rating_value AS rating_value'
            )
            ->join('hr_employees', 'hr_task_rating.employee_id', '=', 'hr_employees.id')
            ->join('hr_tasks', 'hr_tasks.assigned_to', '=', 'hr_employees.id')
            ->groupBy(
                'hr_employees.id',
                'hr_tasks.id',
                'hr_task_rating.rating_value',
                'hr_employees.name',
                'hr_employees.employee_no',
            );
        return $query;

    }
}
