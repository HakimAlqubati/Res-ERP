<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Branch;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollReportResource\Pages\ListPayrollReports;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PayrollReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $cluster = HRSalaryCluster::class;
    
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    
    protected static ?int $navigationSort = 5;

    protected static ?string $pluralLabel = 'Payroll Report';

    protected static ?string $pluralModelLabel = 'Payroll Report';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->deferFilters(false)
            ->filters([
                Filter::make('payroll_filter')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('Branch'))
                            ->options(function () {
                                return Branch::where('active', 1)
                                    ->forBranchManager('id')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder(__('Select Branch'))
                            ->required(),

                        Select::make('period')
                            ->label(__('Month'))
                            ->options(fn() => getMonthOptionsBasedOnSettings())
                            ->required()
                            ->live()
                            ->default(now()->format('F Y')),
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    })
                    ->columns(2),
            ], FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollReports::route('/'),
        ];
    }
}
