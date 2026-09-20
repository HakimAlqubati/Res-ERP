<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeStatementReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeStatementReportResource;
use App\Modules\HR\Payroll\DTOs\EmployeeStatementFilterDTO;
use App\Modules\HR\Payroll\Reports\EmployeeStatementReport;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListEmployeeStatementReports extends ListRecords
{
    protected static string $resource = EmployeeStatementReportResource::class;

    protected string $view = 'reports.hr.payroll.employee-statement-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $statementState = $filters['statement_filter']->getState() ?? [];

        $employeeId = ! empty($statementState['employee_id']) ? (int) $statementState['employee_id'] : null;
        $fromDate   = $statementState['from_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $toDate     = $statementState['to_date'] ?? now()->endOfMonth()->format('Y-m-d');

        try {
            $dto = EmployeeStatementFilterDTO::fromArray([
                'employee_id' => $employeeId,
                'from_date'   => $fromDate,
                'to_date'     => $toDate,
                'status'      => 'approved',
            ]);

            /** @var EmployeeStatementReport $report */
            $report = app(EmployeeStatementReport::class);
            $reportData = $report->getReport($dto);
        } catch (Throwable $e) {
            $reportData = null;
        }

        return [
            'reportData' => $reportData,
        ];
    }

    public function getView(): string
    {
        return $this->view;
    }
}
