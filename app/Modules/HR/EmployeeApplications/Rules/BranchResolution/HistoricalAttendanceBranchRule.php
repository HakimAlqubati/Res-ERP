<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Rules\BranchResolution;

use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeBranchLog;
use App\Modules\HR\EmployeeApplications\Contracts\ApplicationBranchRuleInterface;
use App\Modules\HR\EmployeeApplications\DTOs\ApplicationBranchContext;

/**
 * Resolves the historical branch ID from hr_employee_branch_logs for
 * missed check-in and missed check-out applications based on the missed date.
 */
final class HistoricalAttendanceBranchRule implements ApplicationBranchRuleInterface
{
    /**
     * Map supported application types to candidate payload keys for the target date.
     *
     * @var array<int, array<int, string>>
     */
    private const TYPE_DATE_PATHS = [
        EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => [
            'missed_checkin_request.date',
            'missedCheckinRequest.date',
            'application_date',
        ],
        EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => [
            'missed_checkout_request.date',
            'missed_checkout_request.detail_date',
            'missedCheckoutRequest.detail_date',
            'missedCheckoutRequest.date',
            'application_date',
        ],
    ];

    public function supports(ApplicationBranchContext $context): bool
    {
        return array_key_exists($context->applicationTypeId, self::TYPE_DATE_PATHS);
    }

    public function resolveBranchId(ApplicationBranchContext $context): ?int
    {
        $targetDate = $this->extractTargetDate($context);

        if (! $targetDate) {
            return null;
        }

        return EmployeeBranchLog::resolveBranchIdAtDate(
            employeeId: (int) $context->employee->id,
            date: $targetDate
        );
    }

    private function extractTargetDate(ApplicationBranchContext $context): ?string
    {
        $paths = self::TYPE_DATE_PATHS[$context->applicationTypeId] ?? [];

        foreach ($paths as $path) {
            $value = data_get($context->payload, $path);
            if (! empty($value) && is_string($value)) {
                return $value;
            }
        }

        return null;
    }
}
