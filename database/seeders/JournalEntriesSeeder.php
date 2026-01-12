<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\CashBox;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class JournalEntriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates 100 random journal entries for the year 2026
     */
    public function run(): void
    {
        // Get necessary data
        $accounts = Account::where('allow_manual_entries', true)->get();
        $currencies = Currency::all();
        $baseCurrency = Currency::where('is_base', true)->first();
        $bankAccounts = BankAccount::all();
        $cashBoxes = CashBox::all();

        if ($accounts->isEmpty() || !$baseCurrency) {
            $this->command->error('Please seed accounts and currencies first!');
            return;
        }

        $this->command->info('Creating 100 random journal entries for 2026...');

        $entryNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            $this->createRandomJournalEntry(
                $entryNumber++,
                $accounts,
                $currencies,
                $baseCurrency,
                $bankAccounts,
                $cashBoxes
            );

            if (($i + 1) % 10 == 0) {
                $this->command->info("Created " . ($i + 1) . " entries...");
            }
        }

        $this->command->info('✅ Successfully created 100 journal entries!');
    }

    /**
     * Create a single random journal entry
     */
    private function createRandomJournalEntry(
        int $entryNumber,
        $accounts,
        $currencies,
        $baseCurrency,
        $bankAccounts,
        $cashBoxes
    ): void {
        // Random date in 2026
        $randomDate = Carbon::create(2026, rand(1, 12), rand(1, 28));

        // Random currency
        $currency = $currencies->random();
        $exchangeRate = $currency->exchange_rate;

        // Random entry type
        $entryTypes = [
            'Cash Sale',
            'Bank Deposit',
            'Expense Payment',
            'Supplier Payment',
            'Cash Withdrawal',
            'Transfer',
            'Purchase',
            'Revenue',
            'Adjustment',
            'Opening Balance'
        ];

        $referenceType = $entryTypes[array_rand($entryTypes)];
        $referenceNumber = 'REF-' . str_pad($entryNumber, 6, '0', STR_PAD_LEFT);

        // Create journal entry
        $entry = JournalEntry::create([
            'entry_date' => $randomDate,
            'reference_number' => $referenceNumber,
            'reference_type' => $referenceType,
            'description' => $this->getRandomDescription($referenceType),
            'branch_id' => null,
            'status' => rand(0, 10) > 7 ? 'posted' : 'draft', // 30% posted, 70% draft
            'currency_id' => $currency->id,
            'exchange_rate' => $exchangeRate,
            'entry_number' => 'JE-' . str_pad($entryNumber, 6, '0', STR_PAD_LEFT),
        ]);

        // Create 2-4 lines per entry
        $numberOfLines = rand(2, 4);
        $this->createJournalEntryLines(
            $entry,
            $numberOfLines,
            $accounts,
            $exchangeRate,
            $bankAccounts,
            $cashBoxes
        );
    }

    /**
     * Create journal entry lines (balanced)
     */
    private function createJournalEntryLines(
        JournalEntry $entry,
        int $numberOfLines,
        $accounts,
        float $exchangeRate,
        $bankAccounts,
        $cashBoxes
    ): void {
        $lines = [];
        $totalDebit = 0;
        $totalCredit = 0;

        // Random amount between 1,000 and 1,000,000 YER
        $baseAmount = rand(1000, 1000000);

        // Create debit lines
        $debitLines = ceil($numberOfLines / 2);
        for ($i = 0; $i < $debitLines; $i++) {
            $account = $accounts->random();
            $amount = $i == 0 ? $baseAmount : rand(1000, 50000);

            $foreignAmount = $amount / $exchangeRate;

            $line = [
                'journal_entry_id' => $entry->id,
                'account_id' => $account->id,
                'debit' => $amount,
                'credit' => 0,
                'debit_foreign' => $foreignAmount,
                'credit_foreign' => 0,
                'cost_center_id' => null,
                'branch_id' => null,
                'line_description' => $this->getRandomLineDescription($account),
            ];

            // Randomly assign bank or cash box
            if (rand(0, 10) > 7 && $bankAccounts->isNotEmpty()) {
                $line['bank_account_id'] = $bankAccounts->random()->id;
            } elseif (rand(0, 10) > 7 && $cashBoxes->isNotEmpty()) {
                $line['cash_box_id'] = $cashBoxes->random()->id;
            }

            $lines[] = $line;
            $totalDebit += $amount;
        }

        // Create credit lines to balance
        $creditLines = $numberOfLines - $debitLines;
        $creditPerLine = $totalDebit / max($creditLines, 1);

        for ($i = 0; $i < $creditLines; $i++) {
            $account = $accounts->random();
            $amount = $i == ($creditLines - 1)
                ? ($totalDebit - $totalCredit) // Last line balances exactly
                : round($creditPerLine, 2);

            $foreignAmount = $amount / $exchangeRate;

            $line = [
                'journal_entry_id' => $entry->id,
                'account_id' => $account->id,
                'debit' => 0,
                'credit' => $amount,
                'debit_foreign' => 0,
                'credit_foreign' => $foreignAmount,
                'cost_center_id' => null,
                'branch_id' => null,
                'line_description' => $this->getRandomLineDescription($account),
            ];

            // Randomly assign bank or cash box
            if (rand(0, 10) > 7 && $bankAccounts->isNotEmpty()) {
                $line['bank_account_id'] = $bankAccounts->random()->id;
            } elseif (rand(0, 10) > 7 && $cashBoxes->isNotEmpty()) {
                $line['cash_box_id'] = $cashBoxes->random()->id;
            }

            $lines[] = $line;
            $totalCredit += $amount;
        }

        // Insert all lines
        foreach ($lines as $line) {
            JournalEntryLine::create($line);
        }
    }

    /**
     * Get random description based on entry type
     */
    private function getRandomDescription(string $type): string
    {
        $descriptions = [
            'Cash Sale' => 'Daily cash sales',
            'Bank Deposit' => 'Cash deposit to bank',
            'Expense Payment' => 'Monthly expense payment',
            'Supplier Payment' => 'Payment to supplier',
            'Cash Withdrawal' => 'Cash withdrawal from bank',
            'Transfer' => 'Internal transfer',
            'Purchase' => 'Purchase of goods/services',
            'Revenue' => 'Revenue recognition',
            'Adjustment' => 'Accounting adjustment',
            'Opening Balance' => 'Opening balance entry',
        ];

        return $descriptions[$type] ?? 'General transaction';
    }

    /**
     * Get random line description
     */
    private function getRandomLineDescription(Account $account): string
    {
        $descriptions = [
            'Payment received',
            'Payment made',
            'Transfer in',
            'Transfer out',
            'Daily sales',
            'Purchase expense',
            'Salary payment',
            'Rent payment',
            'Utilities',
            'Maintenance',
            'Marketing expense',
            'Bank charges',
            'Interest income',
            'Adjustment entry',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
