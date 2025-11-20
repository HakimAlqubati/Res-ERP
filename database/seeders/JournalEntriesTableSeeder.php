<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JournalEntriesTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب معرفات الحسابات بناءً على الكود لضمان الدقة
        // سنقوم بإنشاء مصفوفة مفهرسة بالكود ليسهل استدعاؤها
        $codes = [
            '1101', '1103', '1104', '1201', '1203', '2101', '2102', 
            '3100', '4100', '4200', '4300', '5202', '5203'
        ];
        
        $accounts = Account::whereIn('account_code', $codes)
                           ->pluck('id', 'account_code')
                           ->toArray();

        // التأكد من وجود حسابات قبل البدء
        if (empty($accounts)) {
            $this->command->warn("No accounts found! Please run AccountsTableSeeder first.");
            return;
        }

        $currencyId = null;
        $branchId = 1; // افتراض وجود فرع رقم 1

        DB::transaction(function () use ($accounts, $currencyId, $branchId) {

            // ====================================================
            // السيناريو 1: قيد رأس المال (إيداع في البنك)
            // ====================================================
            $entry1 = JournalEntry::create([
                'entry_date'       => Carbon::now()->subDays(30),
                'reference_number' => 'OPEN-001',
                'reference_type'   => 'manual',
                'description'      => 'Initial Capital Injection - إيداع رأس المال',
                'branch_id'        => $branchId,
                'status'           => JournalEntry::STATUS_POSTED,
                'currency_id'      => $currencyId,
            ]);

            // المدين: البنك (1103)
            JournalEntryLine::create([
                'journal_entry_id' => $entry1->id,
                'account_id'       => $accounts['1103'], 
                'debit'            => 500000.00,
                'credit'           => 0.00,
                'branch_id'        => $branchId,
                'line_description' => 'Deposit to Bank',
            ]);

            // الدائن: رأس المال (3100)
            JournalEntryLine::create([
                'journal_entry_id' => $entry1->id,
                'account_id'       => $accounts['3100'], 
                'debit'            => 0.00,
                'credit'           => 500000.00,
                'branch_id'        => $branchId,
                'line_description' => 'Owner Capital',
            ]);


            // ====================================================
            // السيناريو 2: فاتورة مشتريات آجلة (مخزون من مورد)
            // ====================================================
            $entry2 = JournalEntry::create([
                'entry_date'       => Carbon::now()->subDays(25),
                'reference_number' => 'PUR-2023-001',
                'reference_type'   => 'purchase_invoice',
                'description'      => 'Purchase Inventory from Supplier A - شراء مخزون',
                'branch_id'        => $branchId,
                'status'           => JournalEntry::STATUS_POSTED,
                'currency_id'      => $currencyId,
            ]);

            // المدين: مخزون أغذية (1201)
            JournalEntryLine::create([
                'journal_entry_id' => $entry2->id,
                'account_id'       => $accounts['1201'], 
                'debit'            => 10000.00,
                'credit'           => 0.00,
                'branch_id'        => $branchId,
                'line_description' => 'Meat and Vegetables',
            ]);

            // المدين: مخزون تغليف (1203)
            JournalEntryLine::create([
                'journal_entry_id' => $entry2->id,
                'account_id'       => $accounts['1203'], 
                'debit'            => 2000.00,
                'credit'           => 0.00,
                'branch_id'        => $branchId,
                'line_description' => 'Boxes and Bags',
            ]);

            // الدائن: الموردين (2101)
            JournalEntryLine::create([
                'journal_entry_id' => $entry2->id,
                'account_id'       => $accounts['2101'], 
                'debit'            => 0.00,
                'credit'           => 12000.00,
                'branch_id'        => $branchId,
                'line_description' => 'Inv #9988 Supplier A',
            ]);


            // ====================================================
            // السيناريو 3: مبيعات يومية (POS Sales) مع الضريبة
            // ====================================================
            // المبيعات: 5000 (4000 طعام + 1000 مشروبات) + 15% ضريبة (750) = الإجمالي 5750
            $entry3 = JournalEntry::create([
                'entry_date'       => Carbon::now()->subDays(1),
                'reference_number' => 'POS-CLOSING-055',
                'reference_type'   => 'daily_closing',
                'description'      => 'Daily Sales Closing - إقفال مبيعات يومي',
                'branch_id'        => $branchId,
                'status'           => JournalEntry::STATUS_POSTED,
                'currency_id'      => $currencyId,
            ]);

            // المدين: الصندوق الرئيسي (1101) - استلمنا الكاش
            JournalEntryLine::create([
                'journal_entry_id' => $entry3->id,
                'account_id'       => $accounts['1101'], 
                'debit'            => 5750.00,
                'credit'           => 0.00,
                'branch_id'        => $branchId,
                'line_description' => 'Cash Collected',
            ]);

            // الدائن: مبيعات طعام (4100)
            JournalEntryLine::create([
                'journal_entry_id' => $entry3->id,
                'account_id'       => $accounts['4100'], 
                'debit'            => 0.00,
                'credit'           => 4000.00,
                'branch_id'        => $branchId,
                'line_description' => 'Food Sales Revenue',
            ]);

            // الدائن: مبيعات مشروبات (4200)
            JournalEntryLine::create([
                'journal_entry_id' => $entry3->id,
                'account_id'       => $accounts['4200'], 
                'debit'            => 0.00,
                'credit'           => 1000.00,
                'branch_id'        => $branchId,
                'line_description' => 'Beverage Sales Revenue',
            ]);

            // الدائن: ضريبة القيمة المضافة (2102)
            JournalEntryLine::create([
                'journal_entry_id' => $entry3->id,
                'account_id'       => $accounts['2102'], 
                'debit'            => 0.00,
                'credit'           => 750.00,
                'branch_id'        => $branchId,
                'line_description' => 'VAT 15%',
            ]);


            // ====================================================
            // السيناريو 4: دفع مصروف (إيجار) بشيك
            // ====================================================
            $entry4 = JournalEntry::create([
                'entry_date'       => Carbon::now(),
                'reference_number' => 'EXP-Rent-01',
                'reference_type'   => 'payment',
                'description'      => 'Monthly Rent Payment - دفع الإيجار الشهري',
                'branch_id'        => $branchId,
                'status'           => JournalEntry::STATUS_POSTED,
                'currency_id'      => $currencyId,
            ]);

            // المدين: مصروف الإيجار (5202)
            JournalEntryLine::create([
                'journal_entry_id' => $entry4->id,
                'account_id'       => $accounts['5202'], 
                'debit'            => 15000.00,
                'credit'           => 0.00,
                'branch_id'        => $branchId,
                'line_description' => 'Rent for current month',
            ]);

            // الدائن: البنك (1103)
            JournalEntryLine::create([
                'journal_entry_id' => $entry4->id,
                'account_id'       => $accounts['1103'], 
                'debit'            => 0.00,
                'credit'           => 15000.00,
                'branch_id'        => $branchId,
                'line_description' => 'Bank Transfer',
            ]);
        });
    }
}