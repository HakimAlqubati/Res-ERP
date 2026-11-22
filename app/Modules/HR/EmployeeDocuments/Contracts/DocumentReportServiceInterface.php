<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Contracts;

use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentSummaryDTO;
use Illuminate\Database\Eloquent\Builder;

interface DocumentReportServiceInterface
{
    public function buildQuery(DocumentReportFilterDTO $filter, bool $applyDefaultOrder = true): Builder;

    public function getSummary(DocumentReportFilterDTO $filter): DocumentSummaryDTO;
}
