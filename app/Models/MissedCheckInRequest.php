<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class MissedCheckInRequest extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $table = 'hr_missed_check_in_requests';

    protected $fillable = [
        'application_id',
        'application_type_id',
        'application_type_name',
        'employee_id',
        'date',
        'time',
        'reason',
    ];
    protected $auditInclude = [
        'application_id',
        'application_type_id',
        'application_type_name',
        'employee_id',
        'date',
        'time',
        'reason',
    ];

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // You can define other relationships or methods as needed
}
