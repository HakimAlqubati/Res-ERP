<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\Carbon;

class FinancialTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have necessary data
        $categories = FinancialCategory::all();
        $branches = Branch::all();
        $paymentMethods = PaymentMethod::all();
        $user = User::first();

        if ($categories->isEmpty()) {
            $this->command->info('No financial categories found. Please run the categories seeder first.');
            return;
        }

        // Create 50 random transactions
        for ($i = 0; $i < 50; $i++) {
            $category = $categories->random();
            $branch = $branches->isNotEmpty() ? $branches->random() : null;
            $paymentMethod = $paymentMethods->isNotEmpty() ? $paymentMethods->random() : null;

            $date = Carbon::now()->subDays(rand(0, 60));
            $status = FinancialTransaction::STATUSES[array_rand(FinancialTransaction::STATUSES)];

            // If status is paid, we need a payment method (optional but logical)
            // If status is not paid, due date might be relevant

            $dueDate = $date->copy()->addDays(rand(15, 30));

            FinancialTransaction::create([
                'branch_id' => $branch?->id,
                'category_id' => $category->id,
                'amount' => rand(100, 5000) + (rand(0, 99) / 100),
                'type' => $category->type, // Use the category's type
                'transaction_date' => $date,
                'due_date' => $status !== FinancialTransaction::STATUS_PAID ? $dueDate : null,
                'status' => $status,
                'description' => 'Auto-generated transaction #' . ($i + 1),
                'payment_method_id' => $status === FinancialTransaction::STATUS_PAID ? $paymentMethod?->id : null,
                'created_by' => $user?->id,
            ]);
        }
    }
}
