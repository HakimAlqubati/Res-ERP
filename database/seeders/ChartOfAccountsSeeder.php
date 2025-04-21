<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // حذف الحسابات السابقة لتجنب التكرار في التطوير
        
        Account::query()->delete();

        // الأصول (Assets)
        $assets = Account::create(['name' => 'الأصول', 'code' => '1', 'type' => 'asset']);

        $inventory = Account::create([
            'name' => 'المخزون', 'code' => '1.1', 'type' => 'asset', 'parent_id' => $assets->id
        ]);

        $inventoryMain = Account::create([
            'name' => 'مخزون - المركز الرئيسي', 'code' => '1.1.1', 'type' => 'asset', 'parent_id' => $inventory->id
        ]);

        $inventoryBranches = [
            'الرياض' => '1.1.2',
            'جدة' => '1.1.3',
            'الدمام' => '1.1.4',
        ];

        foreach ($inventoryBranches as $name => $code) {
            Account::create([
                'name' => 'مخزون - فرع ' . $name,
                'code' => $code,
                'type' => 'asset',
                'parent_id' => $inventory->id,
            ]);
        }

        // الخصوم (Liabilities)
        $liabilities = Account::create(['name' => 'الخصوم', 'code' => '2', 'type' => 'liability']);

        Account::create([
            'name' => 'الموردين', 'code' => '2.1', 'type' => 'liability', 'parent_id' => $liabilities->id
        ]);

        // المصاريف (Expenses)
        $expenses = Account::create(['name' => 'المصاريف', 'code' => '5', 'type' => 'expense']);

        $cogs = Account::create([
            'name' => 'تكلفة البضاعة المباعة', 'code' => '5.1', 'type' => 'expense', 'parent_id' => $expenses->id
        ]);

        $operationalBranches = [
            'الرياض' => '5.1.1',
            'جدة' => '5.1.2',
            'الدمام' => '5.1.3',
        ];

        foreach ($operationalBranches as $name => $code) {
            Account::create([
                'name' => 'تكلفة تشغيل - فرع ' . $name,
                'code' => $code,
                'type' => 'expense',
                'parent_id' => $cogs->id,
            ]);
        }
    }
}
