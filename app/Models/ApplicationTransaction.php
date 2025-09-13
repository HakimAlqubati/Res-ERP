<?php

namespace App\Models;

use Carbon\Carbon;
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
        'year',        // Newly added field
        'month',       // Newly added field
    ];

    // Constants for transaction types
    const TRANSACTION_TYPES = [
        1 => 'Leave request',
        2 => 'Missed Check-in Request',
        3 => 'Advance request',
        4 => 'Missed Check-out Request',
        5 => 'Opening balance of employee leave',
        6 => 'Deucation of advanced',
    ];
    // Define relationships
    public function application()
    {
        return $this->belongsTo(EmployeeApplicationV2::class, 'application_id');
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
        return self::TRANSACTION_TYPES[$this->transaction_type_id] ?? 'Unknown';
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
            'transaction_type_id' => $employeeApplication->application_type_id,
            'transaction_type_name' => static::TRANSACTION_TYPES[$employeeApplication->application_type_id],
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
    public static function createTransactionFromApplicationV3(EmployeeApplication $employeeApplication,array $details)
    {
        // Initialize variables and assign values if they exist in the details array
    $amount = $details['detail_advance_amount'] ?? 0;
    $remaining = $details['detail_number_of_months_of_deduction'] 
                 ? $amount - ($details['detail_monthly_deduction_amount'] * $details['detail_number_of_months_of_deduction']) 
                 : $amount;
    $fromDate = $details['detail_deduction_starts_from'] ?? null;
    $toDate = $details['detail_deduction_ends_at'] ?? null;
       

  $transaction = self::create([
            'application_id' => $employeeApplication->id,
            'transaction_type_id' => $employeeApplication->application_type_id,
            'transaction_type_name' => static::TRANSACTION_TYPES[$employeeApplication->application_type_id],
            'transaction_description' => 'Transaction created for Employee Application Approval',
            'submitted_on' => now(),
            'branch_id'=> $employeeApplication->branch_id,
            'amount' => $amount,
            'value' => $amount,
            'remaining' => $remaining,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'created_by' => auth()->id(), // أو يمكن تحديد ID المستخدم
            'employee_id' => $employeeApplication->employee_id,
            'details' => json_encode([
                'source' => 'Employee Application Approval',
                'detail_date' => $details['detail_date'] ?? null,
                'detail_advance_amount' => $amount,
                'detail_monthly_deduction_amount' => $details['detail_monthly_deduction_amount'] ?? 0,
                'detail_deduction_starts_from' => $fromDate,
                'detail_deduction_ends_at' => $toDate,
                'detail_number_of_months_of_deduction' => $details['detail_number_of_months_of_deduction'] ?? 0,
            ]),
        ]);

          // Check if installments should be created
          if ($employeeApplication->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST) {
            $transaction->createInstallments(
                $employeeApplication->employee_id,
                $amount,
                $details['detail_number_of_months_of_deduction'] ?? 0,
                $fromDate,
                $employeeApplication->id
            );
        }
    return $transaction;
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
        $transactionTypeName = static::TRANSACTION_TYPES[$transactionTypeId];
        $transactionDescription = match ($transactionTypeId) {
            // 1 => "Leave request submitted for  dates $fromDate to $toDate",
            1 => "Approved Leave",
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

    public function createInstallments($employeeId, $totalAmount, $numberOfMonths, $startDate, $applicationId)
    {
        if ($numberOfMonths <= 0) {
            return; // Exit if no installments are specified
        }

        $installmentAmount = $totalAmount / $numberOfMonths;
        $dueDate = Carbon::parse($startDate);

        // Loop through each month to create installments
        for ($i = 0; $i < $numberOfMonths; $i++) {
            EmployeeAdvanceInstallment::create([
                'employee_id' => $employeeId,
                'application_id' => $applicationId,
                'transaction_id' => $this->id,
                'installment_amount' => round($installmentAmount, 2),
                'due_date' => $dueDate->copy()->addMonths($i),
                'is_paid' => false,
            ]);
        }
    }

    // Relationship: Has many installments
    public function installments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'transaction_id');
    }

    /**
     * to create ontly deducation transaction
     * @param mixed $employeeId
     * @param mixed $branchId
     * @param mixed $amount
     * @param mixed $month
     * @param mixed $year
     * @return 
     */
    public static function createMonthlyDeductionTransaction($employeeId, $branchId, $amount, $month, $year)
    {
        // Create a deduction transaction for advanced payments
        return self::create([
            'application_id' => null, // Set to null or an appropriate ID if linked to an application
            'transaction_type_id' => 6, // Deduction of advanced
            'transaction_type_name' => self::TRANSACTION_TYPES[6],
            'transaction_description' => 'Monthly deduction for advance payment',
            'submitted_on' => now(),
            'branch_id' => $branchId,
            'amount' => $amount,
            'value' => $amount,
            'remaining' => 0, // Adjust as needed based on calculations
            'from_date' => now()->startOfMonth(),
            'to_date' => now()->endOfMonth(),
            'created_by' => auth()->id(),
            'employee_id' => $employeeId,
            'year' => $year,
            'month' => $month,
            'details' => json_encode([
                'source' => 'Monthly Salary Generation',
                'deduction_amount' => $amount,
            ]),
        ]);
    }

}
