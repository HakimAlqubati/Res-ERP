<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'code',
        'status',
        'total_due',
        'remaining_total',
        'paid_installments'

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
    public static function createInstallments(
        $employeeId,
        $totalAmount,
        $numberOfMonths,
        string|\DateTimeInterface $startMonth, // "2025-09-01" (بداية الشهر)
        $applicationId
    ) {
        try {
            if ($numberOfMonths <= 0 || $totalAmount <= 0) return;
            DB::transaction(function () use ($employeeId, $totalAmount, $numberOfMonths, $startMonth, $applicationId) {
                $base = floor(($totalAmount / $numberOfMonths) * 100) / 100; // تقليم لقرشين
                $acc  = round($base * $numberOfMonths, 2);
                $last = round($totalAmount - $acc + $base, 2); // تعويض الفارق في الأخير
    
                $installmentAmount = $totalAmount / $numberOfMonths;
                $cursor = Carbon::parse($startMonth)->startOfMonth();
                // Loop through each month to create installments
                for ($i = 0; $i < $numberOfMonths; $i++) {
                    $slice = ($i === $numberOfMonths) ? $last : $base;

                    EmployeeAdvanceInstallment::create([
                        'employee_id' => $employeeId,
                        'application_id' => $applicationId,
                        'installment_amount' => $slice,
                        'due_date'           => $cursor->toDateString(), // لو موجود مسبقاً
                        'is_paid' => false,
                        'due_month'          => $cursor->toDateString(),
                        'status'             => 'due',

                    ]);
                    $cursor->addMonth();
                }
            });
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    // You can define other relationships or methods as needed
}
