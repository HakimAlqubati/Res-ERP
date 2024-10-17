<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkPeriod extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_work_periods';

    // Define fillable fields
    protected $fillable = [
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
        $end = Carbon::parse($this->end_at);

        // If end_at is before start_at, it's an overnight shift, so add a day to the end time
        if ($end->lt($start)) {
            $end->addDay();
        }

        // Calculate the difference in total minutes
        $totalMinutes = $start->diffInMinutes($end);

        // Convert minutes to hours with decimal (fractional hours)
        $hours = intdiv($totalMinutes, 60); // Get whole hours
        $minutes = $totalMinutes % 60; // Get remaining minutes

        // Return the duration as hours + decimal (fractional) part for the minutes
        // $result = $hours + round($minutes / 60, 2);
        $result = sprintf('%02d:%02d', $hours, $minutes);
        return $result;
    }

   

}
