<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeePeriod extends Model implements Auditable
{
    use HasFactory,DynamicConnection,\OwenIt\Auditing\Auditable;

    // Define the table name if it's not the plural of the model name
    protected $table = 'hr_employee_periods';

    // Specify primary key if not 'id'
    protected $primaryKey = 'id';

    // If timestamps are not present in the table, set to false
    public $timestamps = false;

    // Define fillable or guarded fields
    protected $fillable = [
        'employee_id',
        'period_id',
        // Add other columns if necessary
    ];
    protected $auditInclude = [
        'employee_id',
        'period_id',
        // Add other columns if necessary
    ];

    /**
     * Relationship with HrWorkPeriod (many-to-one).
     */
    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

    /**
     * Relationship with Employee (many-to-one).
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
