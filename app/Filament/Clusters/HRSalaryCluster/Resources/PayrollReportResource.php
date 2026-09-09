<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollReportResource\Pages\ListPayrollReports;
use App\Models\Branch;
use App\Models\EmployeePaymentMethod;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 7;

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
                            ->options(fn () => getMonthOptionsBasedOnSettings())
                            ->required()
                            ->live()
                            ->default(now()->format('F Y')),

                        Select::make('payment_method_id')
                            ->label(__('Payment Method'))
                            ->options(function () {
                                return EmployeePaymentMethod::where('active', 1)
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder(__('All Payment Methods'))
                            ->nullable()
                            ->live(),
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    })
                    ->columns(3),
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

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
           if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
