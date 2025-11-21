<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // ==================== INCOME CATEGORIES ====================
            [
                'name' => 'Branch Sales',
                'type' => FinancialCategory::TYPE_INCOME,
                'is_system' => true,  // Automated from Orders/PosSales
                'is_visible' => false, // Hidden from manual entry
            ],
            [
                'name' => 'Reseller Sales',
                'type' => FinancialCategory::TYPE_INCOME,
                'is_system' => true,  // Automated from ResellerSales
                'is_visible' => false,
            ],
            [
                'name' => 'Other Income',
                'type' => FinancialCategory::TYPE_INCOME,
                'is_system' => false, // Manual entry
                'is_visible' => true,
            ],

            // ==================== EXPENSE CATEGORIES ====================
            
            // A. Cost of Goods
            [
                'name' => 'Inventory Purchases',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from PurchaseInvoices
                'is_visible' => false,
            ],
            [
                'name' => 'Purchase Returns',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from ReturnedOrders
                'is_visible' => false,
            ],

            // B. Personnel Expenses
            [
                'name' => 'Salaries',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from Payroll
                'is_visible' => false,
            ],
            [
                'name' => 'Allowances',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from EmployeeAllowances
                'is_visible' => false,
            ],
            [
                'name' => 'Incentives',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from MonthlyIncentives
                'is_visible' => false,
            ],
            [
                'name' => 'Employee Advances',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => true,  // Automated from AdvanceRequests
                'is_visible' => false,
            ],

            // C. Operating Expenses (Manual Entry)
            [
                'name' => 'Rent',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Utilities',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Maintenance',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Marketing',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Administrative',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Transportation',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Communications',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Hospitality',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],

            // D. Asset Expenses
            [
                'name' => 'Equipment',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Depreciation',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'is_system' => false,
                'is_visible' => true,
            ],
        ];

        foreach ($categories as $category) {
            FinancialCategory::create($category);
        }

        $this->command->info('✅ Financial categories seeded successfully!');
        $this->command->info('   - Income categories: 3');
        $this->command->info('   - Expense categories: 16');
    }
}
