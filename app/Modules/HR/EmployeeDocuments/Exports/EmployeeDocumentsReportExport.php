<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Exports;

use App\Modules\HR\EmployeeDocuments\Builders\EmployeeDocumentQueryBuilder;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Streams employee documents report to Excel using FromQuery + WithCustomChunkSize
 * for O(1) memory usage while reusing EmployeeDocumentQueryBuilder.
 */
final class EmployeeDocumentsReportExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping, ShouldAutoSize
{
    private readonly DocumentStatusCalculator $calculator;

    public function __construct(
        private readonly ?DocumentReportFilterDTO $filter = null,
        private readonly ?Builder $prebuiltQuery = null,
    ) {
        $this->calculator = app(DocumentStatusCalculator::class);
    }

    public function query(): Builder
    {
        if ($this->prebuiltQuery instanceof Builder) {
            $query = clone $this->prebuiltQuery;

            if (empty($query->getQuery()->orders)) {
                $query->orderBy('hr_employee_files.id', 'asc');
            }

            return $query;
        }

        $filter = $this->filter ?? new DocumentReportFilterDTO();

        return EmployeeDocumentQueryBuilder::make()
            ->withReportSelect($filter->asOf)
            ->applyFilter($filter, includeStatus: true)
            ->orderedByUrgency($filter->asOf)
            ->build();
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('lang.employee_no'),
            __('lang.employee'),
            __('lang.branch'),
            __('lang.nationality'),
            __('lang.file_type'),
            __('lang.issue_date'),
            __('lang.expiry_date'),
            __('lang.remaining_days'),
            __('lang.status'),
            __('lang.active'),
            __('lang.additional_fields'),
        ];
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        $hasExpiry = (bool) ($row->file_type_has_expiry ?? $row->fileType?->has_expiry ?? false);
        $alertDays = isset($row->file_type_alert_days)
            ? (int) $row->file_type_alert_days
            : $row->fileType?->expiry_alert_days;

        $asOf = $this->filter?->asOf;
        $status = $this->calculator->resolveStatus($hasExpiry, $row->expiry_date, $alertDays, $asOf);
        $remainingDays = $this->calculator->remainingDays($hasExpiry, $row->expiry_date, $asOf);

        $dynamicSummary = '';
        if (is_array($row->dynamic_field_values) && $row->dynamic_field_values !== []) {
            $pairs = [];
            foreach ($row->dynamic_field_values as $k => $v) {
                if (is_scalar($v) && trim((string) $v) !== '') {
                    $pairs[] = ucfirst(str_replace('_', ' ', (string) $k)) . ': ' . $v;
                }
            }
            $dynamicSummary = implode(' | ', $pairs);
        }

        return [
            $row->employee_no ?? '',
            $row->employee_name ?? '',
            $row->branch_name ?? '',
            $row->employee_nationality ?? '',
            $row->file_type_name ?? '',
            $row->issue_date ? $row->issue_date->format('Y-m-d') : '',
            $row->expiry_date ? $row->expiry_date->format('Y-m-d') : '',
            $remainingDays !== null ? $remainingDays : '',
            $status->label(),
            $row->active ? __('lang.yes') : __('lang.no'),
            $dynamicSummary,
        ];
    }
}
