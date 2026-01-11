<?php

namespace App\Modules\HR\Attendance\Exceptions;

use Exception;

/**
 * الكلاس الأساسي لجميع استثناءات الحضور
 * 
 * يوفر بنية موحدة للتعامل مع أخطاء نظام الحضور
 */
abstract class AttendanceException extends Exception
{
    /**
     * مفتاح الخطأ للتعريف البرمجي
     */
    protected string $errorKey = 'attendance_error';

    /**
     * الحصول على مفتاح الخطأ
     */
    public function getErrorKey(): string
    {
        return $this->errorKey;
    }

    /**
     * تحويل الاستثناء إلى مصفوفة للـ API response
     */
    public function toArray(): array
    {
        return [
            'status' => false,
            'error_key' => $this->errorKey,
            'message' => $this->getMessage(),
        ];
    }

    /**
     * تحويل الاستثناء إلى JSON response
     */
    public function toResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->toArray(), 422);
    }
}
