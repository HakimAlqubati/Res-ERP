<?php

namespace App\Models;

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

    // You can define other relationships or methods as needed
}
