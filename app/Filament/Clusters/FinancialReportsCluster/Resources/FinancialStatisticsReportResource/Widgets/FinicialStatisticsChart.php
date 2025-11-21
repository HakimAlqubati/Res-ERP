<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Widgets;

use App\Models\FinancialTransaction;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\Branch;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class FinicialStatisticsChart extends ChartWidget
{
    use HasFiltersForm,
        InteractsWithForms;

    protected ?string $heading = 'Finicial Statistics Chart';
    protected ?string $maxHeight = '350px';

 
    protected ?string $description = null;
    public ?array $reportData = [];

    // protected function getFilters(): ?array
    // {
    //     return Branch::active()->pluck('name', 'id')->toArray();
    // }

    // public function filtersForm(Schema $schema): Schema
    // {
    //     return $schema
    //         ->components([
    //             Select::make('branch_id')
    //                 ->label(__('Branch'))
    //                 ->options(Branch::active()->branches()->get()->pluck('name', 'id'))
    //                 ->searchable(),
    //             DatePicker::make('start_date')
    //                 ->label(__('Start Date'))
    //                 ->default(now()->startOfMonth()),
    //             DatePicker::make('end_date')
    //                 ->label(__('End Date'))
    //                 ->default(now()->endOfMonth()),
    //         ]);
    // }
    protected function generateReport(): void
    {
        $query = FinancialTransaction::query();
        // dd($this->filters);

        $startDate = null;
        $endDate = null;
        $branchId = null;
        // $startDate = $this->filters['start_date'] ?? null;
        // $endDate = $this->filters['end_date'] ?? null;
        // $branchId = $this->filters['branch_id'] ?? null;

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
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

    protected function getData(): array
    {
        $this->generateReport();

        return [
            'datasets' => [
                [
                    'label' => 'Financial Statistics',
                    'data' => [
                        $this->reportData['statistics']['totals']['income'] ?? 0,
                        $this->reportData['statistics']['totals']['expense'] ?? 0,
                    ],
                    'backgroundColor' => ['#10b981', '#ef4444'], // Green for Income, Red for Expense
                ],
            ],
            'labels' => [
                __('Income'),
                __('Expense'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
