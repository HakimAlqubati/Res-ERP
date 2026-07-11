<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Models\EmployeePaymentMethod;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\HR\PayrollReports\Exports\TngPaymentExport;
use App\Modules\HR\PayrollReports\Services\TngPaymentReportService;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class TngPaymentReportResource extends Resource
{
    protected static ?string $model = Payroll::class;
    
    protected static ?string $slug = 'tng-payment-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?string $label = 'TnG Payment';
    protected static ?string $pluralLabel = 'TnG Payment';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('lang.no_data'))
            ->striped()
            ->columns([
                TextColumn::make('employee.payment_details.account_number')
                    ->label('E-Wallet Account Number')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function ($q) use ($search) {
                            $q->whereJsonContains('payment_details->account_number', $search);
                        });
                    }),

                TextColumn::make('net_salary')
                    ->label("RM")
                    ->numeric(2, '.', '')
                    ->sortable()
                    ->formatStateUsing(fn($state)=>formatMoneyWithCurrency($state))
                    ,

                TextColumn::make('employee.payment_details.full_name')
                    ->label('Reward Name')
                    ->limit(20)
                    ->getStateUsing(function ($record) {
                        return $record->employee?->payment_details['full_name'] ?? $record->employee?->name ?? '';
                    }),

                TextColumn::make('reward_description')
                    ->label('Reward Description')
                    ->limit(200)
                    ->getStateUsing(function ($record) {
                        $monthName = Carbon::create()->month($record->month)->format('F');
                        $branchName = $record->branch?->name ?? $record->employee?->branch?->name ?? 'Unknown Branch';
                        return "Salary - {$monthName} {$record->year} - {$branchName}";
                    }),
            ])
            ->deferFilters(true)
            ->filters([
                Filter::make('month_year')
                    ->form([
                        Select::make('month_year')
                            ->label('Month & Year')
                            ->options(fn() => getMonthOptionsBasedOnSettings())
                            ->searchable()
                            ->placeholder('Select Month & Year')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['month_year'])) {
                            $parts = explode(' ', $data['month_year']);
                            if (count($parts) === 2) {
                                $monthName = $parts[0];
                                $year = $parts[1];
                                $monthNumber = Carbon::parse("1 $monthName")->month;
                                $query->where('year', $year)->where('month', $monthNumber);
                            } else {
                                $query->whereRaw('1 = 0');
                            }
                        } else {
                            // Make filtering mandatory: show no data if no filter is selected
                            $query->whereRaw('1 = 0');
                        }
                        return $query;
                    })
            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $query = clone $livewire->getFilteredTableQuery();

                        $filterData = $livewire->tableFilters['month_year'] ?? [];
                        $month_year = $filterData['month_year'] ?? null;
                        
                        $fileName = 'TnG_Payment_Report';
                        if ($month_year) {
                            $fileName .= "_" . str_replace(' ', '_', $month_year);
                        }
                        $fileName .= '.xlsx';

                        return Excel::download(
                            new TngPaymentExport($query), 
                            $fileName
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Clusters\HRSalaryCluster\Resources\TngPaymentReportResource\Pages\ListTngPaymentReports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return TngPaymentReportService::applyBaseQuery(parent::getEloquentQuery());
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager();
    }
}
