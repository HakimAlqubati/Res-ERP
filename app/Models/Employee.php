<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'hr_employees';
    protected $fillable = [
        'name',
        'position_id',
        'email',
        'phone_number',
        'job_title',
        'user_id',
        'branch_id',
        'department_id',
        'employee_no',
        'active',
        'avatar',
        'join_date',
        'address',
        'salary',
        'discount_exception_if_attendance_late',
        'discount_exception_if_absent',
        'rfid',
        'employee_type',
    ];

    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED = 'added';
    public const TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED = 'removed';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function files()
    {
        return $this->hasMany(EmployeeFile::class, 'employee_id');
    }

    // Accessor for required_documents_count
    public function getRequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', true);
        })->count();
    }

    // Accessor for unrequired_documents_count
    public function getUnrequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', false);
        })->count();
    }

    public function getAvatarImageAttribute()
    {
        $filePath = 'public/' . $this->avatar;
        if (!$this->avatar) {
            return url('/storage') . '/' . 'employees/default/avatar.png';
        }
        if (Storage::exists($filePath)) {
            return url('/storage') . '/' . $this->avatar;
        }
        return url('/storage') . '/' . 'employees/default/avatar.png';
    }

    public function approvedLeaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'employee_id')->where('status', LeaveApplication::STATUS_APPROVED)->with('leaveType');
    }


    public function transactions()
    {
        return $this->hasMany(ApplicationTransaction::class, 'employee_id');
    }

    public function periods()
    {
        return $this->belongsToMany(WorkPeriod::class, 'hr_employee_periods', 'employee_id', 'period_id');
    }

    // Log changes to periods
    public function logPeriodChange(array $periodIds, $action)
    {
        EmployeePeriodLog::create([
            'employee_id' => $this->id,
            'period_ids' => json_encode($periodIds), // Store as JSON
            'action' => $action,
        ]);
    }

    // Apply the global scope
    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        } elseif (isStuff()) {
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                // $builder->where('id', auth()->user()->employee->id); // Add your default query here
            });
        }
    }

    public function getHasUserAttribute()
    {
        if ($this->user()->exists()) {
            return true;
        }
        return false;
    }

    public function monthlyIncentives()
    {
        return $this->hasMany(EmployeeMonthlyIncentive::class);
    }

    public function allowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function overtimes()
    {
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')->where('approved', 1);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Calculate total work hours for a specific period and date.
     *
     * @param int $periodId
     * @param string $date
     * @return string
     */

    public function calculateTotalWorkHours($periodId, $date)
    {
        // Get attendances for the specified period and date, sorted by check_time
        $attendances = $this->attendances()
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->orderBy('id')
            ->get();
        $totalMinutes = 0;

        // Loop through attendances to calculate total minutes worked
        for ($i = 0; $i < $attendances->count(); $i++) {
            $checkIn = $attendances[$i];

            // Ensure the current record is a check-in
            if ($checkIn->check_type === 'checkin') {
                // Look for the next check-out
                $i++;
                if ($i < $attendances->count()) {
                    $checkOut = $attendances[$i];

                    // Ensure it is indeed a check-out
                    if ($checkOut->check_type === 'checkout') {
                        $checkInTime = Carbon::parse("{$checkIn->check_date} {$checkIn->check_time}");
                        $checkOutTime = Carbon::parse("{$checkOut->check_date} {$checkOut->check_time}");

                        // Adjust for midnight crossing
                        if ($checkOutTime < $checkInTime) {
                            $checkOutTime->addDay(); // Add 24 hours
                        }

                        // Calculate the time difference in minutes
                        $totalMinutes += $checkInTime->diffInMinutes($checkOutTime);
                    }
                }
            }
        }

        // Convert total minutes to hours and minutes
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        // Ensure positive values (in case of unexpected negatives)
        $totalHours = abs($totalHours);
        $remainingMinutes = abs($remainingMinutes);

        // Format the output as "X h Y minutes"
        return "{$totalHours} h {$remainingMinutes} m";
    }
    public function calculateEmployeeOvertime($employee, $date)
    {
        // Check if the employee has periods
        if ($employee === null || $employee->periods->isEmpty()) {
            return []; // Return an empty array if no periods exist
        }
    
        // Initialize an array to store results
        $results = [];
    
        // Loop through each period
        foreach ($employee->periods as $period) {
            // Get attendances for the specified employee and date within the current period
            $attendances = $this->attendances()
                ->where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->where('check_date', $date)
                ->orderBy('id')
                ->get();
    
            $totalMinutes = 0;
            $checkOutTime = null; // To store checkout time
    
            // Loop through attendances to calculate total minutes worked
            for ($i = 0; $i < $attendances->count(); $i++) {
                $checkIn = $attendances[$i];
    
                // Ensure the current record is a check-in
                if ($checkIn->check_type === 'checkin') {
                    // Look for the next check-out
                    $i++;
                    if ($i < $attendances->count()) {
                        $checkOut = $attendances[$i];
    
                        // Ensure it is indeed a check-out
                        if ($checkOut->check_type === 'checkout') {
                            $checkInTime = Carbon::parse("{$checkIn->check_date} {$checkIn->check_time}");
                            $checkOutTime = Carbon::parse("{$checkOut->check_date} {$checkOut->check_time}");
    
                            // Adjust for midnight crossing
                            if ($checkOutTime < $checkInTime) {
                                $checkOutTime->addDay(); // Add 24 hours
                            }
    
                            // Calculate the time difference in minutes
                            $totalMinutes += $checkInTime->diffInMinutes($checkOutTime);
                        }
                    }
                }
            }
    
            // Convert the supposed duration string "HH:MM" to total minutes
            list($hours, $minutes) = explode(':', $period->supposed_duration);
            $supposedDurationMinutes = ($hours * 60) + $minutes; // Convert to total minutes
            
            if ($totalMinutes > ($supposedDurationMinutes + Attendance::getMinutesByConstant(Setting::getSetting('period_allowed_to_calculate_overtime')))) {
                // Calculate the overtime minutes
                $overtimeMinutes = $totalMinutes - $supposedDurationMinutes;
    
                // Format the overtime into hours and minutes
                $overtimeHours = floor($overtimeMinutes / 60);
                $remainingMinutes = $overtimeMinutes % 60;
    
                // Format as "X h Y m"
                $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";
    
                // Determine the start time (end of the supposed period) and end time (checkout time)
                // $supposedEndTime = Carbon::parse("{$period->end_time}"); // Assuming you have supposed_end_time in your period
              
                $overtimeStartTime = $period->end_at;
                $overtimeEndTime = $checkOutTime; // End of overtime is the checkout time
    
                if (Setting::getSetting('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR && Setting::getSetting('calculating_overtime_with_half_hour_after_hour')) {
                    $overtimeHours = round($overtimeHours, 2);
                }
                $results[] = [
                    'employee_id' => $employee->id,
                    'period_id' => $period->id,
                    'supposed_duration_minutes' => (int) $overtimeMinutes,
                    'overtime_hours' => $overtimeHours,
                    'overtime' => $formattedOvertime,
                    'overtime_start_time' => $overtimeStartTime, // Return as "HH:MM:SS"
                    'overtime_end_time' => $overtimeEndTime->toTimeString(), // Return as "HH:MM:SS"
                ];
            }
        }
    
        return $results; // Return the results
    }

    
}
