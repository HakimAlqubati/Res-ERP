<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FakeModelReports\AttendanceByBranch;
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
    protected static ?string $model = AttendanceByBranch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $slug = 'employees-attendance-report';
    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Attendance by branch';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No data')
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::withAccess()->active()
                    ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                Filter::make('filter_date')->label('')->form([
                    DatePicker::make('date')
                        ->label('Date')->default(date('Y-m-d')),
                ]),

            ], FiltersLayout::AboveContent);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_attendance-by-branch');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeesAttednaceReport::route('/'),
        ];
    }
}
