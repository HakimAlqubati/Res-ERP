<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'employer_amount',
        'financial_category_id',  // للربط مع الفئة المالية
    ];

    /**
     * Get the financial category associated with this deduction.
     * If set, a separate financial transaction will be created when payroll is processed.
     */
    public function financialCategory()
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    /**
     * Check if this deduction should create a financial transaction.
     */
    public function hasFinancialCategory(): bool
    {
        return !is_null($this->financial_category_id);
    }

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

    public function getAppliedByLabelAttribute(){
        return self::getAppliedByOptions()[$this->applied_by];
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
     * @param float $salary The monthly salary amount to calculate tax for.
     * @return array Detailed tax calculation including brackets applied.
     */
    public function calculateTax(float $salary): array
    {
        $brackets = $this->brackets()->orderBy('min_amount')->get();

        $annualSalary = $salary * 12;
        $tax = 0;
        $appliedBrackets = [];
        $currentBracket = null;

        foreach ($brackets as $bracket) {
            $bracketMin = (float) $bracket['min_amount'];
            $bracketMax = (float) $bracket['max_amount'];
            $bracketPercentage = (float) $bracket['percentage'];

            if ($annualSalary <= $bracketMin) {
                continue;
            }

            if ($annualSalary > $bracketMax) {
                $taxableInBracket = $bracketMax - $bracketMin;
                $taxFromBracket = $taxableInBracket * ($bracketPercentage / 100);
            } else {
                $taxableInBracket = $annualSalary - $bracketMin;
                $taxFromBracket = $taxableInBracket * ($bracketPercentage / 100);
                // This is the bracket where salary falls
                $currentBracket = [
                    'min' => $bracketMin,
                    'max' => $bracketMax,
                    'percentage' => $bracketPercentage,
                ];
            }

            $tax += $taxFromBracket;

            if ($taxFromBracket > 0) {
                $appliedBrackets[] = [
                    'min' => $bracketMin,
                    'max' => $bracketMax,
                    'percentage' => $bracketPercentage,
                    'taxable_amount' => round($taxableInBracket, 2),
                    'tax_amount' => round($taxFromBracket, 2),
                ];
            }

            if ($annualSalary <= $bracketMax) {
                break;
            }
        }

        $effectivePercentage = $annualSalary > 0 ? round(($tax / $annualSalary) * 100, 4) : 0;
        $monthlyTax = round($tax / 12, 2);

        // Simple, concise notes - only the essential info
        if ($currentBracket) {
            $notes = sprintf(
                "Effective rate: %.2f%% (Bracket: %.0f-%.0f @ %.1f%%)",
                $effectivePercentage,
                $currentBracket['min'],
                $currentBracket['max'],
                $currentBracket['percentage']
            );
        } else {
            $notes = sprintf("Effective rate: %.2f%%", $effectivePercentage);
        }

        return [
            'total_tax' => round($tax, 2),
            'monthly_tax' => $monthlyTax,
            'effective_percentage' => $effectivePercentage,
            'applied_brackets' => $appliedBrackets,
            'notes' => $notes,
            'annual_salary' => $annualSalary,
            'current_bracket' => $currentBracket,
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
