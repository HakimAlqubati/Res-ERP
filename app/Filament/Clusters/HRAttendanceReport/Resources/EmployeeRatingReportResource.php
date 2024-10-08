<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource\Pages\ViewDetails;
use App\Filament\Clusters\HRAttendanceReport\Resources\ListEmployeeRatingReports2;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\TaskRating;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
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
            // ->recordAction(function($record){
            //     return 'rating-report/view?employee_id=' . $record->employee_id;
            // })
            ->recordUrl(function($record){
                return 'rating-report/view?employee_id=' . $record->employee_id;
            })
            ->columns([
                TextColumn::make('employee_no')->label('Employee no')->searchable(isIndividual: true, isGlobal: true)->alignCenter(false),
                TextColumn::make('employee_name')->label('Employee name')->searchable(isIndividual: true, isGlobal: true),
                TextColumn::make('count_task')->label('Number of tasks')->alignCenter(true),

                TextColumn::make('rating_value')->label('Rating value')->alignCenter(true)->action(function () {
                    dd('hi');
                }),
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                // SelectFilter::make('employee_id')->label('Employee')
                // // ->options(Employee::where('active', 1)
                // //         ->select('name', 'id')->get()->pluck('name', 'id'))
                //     ->options(fn(Get $get): Collection => Employee::query()
                //             ->where('active', 1)
                //             ->where('branch_id', $get('branch_id'))
                //             ->pluck('name', 'id'))
                //     ->searchable(),

            ], FiltersLayout::AboveContent)
            ->actions([
                Action::make('ViewDetails')->url(function ($record) {

                    return 'rating-report/view?employee_id=' . $record->employee_id;
                }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'view' => ViewDetails::route('/view'),
            // 'view-detail' => ViewReportDetail::route('/{record}/detail'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = TaskRating::select(
            DB::raw('SUM(hr_task_rating.rating_value) as rating_value'),
            DB::raw('count(hr_task_rating.task_id) as count_task'),
            'hr_employees.id as employee_id', 'hr_employees.name as employee_name', 'hr_employees.employee_no as employee_no', 'hr_employees.branch_id as branch_id')
            ->join('hr_employees', 'hr_task_rating.employee_id', '=', 'hr_employees.id');
        if (isBranchManager()) {
            $query->where('hr_employees.branch_id', auth()->user()->branch_id);
        }
        $query = $query->groupBy('hr_employees.id', 'hr_employees.name', 'hr_employees.employee_no', 'hr_employees.branch_id')

        ;
// dd($query->get());
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
        dd($query);
        return $query;

    }
}
