<?php

namespace App\Models;

use App\Observers\EmployeeRewardObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use App\Traits\Scopes\BranchScope;
use App\Traits\Scopes\StatusScope;

#[ObservedBy([EmployeeRewardObserver::class])]
class EmployeeReward extends Model implements Auditable
{
    use HasFactory,
        SoftDeletes,
        \OwenIt\Auditing\Auditable,
        BranchScope,
        StatusScope;

    protected $table = 'hr_employee_rewards';

    // Status Constants
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'incentive_id',
        'reward_amount',
        'reason',
        'month',
        'year',
        'date',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'date'          => 'date',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
    ];

    // Relationships

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function rewardType()
    {
        return $this->belongsTo(MonthlyIncentive::class, 'incentive_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Helper Methods

    /**
     * Approve the reward
     */
    public function approve(int $userId): bool
    {
        return $this->update([
            'status'      => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the reward
     */
    public function reject(int $userId, string $reason): bool
    {
        return $this->update([
            'status'          => self::STATUS_REJECTED,
            'rejected_by'     => $userId,
            'rejected_reason' => $reason,
            'rejected_at'     => now(),
        ]);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }


}
