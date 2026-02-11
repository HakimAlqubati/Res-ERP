<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarryForward extends Model
{
    use SoftDeletes;

    protected $table = 'hr_carry_forward';

    protected $fillable = [
        'employee_id',
        'from_payroll_run_id',
        'year',
        'month',
        'total_amount',
        'settled_amount',
        'remaining_balance',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount'      => 'decimal:2',
        'settled_amount'    => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'year'              => 'integer',
        'month'             => 'integer',
    ];

    /**
     * The employee who has the deficit.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The payroll run that caused the deficit.
     */
    public function sourceRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'from_payroll_run_id');
    }

    /**
     * The user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
