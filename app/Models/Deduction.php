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
        'is_mtd_deduction',       // يُطبَّق على الموظفين ذوي is_mtd_applicable=true
        'applied_by',
        'employer_percentage',
        'employer_amount',
        'has_cap',
        'cap_value',
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

    public function getAppliedByLabelAttribute()
    {
        return self::getAppliedByOptions()[$this->applied_by];
    }
    // Define a model scope for filtering by 'condition_applied'
    public function scopeConditionApplied($query, $condition)
    {
        // Filter the deductions based on the condition_applied
        return $query->where('is_specific', 0)->where('active', 1)->where('condition_applied', $condition);
    }


    // Optional: Define constants for the new field 'condition_applied_v2'
    // Optional: Define constants for the new field 'condition_applied_v2'
    const CONDITION_APPLIED_V2_ALL = 'all';
    const CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE = 'citizen_employee';
    const CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS = 'citizen_employee_and_foreign_has_emp_pass';
    const CONDITION_APPLIED_V2_FOREIGN_HAS_EMP_PASS = 'foreign_has_emp_pass';

    public static function getConditionAppliedV2Options()
    {
        return [
            self::CONDITION_APPLIED_V2_ALL => 'All',
            self::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE => 'Local staff',
            self::CONDITION_APPLIED_V2_FOREIGN_HAS_EMP_PASS => 'Expat with EP',
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
    /**
     * Calculate Tax with correct MTD logic but maintaining old output structure.
     *
     * @param float $salary Monthly Gross Salary
     * @param float $totalReliefs (Optional) Total Annual Reliefs (Default 9000 for individual)
     * @param float $zakatAndRebates (Optional) Total Zakat + other rebates
     * @return array
     */

    public function calculateTax(float $salary, float $totalReliefs = 0, float $zakatAndRebates = 0): array
    {
        // 1. استدعاء الشرائح
        $brackets = $this->brackets()->orderBy('min_amount')->get();

        // 2. الحسابات الأولية
        $annualSalary = $salary * 12;
        $chargeableIncome = max(0, $annualSalary - $totalReliefs);

        $grossTax = 0;
        $appliedBrackets = [];
        $currentBracket = null;

        // متغيرات الملاحظات
        $baseTax = 0;
        $excessAmount = 0;
        $excessTax = 0;

        foreach ($brackets as $bracket) {
            $bracketMin = (float) $bracket['min_amount'];
            $bracketMax = (float) $bracket['max_amount'];
            $bracketPercentage = (float) $bracket['percentage'];

            // تخطي الشرائح التي لم يصل إليها الدخل
            if ($chargeableIncome <= $bracketMin) {
                continue;
            }

            // --- تصحيح الخطأ الجوهري هنا ---
            // لحساب المبلغ الخاضع للضريبة داخل الشريحة، يجب معالجة الفجوة بين الشرائح
            // إذا كانت الشرائح (0-5000) ثم (5001-20000)، الفرق هو (Max - Min + 1) للشرائح المكتملة

            if ($chargeableIncome > $bracketMax) {
                // شريحة مكتملة (سابقة)
                // المعادلة الصحيحة: (Max - Min) + 1 لتعويض الفجوة الرقمية
                // مثال: 20000 - 5001 + 1 = 15000
                $taxableInBracket = ($bracketMax - $bracketMin) + 1;

                // حالة خاصة للشريحة الأولى التي تبدأ بصفر (لأن 5000-0 = 5000 ولا تحتاج +1 عادة إلا لو النظام يعتبر الصفر رقم)
                // لكن الأضمن رياضياً مع جدولك هو التعامل مع الـ Min وكأنه (Previous Max)
                // الحل الأبسط والفعال لجدولك هو: ($bracketMax - ($bracketMin - 1))
                $taxableInBracket = $bracketMax - ($bracketMin - 1);

                $taxFromBracket = $taxableInBracket * ($bracketPercentage / 100);
                $baseTax += $taxFromBracket;
            } else {
                // الشريحة الحالية (الأخيرة)
                // المبلغ هو: الدخل - (بداية الشريحة - 1)
                $taxableInBracket = $chargeableIncome - ($bracketMin - 1);

                $taxFromBracket = $taxableInBracket * ($bracketPercentage / 100);

                $currentBracket = [
                    'min' => $bracketMin,
                    'max' => $bracketMax,
                    'percentage' => $bracketPercentage,
                ];

                $excessAmount = $taxableInBracket;
                $excessTax = $taxFromBracket;
            }

            $grossTax += $taxFromBracket;

            if ($taxFromBracket > 0) {
                $appliedBrackets[] = [
                    'min' => $bracketMin,
                    'max' => $bracketMax,
                    'percentage' => $bracketPercentage,
                    'taxable_amount' => round($taxableInBracket, 2),
                    'tax_amount' => round($taxFromBracket, 2),
                ];
            }

            if ($chargeableIncome <= $bracketMax) {
                break;
            }
        }

        // 3. الخصومات النهائية
        $automaticRebate = ($chargeableIncome > 0 && $chargeableIncome <= 35000) ? 400 : 0;
        $totalRebates = $zakatAndRebates + $automaticRebate;

        $finalTax = max(0, $grossTax - $totalRebates);

        // 4. النتائج النهائية
        $monthlyTax = round($finalTax / 12, 2);
        $effectivePercentage = $annualSalary > 0 ? round(($finalTax / $annualSalary) * 100, 4) : 0;

        // 5. الملاحظات
        if ($currentBracket) {
            $notes = sprintf(
                "Gross: %s * 12 = %s | Reliefs: %s | Chargeable: %s | Bracket: %s-%s @ %s%% | Base Tax: %s + Excess Tax: (%s * %s%% = %s) = Total Tax: %s",
                number_format($salary, 2),
                number_format($annualSalary, 2),
                number_format($totalReliefs, 2),
                number_format($chargeableIncome, 2),
                number_format($currentBracket['min']),
                number_format($currentBracket['max']),
                $currentBracket['percentage'],
                number_format($baseTax, 2),
                number_format($excessAmount, 2),
                $currentBracket['percentage'],
                number_format($excessTax, 2),
                number_format($grossTax, 2)
            );
        } else {
            $notes = sprintf("No Tax Payable. Chargeable Income: %s", number_format($chargeableIncome, 2));
        }

        return [
            'total_tax' => round($finalTax, 2),
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
