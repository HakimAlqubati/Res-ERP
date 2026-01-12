<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // يفضل تفريغ الجدول قبل البدء لتجنب التكرار عند التجربة
        // DB::table('acc_accounts')->truncate(); 

        // سنفترض أن ID العملة الافتراضية هو 1
        $currencyId = null; 

        // ==========================================
        // 1. ASSETS - الأصول (1000)
        // ==========================================
        $assets = $this->createAccount('1000', 'Assets - الأصول', Account::TYPE_ASSET, null, $currencyId, true);

        // 1.1 Current Assets - أصول متداولة
        $currentAssets = $this->createAccount('1100', 'Current Assets - أصول متداولة', Account::TYPE_ASSET, $assets->id, $currencyId, true);
            $this->createAccount('1101', 'Main Cash - الصندوق الرئيسي', Account::TYPE_ASSET, $currentAssets->id, $currencyId);
            $this->createAccount('1102', 'Petty Cash - عهدة نقدية', Account::TYPE_ASSET, $currentAssets->id, $currencyId);
            $this->createAccount('1103', 'Bank - البنك', Account::TYPE_ASSET, $currentAssets->id, $currencyId);
            $this->createAccount('1104', 'Del. Apps Rx - ذمم توصيل', Account::TYPE_ASSET, $currentAssets->id, $currencyId); // Rx = Receivables
        
        // 1.2 Inventory - المخزون
        $inventory = $this->createAccount('1200', 'Inventory - المخزون', Account::TYPE_ASSET, $assets->id, $currencyId, true);
            $this->createAccount('1201', 'Food Inv - مخزون أغذية', Account::TYPE_ASSET, $inventory->id, $currencyId);
            $this->createAccount('1202', 'Bev Inv - مخزون مشروبات', Account::TYPE_ASSET, $inventory->id, $currencyId);
            $this->createAccount('1203', 'Packaging Inv - مخزون تغليف', Account::TYPE_ASSET, $inventory->id, $currencyId);

        // 1.3 Fixed Assets - أصول ثابتة
        $fixedAssets = $this->createAccount('1500', 'Fixed Assets - أصول ثابتة', Account::TYPE_ASSET, $assets->id, $currencyId, true);
            $this->createAccount('1501', 'Kitchen Equip - معدات مطبخ', Account::TYPE_ASSET, $fixedAssets->id, $currencyId);
            $this->createAccount('1502', 'Decor & Improv - ديكورات', Account::TYPE_ASSET, $fixedAssets->id, $currencyId);
            $this->createAccount('1503', 'POS & IT - أجهزة وأنظمة', Account::TYPE_ASSET, $fixedAssets->id, $currencyId);


        // ==========================================
        // 2. LIABILITIES - الخصوم (2000)
        // ==========================================
        $liabilities = $this->createAccount('2000', 'Liabilities - الخصوم', Account::TYPE_LIABILITY, null, $currencyId, true);

        // 2.1 Current Liabilities
        $currLiabilities = $this->createAccount('2100', 'Cur. Liab - خصوم متداولة', Account::TYPE_LIABILITY, $liabilities->id, $currencyId, true);
            $this->createAccount('2101', 'Suppliers - الموردين', Account::TYPE_LIABILITY, $currLiabilities->id, $currencyId);
            $this->createAccount('2102', 'VAT Payable - ضريبة مستحقة', Account::TYPE_LIABILITY, $currLiabilities->id, $currencyId);
            $this->createAccount('2103', 'Accrued Exp - مصاريف مستحقة', Account::TYPE_LIABILITY, $currLiabilities->id, $currencyId);


        // ==========================================
        // 3. EQUITY - الملكية (3000)
        // ==========================================
        $equity = $this->createAccount('3000', 'Equity - الملكية', Account::TYPE_EQUITY, null, $currencyId, true);
            $this->createAccount('3100', 'Capital - رأس المال', Account::TYPE_EQUITY, $equity->id, $currencyId);
            $this->createAccount('3200', 'Retained Earn - أرباح مبقاة', Account::TYPE_EQUITY, $equity->id, $currencyId);
            $this->createAccount('3300', 'Owner Draws - مسحوبات', Account::TYPE_EQUITY, $equity->id, $currencyId);


        // ==========================================
        // 4. REVENUE - الإيرادات (4000)
        // ==========================================
        $revenue = $this->createAccount('4000', 'Revenue - الإيرادات', Account::TYPE_REVENUE, null, $currencyId, true);
            $this->createAccount('4100', 'Food Sales - مبيعات طعام', Account::TYPE_REVENUE, $revenue->id, $currencyId);
            $this->createAccount('4200', 'Bev Sales - مبيعات مشروبات', Account::TYPE_REVENUE, $revenue->id, $currencyId);
            $this->createAccount('4300', 'Delivery Sales - مبيعات توصيل', Account::TYPE_REVENUE, $revenue->id, $currencyId);
            $this->createAccount('4400', 'Other Rev - إيرادات أخرى', Account::TYPE_REVENUE, $revenue->id, $currencyId);


        // ==========================================
        // 5. EXPENSES - المصروفات (5000)
        // ==========================================
        $expenses = $this->createAccount('5000', 'Expenses - المصروفات', Account::TYPE_EXPENSE, null, $currencyId, true);

        // 5.1 Direct Costs (COGS) - تكلفة البضاعة
        $cogs = $this->createAccount('5100', 'COGS - تكلفة البضاعة', Account::TYPE_EXPENSE, $expenses->id, $currencyId, true);
            $this->createAccount('5101', 'Food Cost - تكلفة الأغذية', Account::TYPE_EXPENSE, $cogs->id, $currencyId);
            $this->createAccount('5102', 'Bev Cost - تكلفة مشروبات', Account::TYPE_EXPENSE, $cogs->id, $currencyId);
            $this->createAccount('5103', 'Pack Cost - تكلفة تغليف', Account::TYPE_EXPENSE, $cogs->id, $currencyId);
            $this->createAccount('5104', 'Wastage - تالف وهالك', Account::TYPE_EXPENSE, $cogs->id, $currencyId);

        // 5.2 Operating Expenses - تشغيلية
        $opex = $this->createAccount('5200', 'OpEx - مصاريف تشغيل', Account::TYPE_EXPENSE, $expenses->id, $currencyId, true);
            $this->createAccount('5201', 'Salaries - رواتب وأجور', Account::TYPE_EXPENSE, $opex->id, $currencyId);
            $this->createAccount('5202', 'Rent - إيجارات', Account::TYPE_EXPENSE, $opex->id, $currencyId);
            $this->createAccount('5203', 'Utilities - كهرباء ومياه', Account::TYPE_EXPENSE, $opex->id, $currencyId);
            $this->createAccount('5204', 'Del. Comm - عمولات توصيل', Account::TYPE_EXPENSE, $opex->id, $currencyId); // مهمة جداً للمطاعم
            $this->createAccount('5205', 'Maint - صيانة', Account::TYPE_EXPENSE, $opex->id, $currencyId);
            $this->createAccount('5206', 'Marketing - تسويق', Account::TYPE_EXPENSE, $opex->id, $currencyId);
            $this->createAccount('5207', 'Gov Fees - رسوم حكومية', Account::TYPE_EXPENSE, $opex->id, $currencyId);
    }

    /**
     * Helper function to create accounts cleanly
     */
    private function createAccount($code, $name, $type, $parentId, $currencyId, $isParent = false)
    {
        return Account::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'is_parent'    => $isParent,
            'parent_id'    => $parentId,
            'is_active'    => true,
            // الحساب الأب لا يقبل قيود يدوية، الحساب الفرعي يقبل
            'allow_manual_entries' => !$isParent, 
            'currency_id'  => $currencyId,
        ]);
    }
}