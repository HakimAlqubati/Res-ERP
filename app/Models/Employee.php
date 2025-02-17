<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
        'bank_account_number',
        'bank_information',
        'gender',        // New field
        'nationality',   // New field
        'passport_no',
        'mykad_number',
        'tax_identification_number',
        'has_employee_pass',
        'working_hours',
        'manager_id',
    ];

    public $appends = ['avatar_image'];
    protected $casts = [
        'bank_information' => 'array',
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

        if ($this->avatar && Storage::disk('s3')->exists($this->avatar)) {
            return Storage::disk('s3')->url($this?->avatar);
        }
        if (!$this->avatar) {
            return url('/storage') . '/' . 'employees/default/avatar.png';
        }

        // $filePath = 'public/' . $this->avatar;
        // if (Storage::exists($filePath)) {
        //     return url('/storage') . '/' . $this->avatar;
        // }
        // return url('/storage') . '/' . 'employees/default/avatar.png';
    }
    public function getAvatarImage2Attribute()
    {
        $filePath = 'public/' . $this->avatar;

        if (Storage::exists($filePath) && ($this->avatar != 'employees/default/avatar.png' || $this->avatar != null)) {
            $arr = explode('/', $this->avatar);
            if (is_array($arr) && count($arr) > 0) {

                return $arr[1] ?? $this->avatar;
            }
            return $this->avatar;
        } else if ($this->avatar == null) {
            return 'no';
        }
        return 'no';
    }

    public function leaveApplications()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')->where('hr_employee_applications.application_type_id', 1)->with('leaveRequest');
    }
    public function approvedLeaveApplications()
    {
        return $this->hasMany(EmployeeApplicationV2::class, 'employee_id')->where('hr_employee_applications.application_type_id', 1)->where('status', EmployeeApplicationV2::STATUS_APPROVED)->with('leaveRequest');
    }

    public function transactions()
    {
        return $this->hasMany(ApplicationTransaction::class, 'employee_id');
    }
    public function approvedAdvanceApplication()
    {
        return $this->hasMany(EmployeeApplication::class, 'employee_id')
            ->where('status', EmployeeApplication::STATUS_APPROVED)
            ->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST)
        ;
    }
    public function approvedLeaveApplication()
    {
        return $this->hasMany(EmployeeApplication::class, 'employee_id')
            ->where('status', EmployeeApplication::STATUS_APPROVED)
            ->where('application_type_id', EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST)
        ;
    }

    // public function getApprovedAdvanceApplicationAttribute()
    // {
    //     return $this->approvedAdvanceApplication()->get();
    // }


    // Custom attribute to fetch approved advance applications
    public function getApprovedAdvanceApplicationAttribute()
    {
        return $this->approvedAdvanceApplication()->get()->map(function ($application) {
            // dd($application->paid_installments_count);  
            return [
                'id' => $application->id,
                'paid' => $application->paid_installments_count,
                'details' => json_decode($application->details, true), // Parse the JSON details
                'created_at' => $application->created_at->format('Y-m-d'),
                'updated_at' => $application->updated_at->format('Y-m-d'),
            ];
        });
    }

    // Custom attribute to fetch approved leave requests
    public function getApprovedLeaveRequestsAttribute()
    {
        return $this->approvedLeaveApplication()->get()->map(function ($leaveRequest) {
            return [
                'id' => $leaveRequest->id,
                'leave_type_id' => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_leave_type_id'] ?? null : null,
                'from_date' => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_from_date'] ?? null : null,
                'to_date' => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_to_date'] ?? null : null,
                'days_count' => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_days_count'] ?? null : null,
                'created_at' => $leaveRequest->created_at->format('Y-m-d'),
                'updated_at' => $leaveRequest->updated_at->format('Y-m-d'),
            ];
        });
    }

    public function periods()
    {
        return $this->belongsToMany(WorkPeriod::class, 'hr_employee_periods', 'employee_id', 'period_id');
    }
    public function periodHistories()
    {
        return $this->hasMany(EmployeePeriodHistory::class,);
    }
    public function advancedInstallments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class,);
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



    /**
     * Get the total number of hours worked by the employee based on their assigned periods.
     *
     * @return int
     */
    public function getHoursCountAttribute()
    {
        $totalHours = 0;

        // Loop through each period and calculate the difference in hours
        foreach ($this->periods as $period) {
            $start = Carbon::parse($period->start_at);
            $end = Carbon::parse($period->end_at);
            $totalHours += $start->diffInHours($end);
        }

        return $totalHours;
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
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')->where('approved', 1)->day();
    }
    public function overtimesByDate($date)
    {
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')->day()
            ->where('approved', 1)
            ->where('date', $date);
    }
    public function overtimesofMonth($date)
    {
        $startOfMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($date)->endOfMonth()->toDateString();
        return $this->hasMany(EmployeeOvertime::class, 'employee_id')->day()
            ->where('approved', 1)
            ->whereBetween('date', [$startOfMonth, $endOfMonth]);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendancesByDate($date)
    {
        return $this->hasMany(Attendance::class)->where('check_date', $date);
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
            ->where('accepted', 1)
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
            $checkInTime = null;
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
                // $overtimeHours = floor($overtimeMinutes / 60);
                $overtimeHours = round($overtimeMinutes / 60 * 2) / 2;
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
                    'check_in_time' => $checkIn->check_time, // Check-in time
                    'check_out_time' => $checkOut->check_time, // Check-out time
                ];
            }
        }
        return $results; // Return the results
    }


    /**
     * Scope query to only include employees with manager-level employee types (1, 2, 3)
     * 
     * Example usage:
     * Employee::employeeTypesManagers()->get(); // Gets all employees who are managers
     * Employee::employeeTypesManagers()->where('active', 1)->get(); // Gets active manager employees
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmployeeTypesManagers($query)
    {
        return;
        return $query->whereIn('employee_type', [1, 2, 3]);
    }


    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->whereNotNull('branch_id')->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        } elseif (isStuff()) {
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                // dd(auth()->user()->employee->id);
                // $builder->where('id', auth()->user()->employee->id); // Add your default query here
            });
        }
    }


    // Define the tax brackets
    public const TAX_BRACKETS = [
        [0, 5000, 0],          // 0 - 5,000 -> 0%
        [5001, 20000, 1],      // 5,001 - 20,000 -> 1%
        [20001, 35000, 3],     // 20,001 - 35,000 -> 3%
        [35001, 50000, 8],     // 35,001 - 50,000 -> 8%
        [50001, 70000, 13],    // 50,001 - 70,000 -> 13%
        [70001, 100000, 21],   // 70,001 - 100,000 -> 21%
        [100001, 250000, 24],  // 100,001 - 250,000 -> 24%
        [250001, 400000, 25],  // 250,001 - 400,000 -> 25%
        [400001, 600000, 26],  // 400,001 - 600,000 -> 26%
        [600001, 1000000, 28], // 600,001 - 1,000,000 -> 28%
        [1000001, 2000000, 30], // 1,000,001 - 2,000,000 -> 30%
        [2000001, PHP_INT_MAX, 32] // Above 2,000,000 -> 32%
    ];

    /**
     * Get the tax percentage based on the employee's salary for nationality 'MY'.
     *
     * @return int|null Tax percentage or null if not applicable.
     */
    public function getTaxPercentageAttribute()
    {
        // Only calculate for nationality 'MY'
        if ($this->nationality !== 'MY') {
            return 0; // No tax percentage for non-'MY' nationality
        }

        $salary = $this->salary;

        foreach (self::TAX_BRACKETS as $bracket) {
            [$min, $max, $percentage] = $bracket;

            if ($salary >= $min && $salary <= $max) {
                return $percentage;
            }
        }

        return 0; // Return null if no matching bracket is found
    }

    public function branchLogs()
    {
        return $this->hasMany(EmployeeBranchLog::class);
    }

    // Create an accessor for 'is_citizen' based on nationality
    public function getIsCitizenAttribute()
    {
        // Retrieve the global default nationality from config
        $defaultNationality = setting('default_nationality');

        // Return true if employee nationality matches the default nationality
        return $this->nationality == $defaultNationality;
    }

    // Create an accessor for 'is_foreign' (if needed)
    public function getIsForeignAttribute()
    {
        return !$this->is_citizen; // This will return the opposite of is_citizen
    }
    public function getGenderTitleAttribute()
    {
        switch ($this->gender) {
            case 1:
                return 'Male';
                break;
            case 0:
                return 'Female';
                break;

            default:
                return 'Not set';
                break;
        }
    }


    public function approvedPenaltyDeductions()
    {
        return $this->hasMany(PenaltyDeduction::class)->where('status', 'approved');
    }


    public function getApprovedPenaltyDeductionsForPeriod($year, $month)
    {
        return $this->approvedPenaltyDeductions()
            ->select('year', 'month', 'penalty_amount', 'deduction_id', 'hr_deductions.name as deduction_name')
            ->leftJoin('hr_deductions', 'hr_deductions.id', '=', 'hr_penalty_deductions.deduction_id')
            ->where('year', $year)
            ->where('month', $month)
            ->get();
    }

    // علاقة الموظف بالمدير (كل موظف له مدير واحد)
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    // علاقة الموظف بالموظفين الذين يديرهم
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id', 'id');
    }

    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'manager_id');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }


    public function managers()
    {
        return $this->hasManyThrough(Employee::class, Department::class, 'id', 'department_id', 'department_id', 'manager_id');
    }
}
