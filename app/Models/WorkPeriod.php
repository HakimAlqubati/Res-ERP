<?php

namespace App\Models;

use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class WorkPeriod extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, BranchScope;
    protected $table = 'hr_work_periods';

    // Define fillable fields
    protected $fillable = [
        'id',
        'name',
        'description',
        'active',
        'start_at',
        'end_at',
        'allowed_count_minutes_late',
        'days',
        'created_by',
        'updated_by',
        'branch_id',
        'all_branches',
        'day_and_night',
    ];
    protected $auditInclude = [
        'id',
        'name',
        'description',
        'active',
        'start_at',
        'end_at',
        'allowed_count_minutes_late',
        'days',
        'created_by',
        'updated_by',
        'branch_id',
        'all_branches',
        'day_and_night',
    ];

    // Relationship to the user who created the work period
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the work period
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Accessor for supposed_duration
    public function getSupposedDurationAttribute()
    {
        // Parse start_at and end_at using Carbon
        $start = Carbon::parse($this->start_at);
        $end   = Carbon::parse($this->end_at);

        // If end_at is before start_at, it's an overnight shift, so add a day to the end time
        if ($end->lt($start)) {
            $end->addDay();
        }

        // Calculate the difference in total minutes
        $totalMinutes = $start->diffInMinutes($end);

        // Convert minutes to hours with decimal (fractional hours)
        $hours   = intdiv($totalMinutes, 60); // Get whole hours
        $minutes = $totalMinutes % 60;        // Get remaining minutes

        // Return the duration as hours + decimal (fractional) part for the minutes
        // $result = $hours + round($minutes / 60, 2);
        $result = sprintf('%02d:%02d', $hours, $minutes);
        return $result;
    }

    // Function to calculate the total supposed duration for a given number of days
    public function calculateTotalSupposedDurationForDays(int $days)
    {
        // Parse start_at and end_at
        $start = Carbon::parse($this->start_at);
        $end   = Carbon::parse($this->end_at);

        // Handle overnight shifts by adding a day if necessary
        if ($end->lt($start)) {
            $end->addDay();
        }

        // Calculate total minutes for one day
        $totalMinutesPerDay = $start->diffInMinutes($end);

        // Multiply total minutes by the number of days
        $totalMinutes = $totalMinutesPerDay * $days;

        return $totalMinutes;
        // Convert minutes to hours and minutes
        $totalHours       = intdiv($totalMinutes, 60);
        $remainingMinutes = $totalMinutes % 60;

        // Return the total duration as a formatted string (e.g., 5 days as "12h 30m")
        $result = sprintf('%02d h %02d m', $totalHours, $remainingMinutes);

        return $result;
    }

    protected static function booted()
    {
        // Branch scope logic moved to ApplyBranchScopes middleware
        // to avoid relationship issues during model boot cycle.
        // See: app/Http/Middleware/ApplyBranchScopes.php
    }

    // داخل WorkPeriod.php

    public function employeePeriodDays()
    {
        return $this->hasManyThrough(
            EmployeePeriodDay::class,
            EmployeePeriod::class,
            'period_id',            // Foreign key on EmployeePeriod
            'employee_period_id',   // Foreign key on EmployeePeriodDay
            'id',                   // Local key on WorkPeriod
            'id'                    // Local key on EmployeePeriod
        );
    }
}
