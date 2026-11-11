<?php

namespace App\Modules\HR\ApprovalPolicies\Support;

use App\Models\AdvanceWage;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use Illuminate\Database\Eloquent\Model;

final class ApprovalSubjectRegistry
{
    /**
     * @return list<class-string<Model>>
     */
    public static function restrictedModels(): array
    {
        return [
            EmployeeApplicationV2::class,
            EmployeeOvertime::class,
            AdvanceWage::class,
        ];
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public static function subjectTypeOptions(): array
    {
        return [
            EmployeeApplicationV2::class => __('Employee Requests'),
            EmployeeOvertime::class => __('Employee Overtime'),
            AdvanceWage::class => __('Advance Wages'),
        ];
    }

    public static function subjectTypeLabel(?string $type): string
    {
        return self::subjectTypeOptions()[$type] ?? class_basename((string) $type);
    }

    public static function subjectBadgeColor(?string $type): string
    {
        return match ($type) {
            EmployeeApplicationV2::class => 'info',
            EmployeeOvertime::class => 'warning',
            AdvanceWage::class => 'success',
            default => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function combinedSubjectOptions(): array
    {
        $options = [
            EmployeeOvertime::class => __('Employee Overtime'),
            AdvanceWage::class => __('Advance Wages'),
            EmployeeApplicationV2::class . ':all' => __('All Employee Requests'),
        ];

        foreach (EmployeeApplicationV2::APPLICATION_TYPE_NAMES as $id => $name) {
            $options[EmployeeApplicationV2::class . ':' . $id] = __('Employee Requests') . ' - ' . $name;
        }

        return $options;
    }

    public static function encodeCombinedSubject(?string $approvableType, mixed $applicationTypeId = null): ?string
    {
        if (! $approvableType) {
            return null;
        }

        if ($approvableType === EmployeeApplicationV2::class) {
            $suffix = ($applicationTypeId !== null && $applicationTypeId !== '')
                ? (string) $applicationTypeId
                : 'all';

            return $approvableType . ':' . $suffix;
        }

        return $approvableType;
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    public static function decodeCombinedSubject(?string $combined): array
    {
        if (! $combined) {
            return [null, null];
        }

        if (str_contains($combined, ':')) {
            [$type, $rawAppId] = explode(':', $combined, 2);
            $appId = ($rawAppId === 'all' || $rawAppId === '') ? null : (int) $rawAppId;

            return [$type ?: null, $appId];
        }

        return [$combined, null];
    }
}
