<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Pages;

use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Branch;
use App\Models\FinancialTransaction;

class ListFinancialStatisticsReport extends ListRecords implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = FinancialStatisticsReportResource::class;

    public ?array $reportData = [];
    public ?array $filters = [];

    public function mount(): void
    {
        parent::mount();

        // Initialize default filters
        // $this->filters = [
        //     'start_date' => now()->startOfMonth()->format('Y-m-d'),
        //     'end_date' => now()->endOfMonth()->format('Y-m-d'),
        //     'branch_id' => null,
        // ];

        // $this->generateReport();
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            // ->filters([
            //     SelectFilter::make('branch_id')
            //         ->label(__('Branch'))
            //         ->searchable()
            //         ->options(Branch::active()->branches()->get()->pluck('name', 'id')),

            //     Filter::make('date_range')
            //         ->label(__('Date Range'))
            //         ->schema([
            //             DatePicker::make('start_date')
            //                 ->label(__('Start Date'))
            //                 ->default(fn() => now()->startOfMonth()->format('Y-m-d')),
            //             DatePicker::make('end_date')
            //                 ->label(__('End Date'))
            //                 ->default(fn() => now()->endOfMonth()->format('Y-m-d')),
            //         ])
            //         ->columnSpan(2),
            // ], FiltersLayout::AboveContent)
            ;
    }

    public function getTableFiltersFormWidth(): string
    {
        return '4xl';
    }

    // public function updatedTableFilters(): void
    // {
    //     $this->filters = [
    //         'start_date' => $this->tableFilters['date_range']['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
    //         'end_date' => $this->tableFilters['date_range']['end_date'] ?? now()->endOfMonth()->format('Y-m-d'),
    //         'branch_id' => $this->tableFilters['branch_id']['value'] ?? null,
    //     ];

    //     $this->generateReport();
    // }

    protected function generateReport(): void
    {
        $query = FinancialTransaction::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        // Calculate statistics
        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');
        $netBalance = $income - $expense;

        $incomeCount = (clone $query)->where('type', 'income')->count();
        $expenseCount = (clone $query)->where('type', 'expense')->count();
        $totalCount = $incomeCount + $expenseCount;

        $this->reportData = [
            'statistics' => [
                'totals' => [
                    'income' => $income,
                    'expense' => $expense,
                    'net_balance' => $netBalance,
                ],
                'transaction_counts' => [
                    'income' => $incomeCount,
                    'expense' => $expenseCount,
                    'total' => $totalCount,
                ],
            ],
        ];
    }

    public function getView(): string
    {
        return 'filament.pages.financial-reports.financial-statistics-report';
    }

    protected function getViewData(): array
    {
         return [
            'reportData' => $this->reportData,
            'filters' => $this->filters,
        ];
    }
}
