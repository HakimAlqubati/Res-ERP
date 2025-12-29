<?php

namespace App\Services\Financial;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * Multi-Branch Financial Report Service
 * 
 * A wrapper service that leverages the existing FinancialReportService
 * to generate comparative reports across multiple branches.
 */
class MultiBranchFinancialReportService
{
    protected FinancialReportService $reportService;

    public function __construct(?FinancialReportService $reportService = null)
    {
        $this->reportService = $reportService ?? new FinancialReportService();
    }

    /**
     * Get income statement comparison for multiple branches.
     *
     * @param array $branchIds Array of branch IDs to compare
     * @param string|null $startDate Start date for the report
     * @param string|null $endDate End date for the report
     * @param bool $includeTotal Whether to include a total/summary column
     * @return array
     */
    public function getMultiBranchIncomeStatement(
        array $branchIds,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $includeTotal = true
    ): array {
        $branches = Branch::whereIn('id', $branchIds)->get()->keyBy('id');
        $reports = [];
        $totals = $this->initializeTotals();

        foreach ($branchIds as $branchId) {
            $dto = new IncomeStatementRequestDTO(
                startDate: $startDate,
                endDate: $endDate,
                branchId: (int) $branchId
            );

            $report = $this->reportService->getIncomeStatement($dto);

            $branchName = $branches->get($branchId)?->name ?? "Branch #{$branchId}";

            $reports[$branchId] = [
                'branch_id' => $branchId,
                'branch_name' => $branchName,
                'data' => $report,
            ];

            // Accumulate totals
            $this->accumulateTotals($totals, $report);
        }

        // Calculate total ratio
        if ($totals['revenue']['total'] > 0) {
            $totals['gross_profit']['ratio'] =
                ($totals['gross_profit']['value'] / $totals['revenue']['total']) * 100;
            $totals['gross_profit']['ratio_formatted'] =
                number_format($totals['gross_profit']['ratio'], 2) . '%';
        }

        return [
            'branches' => $reports,
            'totals' => $includeTotal ? $this->formatTotals($totals) : null,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'meta' => [
                'branch_count' => count($branchIds),
                'generated_at' => now()->toDateTimeString(),
            ],
        ];
    }

    /**
     * Get a comparison table format suitable for UI display.
     *
     * @param array $branchIds
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getComparisonTable(
        array $branchIds,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $result = $this->getMultiBranchIncomeStatement($branchIds, $startDate, $endDate);

        $rows = [
            'revenue' => ['label' => __('Total Sales Revenue'), 'values' => []],
            'closing_stock' => ['label' => __('Closing Stock'), 'values' => []],
            'direct_purchase' => ['label' => __('Direct Purchase'), 'values' => []],
            'transfers' => ['label' => __('Transfers'), 'values' => []],
            'gross_profit' => ['label' => __('Gross Profit'), 'values' => []],
            'gross_margin' => ['label' => __('Gross Margin %'), 'values' => []],
            'total_expenses' => ['label' => __('Total Expenses'), 'values' => []],
            'net_profit' => ['label' => __('Net Profit'), 'values' => []],
        ];

        $headers = [];

        foreach ($result['branches'] as $branchId => $branch) {
            $headers[] = [
                'id' => $branchId,
                'name' => $branch['branch_name'],
            ];

            $data = $branch['data'];

            $rows['revenue']['values'][$branchId] = $data['revenue']['total_formatted'];
            $rows['closing_stock']['values'][$branchId] = $data['cost_of_goods_sold']['closing_stock_formatted'];
            $rows['direct_purchase']['values'][$branchId] = $data['cost_of_goods_sold']['direct_purchase_formatted'];
            $rows['transfers']['values'][$branchId] = $data['cost_of_goods_sold']['transfers_formatted'];
            $rows['gross_profit']['values'][$branchId] = $data['gross_profit']['value_formatted'];
            $rows['gross_margin']['values'][$branchId] = $data['gross_profit']['ratio_formatted'];
            $rows['total_expenses']['values'][$branchId] = $data['expenses']['total_formatted'];
            $rows['net_profit']['values'][$branchId] = $data['net_profit_formatted'];
        }

        // Add totals column
        if ($result['totals']) {
            $headers[] = [
                'id' => 'total',
                'name' => __('Total'),
                'is_total' => true,
            ];

            $totals = $result['totals'];
            $rows['revenue']['values']['total'] = $totals['revenue']['total_formatted'];
            $rows['closing_stock']['values']['total'] = $totals['cost_of_goods_sold']['closing_stock_formatted'];
            $rows['direct_purchase']['values']['total'] = $totals['cost_of_goods_sold']['direct_purchase_formatted'];
            $rows['transfers']['values']['total'] = $totals['cost_of_goods_sold']['transfers_formatted'];
            $rows['gross_profit']['values']['total'] = $totals['gross_profit']['value_formatted'];
            $rows['gross_margin']['values']['total'] = $totals['gross_profit']['ratio_formatted'];
            $rows['total_expenses']['values']['total'] = $totals['expenses']['total_formatted'];
            $rows['net_profit']['values']['total'] = $totals['net_profit_formatted'];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'period' => $result['period'],
            'meta' => $result['meta'],
        ];
    }

    /**
     * Initialize totals structure.
     */
    private function initializeTotals(): array
    {
        return [
            'revenue' => ['total' => 0],
            'cost_of_goods_sold' => [
                'transfers' => 0,
                'direct_purchase' => 0,
                'closing_stock' => 0,
                'total' => 0,
            ],
            'gross_profit' => [
                'value' => 0,
                'ratio' => 0,
            ],
            'expenses' => ['total' => 0],
            'net_profit' => 0,
        ];
    }

    /**
     * Accumulate values into totals.
     */
    private function accumulateTotals(array &$totals, array $report): void
    {
        $totals['revenue']['total'] += $report['revenue']['total'] ?? 0;
        $totals['cost_of_goods_sold']['transfers'] += $report['cost_of_goods_sold']['transfers'] ?? 0;
        $totals['cost_of_goods_sold']['direct_purchase'] += $report['cost_of_goods_sold']['direct_purchase'] ?? 0;
        $totals['cost_of_goods_sold']['closing_stock'] += $report['cost_of_goods_sold']['closing_stock'] ?? 0;
        $totals['cost_of_goods_sold']['total'] += $report['cost_of_goods_sold']['total'] ?? 0;
        $totals['gross_profit']['value'] += $report['gross_profit']['value'] ?? 0;
        $totals['expenses']['total'] += $report['expenses']['total'] ?? 0;
        $totals['net_profit'] += $report['net_profit'] ?? 0;
    }

    /**
     * Format totals with currency formatting.
     */
    private function formatTotals(array $totals): array
    {
        return [
            'revenue' => [
                'total' => $totals['revenue']['total'],
                'total_formatted' => formatMoneyWithCurrency($totals['revenue']['total']),
            ],
            'cost_of_goods_sold' => [
                'transfers' => $totals['cost_of_goods_sold']['transfers'],
                'transfers_formatted' => formatMoneyWithCurrency($totals['cost_of_goods_sold']['transfers']),
                'direct_purchase' => $totals['cost_of_goods_sold']['direct_purchase'],
                'direct_purchase_formatted' => formatMoneyWithCurrency($totals['cost_of_goods_sold']['direct_purchase']),
                'closing_stock' => $totals['cost_of_goods_sold']['closing_stock'],
                'closing_stock_formatted' => formatMoneyWithCurrency($totals['cost_of_goods_sold']['closing_stock']),
                'total' => $totals['cost_of_goods_sold']['total'],
                'total_formatted' => formatMoneyWithCurrency($totals['cost_of_goods_sold']['total']),
            ],
            'gross_profit' => [
                'value' => $totals['gross_profit']['value'],
                'value_formatted' => formatMoneyWithCurrency($totals['gross_profit']['value']),
                'ratio' => $totals['gross_profit']['ratio'],
                'ratio_formatted' => number_format($totals['gross_profit']['ratio'], 2) . '%',
            ],
            'expenses' => [
                'total' => $totals['expenses']['total'],
                'total_formatted' => formatMoneyWithCurrency($totals['expenses']['total']),
            ],
            'net_profit' => $totals['net_profit'],
            'net_profit_formatted' => formatMoneyWithCurrency($totals['net_profit']),
        ];
    }
}
