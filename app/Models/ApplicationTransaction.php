<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationTransaction extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_application_transactions';
    protected $fillable = [
        'application_id',
        'transaction_type_id',
        'transaction_type_name',
        'transaction_description',
        'submitted_on',
        'amount',
        'remaining',
        'from_date',
        'to_date',
        'created_by',
        'employee_id',
        'is_canceled',
        'canceled_at',
        'cancel_reason',
        'details',
        'branch_id',
        'value',
    ];

    // Constants for transaction types
    const APPLICATION_TYPES = [
        1 => 'Leave request',
        2 => 'Missed Check-in Request',
        3 => 'Advance request',
        4 => 'Missed Check-out Request',
        5 => 'Opening balance of employee leave',
    ];

    // Define relationships
    public function application()
    {
        return $this->belongsTo(EmployeeApplication::class, 'application_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Helper function to get the application type name
    public function getTransactionTypeNameAttribute()
    {
        return self::APPLICATION_TYPES[$this->transaction_type_id] ?? 'Unknown';
    }

    public static function createTransactionFromApplication(EmployeeApplication $employeeApplication)
    {
        $amount = 0;
        if ($employeeApplication->amount) {
            $amount = $employeeApplication->amount;
        }
        $remaining = 0;
        if ($employeeApplication->remaining) {
            $remaining = $employeeApplication->remaining;
        }
        $fromDate = null;
        if ($employeeApplication->from_date) {
            $fromDate = $employeeApplication->from_date;
        }
        $toDate = null;
        if ($employeeApplication->to_date) {
            $toDate = $employeeApplication->to_date;
        }
        return self::create([
            'application_id' => $employeeApplication->id,
            'transaction_type_id' => $employeeApplication->transaction_type_id,
            'transaction_type_name' => self::APPLICATION_TYPES[$employeeApplication->transaction_type_id] ?? 'Unknown',
            'transaction_description' => 'Transaction created for Employee Application Approval',
            'submitted_on' => now(),
            'amount' => $amount,
            'remaining' => $remaining,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'created_by' => auth()->id(), // أو يمكن تحديد ID المستخدم
            'employee_id' => $employeeApplication->employee_id,
            'details' => json_encode(['source' => 'Employee Application Approval']),
        ]);
    }

    /**
     * Create a new ApplicationTransaction.
     *
     * @param array $data
     * @return ApplicationTransaction
     */
    // Create transaction method
    public static function createTransactionFromApplicationV2(
        int $applicationId,
        int $transactionTypeId,
        float $amount = 0,
        float $remaining = null,
        string $fromDate = null,
        string $toDate = null,
        int $createdBy = null,
        int $employeeId = null,
        bool $isCanceled = false,
        string $canceledAt = null,
        string $cancelReason = null,
        string $details = null,
        int $branchId = null,
        float $value = null
    ) {
        // Get transaction type name and description
        $transactionTypeName = static::APPLICATION_TYPES[$transactionTypeId];
        $transactionDescription = match ($transactionTypeId) {
            1 => "Leave request submitted for dates $fromDate to $toDate",
            2 => 'Missed check-in request processed',
            3 => "Advance request for an amount of $amount",
            4 => 'Missed check-out request processed',
            5 => 'Initial leave balance set',
            default => 'General transaction',
        };

        return self::create([
            'application_id' => $applicationId,
            'transaction_type_id' => $transactionTypeId,
            'transaction_type_name' => $transactionTypeName,
            'transaction_description' => $transactionDescription,
            'submitted_on' => now(),
            'amount' => $amount,
            'remaining' => $remaining,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'created_by' => $createdBy ?? auth()->id(),
            'employee_id' => $employeeId,
            'is_canceled' => $isCanceled,
            'canceled_at' => $canceledAt,
            'cancel_reason' => $cancelReason,
            'details' => $details ?? null,
            'branch_id' => $branchId,
            'value' => $value,
        ]);
    }
}
