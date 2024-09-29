<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;
    protected $table = 'hr_leave_applications';

    const STATUS_PENDING = 'pending';
    const STATUS_CANCEL = 'cancel';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Define status labels
    const STATUS_LABEL_PENDING = 'Pending';
    const STATUS_LABEL_CANCEL = 'Cancelled';
    const STATUS_LABEL_APPROVED = 'Approved';
    const STATUS_LABEL_REJECTED = 'Rejected';

    //   Define colors for status
    const STATUS_COLOR_PENDING = '#f0ad4e';
    const STATUS_COLOR_CANCEL = '#ffc107';
    const STATUS_COLOR_APPROVED = '#5bc0de';
    const STATUS_COLOR_REJECTED = '#d9534f';

    protected $fillable = [
        'employee_id',
        'created_by',
        'updated_by',
        'status',
        'leave_reason',
        'approved_by',
        'rejected_by',
        'reject_reason',
        'approved_at',
        'rejected_at',
        'from_date',
        'days_count',
        'to_date',
        'leave_type_id',
        'branch_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return self::STATUS_COLOR_PENDING;
            case self::STATUS_CANCEL:
                return self::STATUS_COLOR_CANCEL;
            case self::STATUS_APPROVED:
                return self::STATUS_COLOR_APPROVED;
            case self::STATUS_REJECTED:
                return self::STATUS_COLOR_REJECTED;
            default:
                return '#ffffff';
        }
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return self::STATUS_LABEL_PENDING;
            case self::STATUS_CANCEL:
                return self::STATUS_LABEL_CANCEL;
            case self::STATUS_APPROVED:
                return self::STATUS_LABEL_APPROVED;
            case self::STATUS_REJECTED:
                return self::STATUS_LABEL_REJECTED;
            default:
                return 'Unknown'; // Default label for unknown statuses
        }
    }

    public static function getStatus()
    {
        return [self::STATUS_PENDING => self::STATUS_LABEL_PENDING,
            self::STATUS_CANCEL => self::STATUS_LABEL_CANCEL,
            self::STATUS_APPROVED => self::STATUS_LABEL_APPROVED,
            self::STATUS_REJECTED => self::STATUS_LABEL_REJECTED];
    }

    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope( function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        }

        if(isStuff()){
            static::addGlobalScope( function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('employee_id', auth()->user()->employee->id); // Add your default query here
            });
        }
    }
}
