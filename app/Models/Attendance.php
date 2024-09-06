<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $table = 'hr_attendances';

    const CHECKTYPE_CHECKIN = 'checkin';
    const CHECKTYPE_CHECKOUT = 'checkout';
    const CHECKTYPE_CHECKIN_LABLE = 'Check in';
    const CHECKTYPE_CHECKOUT_LABLE = 'Checkout';
    protected $fillable = [
        'employee_id',
        'check_type',
        'check_time',
        'check_date',
        'location',
        'is_manual',
        'notes',
        'created_by',
        'updated_by',
        'day',
    ];




    public static function getCheckTypes()
    {
        return [
            self::CHECKTYPE_CHECKIN => self::CHECKTYPE_CHECKIN_LABLE,
            self::CHECKTYPE_CHECKOUT => self::CHECKTYPE_CHECKOUT_LABLE,
        ];
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relationship to the user who created the attendance
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the attendance
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
