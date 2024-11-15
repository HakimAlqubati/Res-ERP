<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    use HasFactory;

    protected $table = 'hr_task_logs';

    // Constants for log types
    const TYPE_CREATED = 'created';
    const TYPE_MOVED = 'moved';
    const TYPE_EDITED = 'edited';
    const TYPE_REJECTED = 'rejected';
    const TYPE_COMMENTED = 'commented';
    const TYPE_IMAGES_ADDED = 'images_added';

    // Fillable attributes
    protected $fillable = [
        'task_id',
        'created_by',
        'description',
        'log_type',
        'details',
        'total_hours_taken',
    ];

    // Define the relationship with the Task model
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Define the relationship with the User model
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function formatTimeDifferenceFromString(?string $time): string
    {
        // Return an empty string or a default message if $time is null
        if ($time === null) {
            return 'N/A'; // or you could return an empty string ''
        }
        
        // Parse the input time string (HH:MM:SS)
        list($hours, $minutes, $seconds) = explode(':', $time);
        
        // Convert hours, minutes, and seconds to integers
        $hours = (int) $hours;
        $minutes = (int) $minutes;
        $seconds = (int) $seconds;
        
        // Calculate days and remaining hours
        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        // Format the result
        if ($days > 0) {
            return sprintf("%dd %dh %dm", $days, $remainingHours, $minutes);
        } elseif ($remainingHours > 0) {
            return sprintf("%dh %dm", $remainingHours, $minutes);
        } else {
            // Less than 1 hour, show minutes and seconds only
            return sprintf("%dm %ds", $minutes, $seconds);
        }
    }


}
