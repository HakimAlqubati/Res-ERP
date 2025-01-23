<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeAttendanceReportResource\Pages;
use App\Filament\Clusters\HRCluster\Resources\EmployeeAttendanceReportResource\RelationManagers;
use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeDeductoinSummaryResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;

    protected static ?string $pluralLabel = 'Deduction Summary';

    protected static ?string $pluralModelLabel = 'Deduction Summary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                SelectFilter::make('employee_id')->label('Employee')->options(
                    function () {
                        return Employee::where('active', 1)
                            ->get()
                            ->mapWithKeys(function ($employee) {
                                return [$employee->id => $employee->name . ' - ' . $employee->id];
                            });
                    }
                )

                    ->hidden(fn() => isStuff())
                    ->searchable(),
                SelectFilter::make('year')->label('Year')
                    ->native(false)
                    ->options(
                        [2024 => 2024, 2025 => 2025, 2026 => 2026]
                    )
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeDeductionSummaryReports::route('/'),

        ];
    }
}
