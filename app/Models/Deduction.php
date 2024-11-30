<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_deductions';
    protected $fillable = [
        'name',
        'description',
        'is_monthly',
        'active',
        'is_penalty',
        'is_specific',
        'amount',
        'percentage',
        'is_percentage',
        'nationalities_applied',
        'condition_applied',
        'less_salary_to_apply',
    ];

    // Add constants for the 'condition_applied' enum values
    const CONDITION_ALL = 'all';
    const CONDITION_SPECIFIED_NATIONALITIES = 'specified_nationalities';
    const CONDITION_SPECIFIED_NATIONALITIES_AND_EMP_HAS_PASS = 'specified_nationalties_and_emp_has_pass';

    // Optional: You can define a method to retrieve the list of condition_applied values
    public static function getConditionAppliedOptions()
    {
        return [
            self::CONDITION_ALL => 'All',
            self::CONDITION_SPECIFIED_NATIONALITIES => 'Specified Nationalities',
            self::CONDITION_SPECIFIED_NATIONALITIES_AND_EMP_HAS_PASS => 'Specified Nationalities and Employee Has Pass',
        ];
    }

    // Define a model scope for filtering by 'condition_applied'
    public function scopeConditionApplied($query, $condition)
    {
        // Filter the deductions based on the condition_applied
        return $query->where('is_specific', 0)->where('active', 1)->where('condition_applied', $condition);
    }
}
