<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceImagesUploaded extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attendance_images_uploaded';

    protected $appends = 'employee_name';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'img_url',
        'employee_id',
        'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * سجلات الحضور المرتبطة بهذه الصورة (عبر polymorphic source)
     */
    public function attendances()
    {
        return $this->morphMany(Attendance::class, 'source');
    }

    public function getFullImageUrlAttribute()
    {
        $url = env('AWS_URL_IMG') . $this->img_url;
        return $url;
    }

    public function getEmployeeNameAttribute()
    {
        return $this?->employee?->name ?? 'Unknown';
    }
}
