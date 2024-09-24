<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\ListEmployeeRatingReports2;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('employee_no')->label('Employee no')->searchable(isIndividual: true, isGlobal: true)->alignCenter(true),
                TextColumn::make('employee_name')->label('Employee name')->searchable(isIndividual: true, isGlobal: true),
                TextColumn::make('branch_name')->label('Branch')->alignCenter(true),
                TextColumn::make('task_id')->label('Task id')->alignCenter(true),
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
        // $query = static::getModel()::query();
        $query = Employee::query()
            ->select(
                'hr_employees.id AS employee_id',
                'hr_employees.name AS employee_name',
                'hr_employees.employee_no AS employee_no',
                'branches.name AS branch_name',
                'hr_tasks.id AS task_id',
                'hr_task_rating.rating_value AS rating_value',
                // 'products.id AS product_id',
                // 'branches.name AS branch',
                // 'units.name AS unit',
                // DB::raw('SUM(orders_details.available_quantity) AS quantity'),

            )
            ->join('branches', 'hr_employees.branch_id', '=', 'branches.id')
            ->join('hr_tasks', 'hr_employees.id', '=', 'hr_tasks.assigned_to')
            ->join('hr_task_rating', 'hr_employees.id', '=', 'hr_task_rating.task_user_id_assigned')

        // ->whereNull('hr_employees.deleted_at')
        // ->where('products.id', $product_id)
        // ->groupBy('orders.branch_id', 'products.name', 'products.id', 'branches.name', 'units.name', 'orders_details.price')
        ;
        return $query;

    }
}
