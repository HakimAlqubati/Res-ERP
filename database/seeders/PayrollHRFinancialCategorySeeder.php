<?php

namespace Database\Seeders;

use App\Enums\FinancialCategoryCode;
use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;

/**
 * Seeder for HR-related financial categories.
 * 
 * These categories are used for automatic financial integration with:
 * - Payroll system (salaries, allowances, incentives, advances)
 * - Maintenance system (repairs, equipment)
 * 
 * All categories have is_visible = false as they are system-managed.
 */
class PayrollHRFinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // ==================== PAYROLL CATEGORIES ====================
            [
                'name' => 'Net Salaries',
                'code' => FinancialCategoryCode::PAYROLL_SALARIES,
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,
                'is_visible' => false,
                'description' => 'Net employee salaries (includes base salary, allowances, incentives minus internal deductions)',
            ],
            [
                'name' => 'Employee Advances',
                'code' => FinancialCategoryCode::PAYROLL_ADVANCES,
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,
                'is_visible' => false,
                'description' => 'Employee advance payments (loans)',
            ],
           

            // ==================== MAINTENANCE CATEGORIES ====================
            [
                'name' => 'Maintenance & Repairs',
                'code' => FinancialCategoryCode::MAINTENANCE_REPAIR,
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,
                'is_visible' => false,
                'description' => 'Equipment maintenance and repair costs',
            ],
            [
                'name' => 'Equipment Purchase',
                'code' => FinancialCategoryCode::EQUIPMENT_PURCHASE,
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,
                'is_visible' => false,
                'description' => 'New equipment purchases',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($categories as $category) {
            $result = FinancialCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );

            if ($result->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("✅ HR Financial categories seeded successfully!");
        $this->command->info("   - Created: {$created}");
        $this->command->info("   - Updated: {$updated}");
    }
}
