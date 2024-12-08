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
        'condition_applied_v2',
        'has_brackets',
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


    // Optional: Define constants for the new field 'condition_applied_v2'
    const CONDITION_APPLIED_V2_ALL = 'all';
    const CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE = 'citizen_employee';
    const CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS = 'citizen_employee_and_foreign_has_emp_pass';

    public static function getConditionAppliedV2Options()
    {
        return [
            self::CONDITION_APPLIED_V2_ALL => 'All',
            self::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE => 'Local staff',
            self::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS => 'Local staff & expat with EP',
        ];
    }


    // Define the relationship with deduction brackets
    public function brackets()
    {
        return $this->hasMany(DeductionBracket::class, 'deduction_id');
    }


    /**
     * Calculate the tax based on the provided salary.
     *
     * @param float $salary The salary amount to calculate tax for.
     * @return array The tax amount as an array ['yearly' => x, 'monthly' => y].
     */
    public function calculateTax(float $salary): array
    {
        // Retrieve the brackets associated with this deduction
        $brackets = $this->brackets()->orderBy('min_amount')->get();

        $yearlyTax = 0;
        $next = 0;

        // to get yearly salary
        $salary *= 12;
        $previous = 0;
        // Iterate over each tax bracket
        foreach ($brackets as $i => $bracket) {
            if ($salary >= $bracket->min_amount) {
                if ($bracket->min_amount > 0) {
                    $previous = 1;
                }
                $next = $bracket->max_amount - ($bracket->min_amount - $previous);
                $yearlyTax = (($next * $bracket->percentage) / 100);
                $yearlyTax = round($yearlyTax);
                $per = $bracket->percentage;
            }
        }

        // Calculate monthly tax by dividing yearly tax by 12
        $monthlyTax = $yearlyTax / 12;

        return [
            'yearly' => round($yearlyTax, 2),
            'monthly' => round($monthlyTax, 2),
            'percentage_used' => $per,
            'next' => $next,
        ];
    }
}
