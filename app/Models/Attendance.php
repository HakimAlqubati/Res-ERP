<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_attendances';

    const CHECKTYPE_CHECKIN        = 'checkin';
    const CHECKTYPE_CHECKOUT       = 'checkout';
    const CHECKTYPE_CHECKIN_LABLE  = 'Check in';
    const CHECKTYPE_CHECKOUT_LABLE = 'Checkout';

    public function scopeEarlyDepartures($query)
    {
        return $query->where('check_type', self::CHECKTYPE_CHECKOUT)
            ->where('status', self::STATUS_EARLY_DEPARTURE)
            ->where('early_departure_minutes', '<', 20);
    }
    public function scopeLateArrival($query)
    {
        return $query->where('check_type', self::CHECKTYPE_CHECKIN)
            ->where('status', self::STATUS_LATE_ARRIVAL)
            ->where('delay_minutes', '<', 10)
        ;
    }

    const PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR = 'quarter_period';
    const PERIOD_ALLOWED_OVERTIME_HALF_HOUR    = 'half_period';
    const PERIOD_ALLOWED_OVERTIME_HOUR         = 'hour';

    const PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_MINUTES = 15;
    const PERIOD_ALLOWED_OVERTIME_HALF_HOUR_MINUTES    = 30;
    const PERIOD_ALLOWED_OVERTIME_HOUR_MINUTES         = 60;

    const PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_LABEL = 'Quarter hour';
    const PERIOD_ALLOWED_OVERTIME_HALF_HOUR_LABEL    = 'Half hour';
    const PERIOD_ALLOWED_OVERTIME_HOUR_LABEL         = 'Hour';

    const STATUS_EARLY_ARRIVAL   = 'early_arrival';
    const STATUS_LATE_ARRIVAL    = 'late_arrival';
    const STATUS_ON_TIME         = 'on_time';
    const STATUS_EARLY_DEPARTURE = 'early_departure';
    const STATUS_LATE_DEPARTURE  = 'late_departure';
    const STATUS_TEST            = 'status_test';

    protected $fillable = [
        'employee_id',
        'check_type',
        'check_time',
        'check_date',
        'period_id',
        'location',
        'is_manual',
        'notes',
        'created_by',
        'updated_by',
        'day',
        'delay_minutes',
        'early_arrival_minutes',
        'late_departure_minutes',
        'early_departure_minutes',
        'status',
        'actual_duration_hourly',
        'supposed_duration_hourly',
        'branch_id',
        'checkinrecord_id',
        'total_actual_duration_hourly',
        'is_from_previous_day',
        'attendance_method',
        'accepted',
        'message',
        'attendance_type',
        'real_check_date',

    ];

    const ATTENDANCE_TYPE_WEBCAM  = 'webcam';
    const ATTENDANCE_TYPE_RFID    = 'rfid';
    const ATTENDANCE_TYPE_REQUEST = 'request';

    const ATTENDANCE_METHOD_FINGERPRINT      = 'fingerprint';
    const ATTENDANCE_METHOD_FACEPRINT        = 'faceprint';
    const ATTENDANCE_METHOD_RFID             = 'rfid';
    const ATTENDANCE_METHOD_EMPLOYEE_REQUEST = 'employee_request';
    public static function getMinutesByConstant($constantName)
    {
        switch ($constantName) {
            case self::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR:
                return self::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_MINUTES;
            case self::PERIOD_ALLOWED_OVERTIME_HALF_HOUR:
                return self::PERIOD_ALLOWED_OVERTIME_HALF_HOUR_MINUTES;
            case self::PERIOD_ALLOWED_OVERTIME_HOUR:
                return self::PERIOD_ALLOWED_OVERTIME_HOUR_MINUTES;
        }
    }
    public static function getCheckTypes()
    {
        return [
            self::CHECKTYPE_CHECKIN  => self::CHECKTYPE_CHECKIN_LABLE,
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

    public function period()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        }
    }

    // Define the self-referencing relationship for check-in
    public function checkinRecord()
    {
        return $this->belongsTo(Attendance::class, 'checkinrecord_id');
    }

    // Define the relationship to get the checkout record associated with a check-in
    public function checkoutRecord()
    {
        return $this->hasOne(Attendance::class, 'checkinrecord_id');
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_EARLY_ARRIVAL   => self::STATUS_EARLY_ARRIVAL,
            self::STATUS_LATE_ARRIVAL    => self::STATUS_LATE_ARRIVAL,
            self::STATUS_ON_TIME         => self::STATUS_ON_TIME,
            self::STATUS_EARLY_DEPARTURE => self::STATUS_EARLY_DEPARTURE,
            self::STATUS_LATE_DEPARTURE  => self::STATUS_LATE_DEPARTURE,
            self::STATUS_TEST            => self::STATUS_TEST,
        ];
    }

    /**
     * Store a new attendance record with 'accepted' set to false.
     *
     * @param array $attributes
     * @return Attendance
     */
    public static function storeNotAccepted($employee, $date, $time, $day, $message, $periodId, $attendanceType)
    {
        // Ensure that 'accepted' is set to false
        $attributes['accepted'] = false;

        $attributes['created_by']      = auth()->id();
        $attributes['employee_id']     = $employee->id;
        $attributes['check_date']      = $date;
        $attributes['check_time']      = $time;
        $attributes['day']             = $day;
        $attributes['message']         = $message;
        $attributes['period_id']       = $periodId;
        $attributes['branch_id']       = $employee?->branch_id;
        $attributes['attendance_type'] = $attendanceType;

        // Create and return the new attendance record
        return self::create($attributes);
    }

    public function scopeAccepted($query)
    {
        return $query->where('accepted', 1);
    }

    public static function isPeriodClosed($employeeId, $periodId, $date)
    { 
        $workPeriod = \App\Models\WorkPeriod::find($periodId);
        if (! $workPeriod) {
            return false;
        }

        $checkoutRecord = self::where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('check_type', self::CHECKTYPE_CHECKOUT)
            ->where('accepted', 1)
            ->orderByDesc('check_time')
            ->first();
        if (! $checkoutRecord) {
            return false;
        }

        // بناء تاريخ/وقت نهاية الشفت بالضبط
        $periodEndDateTime = \Carbon\Carbon::parse("$date " . $workPeriod->end_at);
        $checkoutDateTime  = \Carbon\Carbon::parse("$date " . $checkoutRecord->check_time);
        // إذا الشفت ليل ووقت الانصراف بعد منتصف الليل
        if ($workPeriod->day_and_night && $checkoutDateTime->lessThan($periodEndDateTime)) {
            $checkoutDateTime->addDay();
        } 
        
        if($workPeriod->start_at == '00:00:00'){
            $checkoutDateTime  = \Carbon\Carbon::parse("$checkoutRecord->real_check_date " . $checkoutRecord->check_time);
        }
        // dd($checkoutDateTime->greaterThan($periodEndDateTime));
        return $checkoutDateTime->greaterThan($periodEndDateTime);
    }

}