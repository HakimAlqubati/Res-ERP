<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\DynamicConnection;
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
        'applied_by',
        'employer_percentage',
        'employer_amount'
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

    // Add constants for applied_by field
    const APPLIED_BY_EMPLOYEE = 'employee';
    const APPLIED_BY_EMPLOYER = 'employer';
    const APPLIED_BY_BOTH = 'both';

    // You can also create a method to retrieve the applied_by options
    public static function getAppliedByOptions()
    {
        return [
            self::APPLIED_BY_EMPLOYEE => 'Employee',
            self::APPLIED_BY_EMPLOYER => 'Employer',
            self::APPLIED_BY_BOTH => 'Both',
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
        // Retrieve the tax brackets from the database and sort by 'min_amount'
        $brackets = $this->brackets()->orderBy('min_amount')->get();

        $salary *= 12;
        $tax = 0;
        $previousBracketMax = 0;

        // Loop through each tax bracket and calculate the tax for each applicable range
        foreach ($brackets as $bracket) {
            // If the salary exceeds the current bracket's max_amount, calculate the tax for the full range
            if ($salary > $bracket['max_amount']) {
                $tax += ($bracket['max_amount'] - $bracket['min_amount']) * ($bracket['percentage'] / 100);
            } else {
                // Calculate tax for the remaining amount within the bracket
                $tax += ($salary - $bracket['min_amount']) * ($bracket['percentage'] / 100);
                break; // No need to continue once the salary is fully taxed
            }
        }

        // Return both the total tax and the monthly tax deduction (divide by 12 for monthly)
        $monthlyTax = round($tax / 12, 2);

        return [
            'total_tax' => round($tax, 2),
            'monthly_tax' => $monthlyTax,
        ];
    }
    public function calculateTax_(float $salary): array
    {
        // Retrieve the brackets associated with this deduction
        $brackets = $this->brackets()->orderBy('min_amount')->get();

        return $brackets->toArray();
        $yearlyTax = 0;
        $next = 0;

        // to get yearly salary
        $salary *= 12;
        $previous = 0;
        $res = [];
        // Iterate over each tax bracket
        foreach ($brackets as $i => $bracket) {
            if ($salary >= $bracket->min_amount) {
                if ($bracket->min_amount > 0) {
                    $previous = 1;
                }
                $next = $bracket->max_amount - ($bracket->min_amount - $previous);
                $first = ($bracket->min_amount - $previous);
                $yearlyTax = (($next * $bracket->percentage) / 100);
                $yearlyTax = round($yearlyTax);
                $per = $bracket->percentage;
                $res[] = [
                    'per' => $per,
                    'next' => $next,
                    'first' => $first,
                    'min' => $bracket->min_amount,
                    'max' => $bracket->max_amount,
                    'yearly_tax' => $yearlyTax,
                ];
            }
        }

        return ($res);

        // Calculate monthly tax by dividing yearly tax by 12
        $monthlyTax = $yearlyTax / 12;

        return [
            'yearly' => round($yearlyTax, 2),
            'monthly' => round($monthlyTax, 2),
            'percentage_used' => $per,
            'next' => $next,
        ];
    }

    public static function calculateTax2($salary)
    {
        $annualSalary = $salary * 12;
        $tax = 0;
        $brackets = [
            [0, 5000, 0, 0], // 0 - 5,000: 0%
            [5001, 20000, 1, 150], // 5,001 - 20,000: 1%
            [20001, 35000, 3, 450], // 20,001 - 35,000: 3%
            [35001, 50000, 6, 900], // 35,001 - 50,000: 6%
            [50001, 70000, 11, 2200], // 50,001 - 70,000: 11%
            [70001, 100000, 19, 5700], // 70,001 - 100,000: 19%
            [100001, 400000, 25, 75000], // 100,001 - 400,000: 25%
            [400001, 600000, 26, 52000], // 400,001 - 600,000: 26%
            [600001, 2000000, 28, 392000], // 600,001 - 2,000,000: 28%
            [2000001, INF, 30, 528400], // Above 2,000,000: 30%
        ];

        foreach ($brackets as $bracket) {
            list($start, $end, $rate, $previousTax) = $bracket;

            if ($annualSalary > $start) {
                $taxableIncome = min($annualSalary, $end) - $start;
                $tax += ($taxableIncome * $rate / 100);
            }
        }

        return [
            'annual_tax' => $tax,
            'monthly_tax' => $tax / 12, // Monthly tax deduction
        ];
    }

    /**
     * Scope a query to only include penalty deductions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePenalty($query)
    {
        return $query->where('is_penalty', true);
    }
}
