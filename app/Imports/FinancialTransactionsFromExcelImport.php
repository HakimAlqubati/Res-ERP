<?php

namespace App\Imports;

use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Enums\FinancialCategoryCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class FinancialTransactionsFromExcelImport implements ToCollection
{
    protected int $successfulImports = 0;
    protected int $skippedRows = 0;

    public function __construct(
        protected int $branchId,
        protected ?int $paymentMethodId = null,
        protected ?int $userId = null,
    ) {
        $this->userId = $this->userId ?: Auth::id();
    }

    /**
     * Import financial transactions from Excel
     * Expected structure:
     * Column A (0) = Biz Date (التاريخ)
     * Column E (4) = Gross Total (الإجمالي)
     * Column F (5) = Svc Charge (رسوم الخدمة)
     * 
     * Amount = Gross Total + Svc Charge
     */
    public function collection(Collection $rows): void
    {
        // Get Sales category
        $salesCategory = FinancialCategory::findByCode(FinancialCategoryCode::SALES);

        if (!$salesCategory) {
            throw new \Exception('Sales financial category not found. Please ensure the financial categories are seeded properly.');
        }

        foreach ($rows as $index => $row) {
            // Skip first 1 rows (header rows)
            if ($index < 1) {
                continue;
            }

            // Get values from columns
            // $dateValue = trim((string) ($row[0] ?? '')); // Column A - Biz Date
            $dateValue =   $row[0] ?? ''; // Column A - Biz Date
            // $dateValue =   $index . '/12/2025'; // Column A - Biz Date
            $grossTotal = $row[4] ?? 0; // Column E - Gross Total
            $svcCharge = $row[5] ?? 0;  // Column F - Svc Charge

            // Skip empty rows
            if ($dateValue === '' || ($grossTotal == 0 && $svcCharge == 0)) {
                $this->skippedRows++;
                continue;
            }

            // Parse date
            try {
                $transactionDate = $this->parseDate($dateValue);
                // dd($rows, $amount, $transactionDate);
            } catch (\Exception $e) {
                // Skip rows with invalid dates
                $this->skippedRows++;
                continue;
            }

            // Calculate total amount
            $amount = (float) $grossTotal + (float) $svcCharge;

            // Skip if amount is zero or negative
            if ($amount <= 0) {
                $this->skippedRows++;
                continue;
            }

            // Create financial transaction
            FinancialTransaction::create([
                'branch_id' => $this->branchId,
                'category_id' => $salesCategory->id,
                'amount' => $amount,
                'type' => FinancialTransaction::TYPE_INCOME,
                'transaction_date' => $transactionDate,
                'status' => FinancialTransaction::STATUS_PAID,
                'description' => "Sales transaction imported from Excel for date: " . $transactionDate->format('Y-m-d'),
                'payment_method_id' => $this->paymentMethodId,
                'created_by' => $this->userId,
                'month' => $transactionDate->month,
                'year' => $transactionDate->year,
            ]);

            $this->successfulImports++;
        }
    }

    /**
     * Parse date from various formats
     * Supports: 
     * - Excel serial dates (numeric values like 45962)
     * - Text formats: dd-mm-yyyy, yyyy-mm-dd, d/m/y, etc.
     */
    public function parseDate($dateValue): Carbon
    {
        // Handle Excel serial date numbers (e.g., 45962)
        // Excel stores dates as numbers representing days since 1900-01-01
        if (is_numeric($dateValue)) {
            try {
                // Convert Excel serial date to DateTime object
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                return Carbon::instance($dateTime);
            } catch (\Exception $e) {
                // If conversion fails, continue to text format parsing
            }
        }

        // Convert to string for text-based parsing
        $dateValue = trim((string) $dateValue);

        // Try different text date formats
        $formats = [
            'd-m-Y',      // 01-11-2025
            'Y-m-d',      // 2025-11-01
            'd/m/Y',      // 01/11/2025
            'm/d/Y',      // 11/01/2025
            'Y/m/d',      // 2025/11/01
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateValue);
                if ($date) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // If all formats fail, throw exception
        throw new \Exception("Unable to parse date: {$dateValue}");
    }

    /**
     * Get count of successfully imported transactions
     */
    public function getSuccessfulImportsCount(): int
    {
        return $this->successfulImports;
    }

    /**
     * Get count of skipped rows
     */
    public function getSkippedRowsCount(): int
    {
        return $this->skippedRows;
    }
}
