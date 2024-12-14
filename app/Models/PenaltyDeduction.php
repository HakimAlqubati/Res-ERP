<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenaltyDeduction extends Model
{
    use HasFactory, SoftDeletes;

    // The table associated with the model.
    protected $table = 'hr_penalty_deductions';

    // Fillable fields for mass assignment
    protected $fillable = [
        'employee_id',
        'deduction_id',
        'penalty_amount',
        'description',
        'month',
        'year',
        'deduction_type',
        'status',
        'created_by',
        'approved_by',
        'rejected_by',
        'rejected_reason',
        'percentage',
        'date',
        'approved_at',
        'rejected_at',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class, 'deduction_id');
    }

    // Helper method to apply penalty deduction
    public static function applyPenalty($employeeId, $deductionId, $penaltyAmount, $description, $month, $year, $createdBy, $deductionType = 'based_on_selected_deduction')
    {
        // Check if penalty exists for this employee in the given month and year
        $existingPenalty = self::where('employee_id', $employeeId)
            ->where('deduction_id', $deductionId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existingPenalty) {
            // If penalty exists, update it
            $existingPenalty->penalty_amount = $penaltyAmount;
            $existingPenalty->description = $description;
            $existingPenalty->deduction_type = $deductionType; // Update the deduction type
            $existingPenalty->status = 'pending'; // Reset status to pending for review
            $existingPenalty->created_by = $createdBy;
            $existingPenalty->save();
        } else {
            // Otherwise, create a new penalty record
            self::create([
                'employee_id' => $employeeId,
                'deduction_id' => $deductionId,
                'penalty_amount' => $penaltyAmount,
                'description' => $description,
                'month' => $month,
                'year' => $year,
                'deduction_type' => $deductionType, // Set the deduction type
                'status' => 'pending',
                'created_by' => $createdBy
            ]);
        }
    }

    // Approve the penalty deduction
    public function approvePenalty($approvedBy,$approvedAt)
    {
        $this->status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_at = $approvedAt;
        $this->save();
    }

    // Reject the penalty deduction
    public function rejectPenalty($rejectedBy, $rejectedReason,$rejectedAt)
    {
        $this->status = 'rejected';
        $this->rejected_by = $rejectedBy;
        $this->rejected_reason = $rejectedReason;
        $this->rejected_at = $rejectedAt;
        $this->save();
    }

    // Scope to get all pending penalties
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope to get all approved penalties
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Scope to get all rejected penalties
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper method to get the full status name (optional)
    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];

        return $statusLabels[$this->status] ?? 'Unknown';
    }


    // Constants for the 'deduction_type' enum values
    // const DEDUCTION_TYPE_BASED_ON_SELECTED_DEDUCTION = 'based_on_selected_deduction';
    const DEDUCTION_TYPE_FIXED_AMOUNT = 'fixed_amount';
    const DEDUCTION_TYPE_SPECIFIC_PERCENTAGE = 'specific_percentage';

    //Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    // Optional: You can define a method to retrieve the list of deduction_type options
    public static function getDeductionTypeOptions()
    {
        return [
            // self::DEDUCTION_TYPE_BASED_ON_SELECTED_DEDUCTION => 'Based on Selected Deduction',
            self::DEDUCTION_TYPE_FIXED_AMOUNT => 'Fixed Amount',
            self::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE => 'Specific Percentage',
        ];
    }
    // Helper method to return deduction type as a label
    public function getDeductionTypeLabelAttribute()
    {
        $deductionTypeLabels = [
            'based_on_selected_deduction' => 'Based on Selected Deduction',
            'fixed_amount' => 'Fixed Amount',
            'specific_percentage' => 'Specific Percentage'
        ];

        return $deductionTypeLabels[$this->deduction_type] ?? 'Unknown';
    }
}
