<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePaymentMethod extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employee_payment_method';

    protected $fillable = [
        'name',
        'active',
        'created_by',
    ];
}
