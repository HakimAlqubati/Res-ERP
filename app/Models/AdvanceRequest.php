<?php

namespace App\Models;

use Throwable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceRequest extends Model
{
    use HasFactory;

    protected $table = 'hr_advance_requests';

    protected $fillable = [
        'application_id',
        'application_type_id',
        'application_type_name',
        'employee_id',
        'advance_amount',
        'monthly_deduction_amount',
        'deduction_ends_at',
        'number_of_months_of_deduction',
        'date',
        'deduction_starts_from',
        'reason',
    ];

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function installments()
    {
        return $this->hasMany(EmployeeAdvanceInstallment::class, 'transaction_id');
    }
    public static function createInstallments($employeeId, $totalAmount, $numberOfMonths, $startDate, $applicationId)
    {
        try {
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
                    'installment_amount' => round($installmentAmount, 2),
                    'due_date' => $dueDate->copy()->addMonths($i),
                    'is_paid' => false,
                ]);
            }
        } catch (Throwable $th) {
            //throw $th;
        }
    }
    // You can define other relationships or methods as needed
}
