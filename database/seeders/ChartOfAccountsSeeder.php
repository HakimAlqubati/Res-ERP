<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('local')) {
            Account::query()->delete();
        }

        // 🟢 الأصول
        $assets = Account::create([
            'name' => 'الأصول',
            'code' => 1,
            'type' => Account::TYPE_ASSET,
            'is_parent' => true,
        ]);

        $inventory = Account::create([
            'name' => 'المخزون',
            'code' => 11,
            'type' => Account::TYPE_ASSET,
            'parent_id' => $assets->id,
            'is_parent' => true,
        ]);

        // 🟢 حسابات المخازن
        $storeIndex = 1;
        foreach (Store::all() as $store) {
            $storeCode = (int)("11" . str_pad((string)$storeIndex, 2, '0', STR_PAD_LEFT)); // مثل: 1101
            $account = Account::create([
                'name' => 'مخزون - ' . $store->name,
                'code' => $storeCode,
                'type' => Account::TYPE_ASSET,
                'parent_id' => $inventory->id,
                'is_parent' => false,
            ]);

            $store->update(['inventory_account_id' => $account->id]);
            $storeIndex++;
        }

        // 🔴 الخصوم
        $liabilities = Account::create([
            'name' => 'الخصوم',
            'code' => 2,
            'type' => Account::TYPE_LIABILITY,
            'is_parent' => true,
        ]);

        // 🔴 حساب تحليلي موحّد للموردين
        $suppliersAccount = Account::create([
            'name' => 'الموردين (تحليلي)',
            'code' => 21,
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $liabilities->id,
            'is_parent' => false,
        ]);

        foreach (Supplier::all() as $supplier) {
            $supplier->update(['account_id' => $suppliersAccount->id]);
        }

        // 🟡 المصاريف
        $expenses = Account::create([
            'name' => 'المصاريف',
            'code' => 5,
            'type' => Account::TYPE_EXPENSE,
            'is_parent' => true,
        ]);

        $cogs = Account::create([
            'name' => 'تكلفة البضاعة المباعة',
            'code' => 51,
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $expenses->id,
            'is_parent' => true,
        ]);

        $branchIndex = 1;
        foreach (Branch::all() as $branch) {
            $branchCode = (int)("51" . str_pad((string)$branchIndex, 2, '0', STR_PAD_LEFT)); // مثل: 5101
            $account = Account::create([
                'name' => 'تكلفة تشغيل - ' . $branch->name,
                'code' => $branchCode,
                'type' => Account::TYPE_EXPENSE,
                'parent_id' => $cogs->id,
                'is_parent' => false,
            ]);

            $branch->update(['operational_cost_account_id' => $account->id]);
            $branchIndex++;
        }
    }
}
