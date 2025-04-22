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
        // حذف الحسابات السابقة فقط في بيئة التطوير
        if (app()->environment('local')) {
            Account::query()->delete();
        }

        // 🟢 الأصول
        $assets = Account::create([
            'name' => 'الأصول',
            'code' => '1',
            'type' => Account::TYPE_ASSET,
        ]);

        $inventory = Account::create([
            'name' => 'المخزون',
            'code' => '1.1',
            'type' => Account::TYPE_ASSET,
            'parent_id' => $assets->id,
        ]);

        // 🟢 إنشاء حساب لكل مخزن
        $storeIndex = 1;
        foreach (Store::all() as $store) {
            $storeCode = '1.1.' . str_pad((string)$storeIndex, 2, '0', STR_PAD_LEFT);
            $account = Account::create([
                'name' => 'مخزون - ' . $store->name,
                'code' => $storeCode,
                'type' => Account::TYPE_ASSET,
                'parent_id' => $inventory->id,
            ]);

            $store->update(['inventory_account_id' => $account->id]);
            $storeIndex++;
        }

        // 🔴 الخصوم
        $liabilities = Account::create([
            'name' => 'الخصوم',
            'code' => '2',
            'type' => Account::TYPE_LIABILITY,
        ]);

        $suppliersParent = Account::create([
            'name' => 'الموردين',
            'code' => '2.1',
            'type' => Account::TYPE_LIABILITY,
            'parent_id' => $liabilities->id,
        ]);

        // 🔴 إنشاء حساب لكل مورد
        $supplierIndex = 1;
        foreach (Supplier::all() as $supplier) {
            $supplierCode = '2.1.' . str_pad((string)$supplierIndex, 2, '0', STR_PAD_LEFT);
            $account = Account::create([
                'name' => 'مورد - ' . $supplier->name,
                'code' => $supplierCode,
                'type' => Account::TYPE_LIABILITY,
                'parent_id' => $suppliersParent->id,
            ]);

            $supplier->update(['account_id' => $account->id]);
            $supplierIndex++;
        }

        // 🟡 المصاريف
        $expenses = Account::create([
            'name' => 'المصاريف',
            'code' => '5',
            'type' => Account::TYPE_EXPENSE,
        ]);

        $cogs = Account::create([
            'name' => 'تكلفة البضاعة المباعة',
            'code' => '5.1',
            'type' => Account::TYPE_EXPENSE,
            'parent_id' => $expenses->id,
        ]);

        // 🟡 إنشاء حساب تكلفة تشغيل لكل فرع
        $branchIndex = 1;
        foreach (Branch::all() as $branch) {
            $branchCode = '5.1.' . str_pad((string)$branchIndex, 2, '0', STR_PAD_LEFT);
            $account = Account::create([
                'name' => 'تكلفة تشغيل - ' . $branch->name,
                'code' => $branchCode,
                'type' => Account::TYPE_EXPENSE,
                'parent_id' => $cogs->id,
            ]);

            $branch->update(['operational_cost_account_id' => $account->id]);
            $branchIndex++;
        }
    }
}
