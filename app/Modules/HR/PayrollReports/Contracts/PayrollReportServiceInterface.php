<?php

namespace App\Modules\HR\PayrollReports\Contracts;

use App\Modules\HR\PayrollReports\DTOs\PayrollReportFilterDTO;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportResultDTO;

interface PayrollReportServiceInterface
{
    /**
     * Generate a detailed payroll report based on the provided filters.
     *
     * @param PayrollReportFilterDTO $filter
     * @return PayrollReportResultDTO
     */
    public function generate(PayrollReportFilterDTO $filter): PayrollReportResultDTO;
}
