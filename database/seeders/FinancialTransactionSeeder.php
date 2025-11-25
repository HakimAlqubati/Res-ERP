<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing transactions
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FinancialTransaction::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Get necessary data
        $branches = Branch::all();
        $paymentMethods = PaymentMethod::all();
        $user = User::first();

        // Get Categories by Name
        $catRent = FinancialCategory::where('name', 'Rent')->first();
        $catSalaries = FinancialCategory::where('name', 'Salaries')->first();
        $catElectricity = FinancialCategory::where('name', 'Electricity')->first();
        $catWater = FinancialCategory::where('name', 'Water')->first();
        $catMaintenance = FinancialCategory::where('name', 'Maintenance')->first();
        $catTransfers = FinancialCategory::where('name', 'Transfers')->first();
        $catSales = FinancialCategory::where('name', 'Branch Sales')->first();

        if (!$catRent || !$catSalaries || !$catSales) {
            $this->command->error('Essential categories missing. Please run the categories migration first.');
            return;
        }

        $defaultBranch = $branches->first();
        $cashPayment = $paymentMethods->first(); // Assuming first is Cash

        // 3. Loop through 2025
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2025, 12, 31);

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $month = $currentDate->month;
            $day = $currentDate->day;

            // --- Monthly Fixed Expenses (Rent) - 1st of month ---
            if ($day === 1) {
                $this->createTransaction($catRent, 5000, $currentDate, $defaultBranch, $user, $cashPayment);
            }

            // --- Monthly Salaries - 28th of month ---
            if ($day === 28) {
                $this->createTransaction($catSalaries, 15000, $currentDate, $defaultBranch, $user, $cashPayment);
            }

            // --- Monthly Variable Expenses (Electricity/Water) - Around 10th ---
            if ($day === 10) {
                if ($catElectricity) {
                    $amount = rand(300, 800) + (rand(0, 99) / 100);
                    $this->createTransaction($catElectricity, $amount, $currentDate, $defaultBranch, $user, $cashPayment);
                }
                if ($catWater) {
                    $amount = rand(50, 150) + (rand(0, 99) / 100);
                    $this->createTransaction($catWater, $amount, $currentDate, $defaultBranch, $user, $cashPayment);
                }
            }

            // --- Occasional Expenses (Maintenance) - Random days ---
            if ($catMaintenance && rand(1, 100) <= 5) { // 5% chance daily
                $amount = rand(100, 500);
                $this->createTransaction($catMaintenance, $amount, $currentDate, $defaultBranch, $user, $cashPayment);
            }

            // --- Occasional Transfers - Random days ---
            if ($catTransfers && rand(1, 100) <= 3) { // 3% chance daily
                $amount = rand(1000, 3000);
                $this->createTransaction($catTransfers, $amount, $currentDate, $defaultBranch, $user, $cashPayment);
            }

            // --- Daily Sales (Income) ---
            // Skip Fridays (Weekend) for realism if desired, or just random
            if ($currentDate->dayOfWeek !== Carbon::FRIDAY) {
                $dailySales = rand(1000, 5000) + (rand(0, 99) / 100);
                $this->createTransaction($catSales, $dailySales, $currentDate, $defaultBranch, $user, $cashPayment);
            }

            $currentDate->addDay();
        }
    }

    private function createTransaction($category, $amount, $date, $branch, $user, $paymentMethod)
    {
        FinancialTransaction::create([
            'branch_id' => $branch?->id,
            'category_id' => $category->id,
            'amount' => $amount,
            'type' => $category->type,
            'transaction_date' => $date->format('Y-m-d'),
            'due_date' => null, // Assuming paid immediately for simplicity
            'status' => FinancialTransaction::STATUS_PAID,
            'description' => $category->name . ' - ' . $date->format('F Y'),
            'payment_method_id' => $paymentMethod?->id,
            'created_by' => $user?->id,
            'month' => $date->month,
            'year' => $date->year,
        ]);
    }
}
