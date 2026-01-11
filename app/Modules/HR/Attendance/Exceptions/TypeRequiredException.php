<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * استثناء: يجب تحديد نوع العملية (دخول/خروج) صراحة
 * 
 * يحدث عند محاولة تسجيل حضور قرب نهاية الشيفت بدون سجلات سابقة
 */
class TypeRequiredException extends AttendanceException
{
    protected string $errorKey = 'type_required';

    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('notifications.cannot_auto_checkin_near_shift_end')
        );
    }
}
