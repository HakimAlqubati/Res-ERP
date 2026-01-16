<?php

namespace App\Modules\HR\Attendance\DTOs;

use App\Models\Attendance;

/**
 * DTO لنتيجة عملية الحضور
 * 
 * يستخدم لنقل نتيجة تسجيل الحضور بشكل موحد
 */
final readonly class AttendanceResultDTO
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?Attendance $record = null,
        public bool $typeRequired = false,
        public bool $shiftSelectionRequired = false,
        public ?array $availableShifts = null,
    ) {}

    /**
     * إنشاء نتيجة نجاح
     */
    public static function success(string $message, Attendance $record): self
    {
        return new self(
            success: true,
            message: $message,
            record: $record,
        );
    }

    /**
     * إنشاء نتيجة فشل
     */
    public static function failure(string $message, bool $typeRequired = false): self
    {
        return new self(
            success: false,
            message: $message,
            typeRequired: $typeRequired,
        );
    }

    /**
     * إنشاء نتيجة تتطلب اختيار الوردية
     */
    public static function shiftSelectionRequired(array $availableShifts): self
    {
        return new self(
            success: false,
            message: __('notifications.multiple_shifts_available'),
            shiftSelectionRequired: true,
            availableShifts: $availableShifts,
        );
    }

    /**
     * تحويل إلى مصفوفة للـ API response
     */
    public function toArray(): array
    {
        $result = [
            'status' => $this->success,
            'message' => $this->message,
            'data' => $this->record,
            'type_required' => $this->typeRequired,
        ];

        // إضافة معلومات اختيار الوردية إذا كانت مطلوبة
        if ($this->shiftSelectionRequired) {
            $result['shift_selection_required'] = true;
            $result['available_shifts'] = $this->availableShifts;
        }

        return $result;
    }

    /**
     * تحويل إلى JSON response
     */
    public function toResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->toArray(),
            $this->success ? 200 : 422
        );
    }
}
