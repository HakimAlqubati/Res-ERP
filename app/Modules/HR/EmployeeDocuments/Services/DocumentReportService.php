<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Services;

use App\Modules\HR\EmployeeDocuments\Builders\EmployeeDocumentQueryBuilder;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentSummaryDTO;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use Illuminate\Database\Eloquent\Builder;

final class DocumentReportService implements DocumentReportServiceInterface
{
    public function buildQuery(DocumentReportFilterDTO $filter, bool $applyDefaultOrder = true): Builder
    {
        $builder = EmployeeDocumentQueryBuilder::make()
            ->withReportSelect($filter->asOf)
            ->applyFilter($filter, includeStatus: true);

        if ($applyDefaultOrder) {
            $builder->orderedByUrgency($filter->asOf);
        }

        return $builder->build();
    }

    /**
     * Computes all status counters in a single SQL aggregation query.
     */
    public function getSummary(DocumentReportFilterDTO $filter): DocumentSummaryDTO
    {
        $ref = $filter->referenceDateString();
        $defaultDays = DocumentStatusCalculator::DEFAULT_ALERT_DAYS;

        $row = EmployeeDocumentQueryBuilder::make()
            ->applyFilter($filter, includeStatus: false)
            ->build()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total,
                COALESCE(SUM(CASE WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL AND hr_employee_files.expiry_date < ? THEN 1 ELSE 0 END), 0) as expired,
                COALESCE(SUM(CASE WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL AND hr_employee_files.expiry_date >= ? AND hr_employee_files.expiry_date <= DATE_ADD(?, INTERVAL COALESCE(ft.expiry_alert_days, ?) DAY) THEN 1 ELSE 0 END), 0) as expiring_soon,
                COALESCE(SUM(CASE WHEN ft.has_expiry = 1 AND hr_employee_files.expiry_date IS NOT NULL AND hr_employee_files.expiry_date > DATE_ADD(?, INTERVAL COALESCE(ft.expiry_alert_days, ?) DAY) THEN 1 ELSE 0 END), 0) as valid,
                COALESCE(SUM(CASE WHEN ft.has_expiry = 0 OR hr_employee_files.expiry_date IS NULL THEN 1 ELSE 0 END), 0) as no_expiry',
                [$ref, $ref, $ref, $defaultDays, $ref, $defaultDays]
            )
            ->first();

        return new DocumentSummaryDTO(
            total: (int) ($row->total ?? 0),
            expired: (int) ($row->expired ?? 0),
            expiringSoon: (int) ($row->expiring_soon ?? 0),
            valid: (int) ($row->valid ?? 0),
            noExpiry: (int) ($row->no_expiry ?? 0),
        );
    }
}
