<?php

namespace App\Modules\HR\Attendance\Actions;

use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\Enums\CheckType;

/**
 * Action لتحديد نوع العملية (دخول/خروج)
 * 
 * يحدد تلقائياً إذا كانت العملية دخول أو خروج بناءً على:
 * 1. النوع المحدد في الطلب (إذا موجود)
 * 2. وجود سجل دخول مفتوح
 */
class DetermineCheckTypeAction
{
    public function __construct(
        private AttendanceRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ العملية
     */
    public function execute(AttendanceContextDTO $context): AttendanceContextDTO
    {
        // إذا كان النوع محدداً في الطلب
        $requestedType = $context->getRequestedCheckType();
        if ($requestedType !== null) {
            return $this->handleExplicitType($context, $requestedType);
        }

        // تحديد تلقائي
        return $this->autoDetect($context);
    }

    /**
     * معالجة النوع المحدد صراحة
     */
    private function handleExplicitType(AttendanceContextDTO $context, CheckType $type): AttendanceContextDTO
    {
        $context->setCheckType($type);

        // إذا كان خروج، نبحث عن سجل الدخول
        if ($type->isCheckOut()) {
            $lastCheckIn = $this->findOpenCheckIn($context);
            $context->setLastCheckIn($lastCheckIn);
        }

        return $context;
    }

    /**
     * التحديد التلقائي للنوع
     */
    private function autoDetect(AttendanceContextDTO $context): AttendanceContextDTO
    {
        $lastCheckIn = $this->findOpenCheckIn($context);

        if ($lastCheckIn) {
            // يوجد سجل دخول مفتوح → هذا خروج
            $context->setCheckType(CheckType::CHECKOUT);
            $context->setLastCheckIn($lastCheckIn);
        } else {
            // لا يوجد سجل دخول مفتوح → هذا دخول
            $context->setCheckType(CheckType::CHECKIN);
        }

        return $context;
    }

    /**
     * البحث عن سجل دخول مفتوح
     */
    private function findOpenCheckIn(AttendanceContextDTO $context): ?\App\Models\Attendance
    {
        if (!$context->workPeriod || !$context->shiftDate) {
            return null;
        }

        return $this->repository->findOpenCheckIn(
            $context->employee->id,
            $context->workPeriod->id,
            $context->shiftDate
        );
    }
}
