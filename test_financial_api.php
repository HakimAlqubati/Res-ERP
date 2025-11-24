<?php

use Illuminate\Support\Facades\Http;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;

// Create dummy data
$incomeCategory = FinancialCategory::firstOrCreate(
    ['name' => 'Sales Revenue'],
    ['type' => 'income', 'is_system' => true]
);

$expenseCategory = FinancialCategory::firstOrCreate(
    ['name' => 'Rent'],
    ['type' => 'expense', 'is_system' => true]
);

FinancialTransaction::create([
    'amount' => 10000,
    'type' => 'income',
    'category_id' => $incomeCategory->id,
    'transaction_date' => now()->format('Y-m-d'),
    'created_by' => 1, // Assuming user 1 exists
]);

FinancialTransaction::create([
    'amount' => 2000,
    'type' => 'expense',
    'category_id' => $expenseCategory->id,
    'transaction_date' => now()->format('Y-m-d'),
    'created_by' => 1,
]);

// Call the API internally
$service = new \App\Services\Financial\FinancialReportService();
$controller = new \App\Http\Controllers\Api\FinancialReportController($service);
$request = new \Illuminate\Http\Request();
$request->merge([
    'start_date' => now()->subDays(1)->format('Y-m-d'),
    'end_date' => now()->addDays(1)->format('Y-m-d'),
]);

$response = $controller->incomeStatement($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
