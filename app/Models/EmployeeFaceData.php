<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeFaceData extends Model
{
    protected $table = 'hr_employee_face_data';

    protected $fillable = [
        'employee_id',
        'employee_name',
        'employee_email',
        'employee_branch_id',
        'image_path',
        'embedding',
        'active',
        'face_added',
        'response_message',

    ];

    protected $appends = ['image_url'];

    protected $casts = [
        'embedding'  => 'array',
        'active'     => 'boolean',
        'face_added' => 'boolean',
    ];

    /**
     * علاقة اختيارية بالموظف إن أردت ربطه لاحقًا
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Scope: فقط الفعّالة
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * استرجاع كل بصمات موظف باستخدام البريد الإلكتروني
     */
    public static function getByEmail($email)
    {
        return self::where('employee_email', $email)->active()->get();
    }

    /**
     * دالة مساعدة لإضافة بصمة وجه جديدة
     */
    public static function addFaceRecord(array $data): self
    {
        return self::create($data);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
        ? Storage::disk('public')->url($this->image_path)
        : null;
    }

    /**
     * Scope: فقط السجلات التي تم فيها إضافة البصمة
     */
    public function scopeFaceAdded($query)
    {
        return $query->where('face_added', true);
    }

/**
 * Scope: فقط السجلات التي لم تُضف فيها البصمة بعد
 */
    public function scopeFaceNotAdded($query)
    {
        return $query->where('face_added', false);
    }

}