<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeApplication extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_employee_applications';
    protected $fillable = [
        'employee_id',
        'branch_id',
        'application_date',
        'status',
        'notes',
        'application_type_id',
        'application_type_name',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'details', // json
        'rejected_reason',
    ];

    // Constants for application types
    const APPLICATION_TYPES = [
        1 => 'Leave request',
        2 => 'Attendance fingerprint request',
        3 => 'Advance request',
        4 => 'Departure fingerprint request',
    ];

    // Constants for application types
    // const APPLICATION_TYPE_LEAVE_REQUEST = 1;
    const APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST = 2;
    // const APPLICATION_TYPE_ADVANCE_REQUEST = 3;
    const APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST = 4;


    const APPLICATION_TYPE_NAMES = [
        // self::APPLICATION_TYPE_LEAVE_REQUEST => 'Leave request',
        self::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'Attendance fingerprint request',
        // self::APPLICATION_TYPE_ADVANCE_REQUEST => 'Advance request',
        self::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => 'Departure fingerprint request',
    ];

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class); // Assuming you have an Employee model
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class); 
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by'); // Assuming a User model
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by'); // Assuming a User model
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by'); // Assuming a User model
    }

    public function getDetailTimeAttribute()
    {
        if ( in_array($this->application_type_id , [2,4])) {
            // Decode the details JSON to an associative array
            $details = json_decode($this->details, true);
            // Return the detail_time if it exists
            return $details['detail_time'] ?? null;
        }

        return null; 
    }
    public function getDetailDateAttribute()
    {
        if ( in_array($this->application_type_id , [2,4])) {
            // Decode the details JSON to an associative array
            $details = json_decode($this->details, true);
            // Return the detail_date if it exists
            return $details['detail_date'] ?? null;
        }

        return null; 
    }
}
