<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Allowance extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_allowances';
    protected $fillable = ['name', 'description', 'is_monthly', 'active', 'is_specific', 'amount', 'percentage', 'is_percentage', 'financial_category_id'];
    protected $auditInclude = ['name', 'description', 'is_monthly', 'active', 'is_specific', 'amount', 'percentage', 'is_percentage', 'financial_category_id'];
    protected $casts = [
        'is_monthly'   => 'boolean',
        'active'       => 'boolean',
        'is_specific'  => 'boolean',
        'is_percentage' => 'boolean',
        'amount'       => 'decimal:2',
        'percentage'   => 'decimal:2',
    ];

    /**
     * Get the financial category associated with this allowance.
     */
    public function financialCategory()
    {
        return $this->belongsTo(FinancialCategory::class);
    }
}
