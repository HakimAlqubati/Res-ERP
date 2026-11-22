<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Services;

use App\Models\EmployeeFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Atomically renews an employee document:
 * archives the previous record (active = false) and creates a new active record.
 */
final class DocumentRenewalService
{
    /**
     * @param  array{
     *     issue_date?: ?string,
     *     expiry_date: string,
     *     attachment?: ?string,
     *     description?: ?string,
     *     dynamic_field_values?: ?array<string, mixed>
     * }  $data
     */
    public function renew(EmployeeFile $existingFile, array $data): EmployeeFile
    {
        $issueDate = $data['issue_date'] ?? null;
        $expiryDate = $data['expiry_date'] ?? null;

        if (empty($expiryDate)) {
            throw ValidationException::withMessages([
                'expiry_date' => __('lang.expiry_date_required'),
            ]);
        }

        if (! empty($issueDate) && $expiryDate < $issueDate) {
            throw ValidationException::withMessages([
                'expiry_date' => __('lang.expiry_date_must_be_after_issue_date'),
            ]);
        }

        return DB::transaction(function () use ($existingFile, $data, $issueDate, $expiryDate): EmployeeFile {
            $existingFile->update(['active' => false]);

            return EmployeeFile::create([
                'employee_id' => $existingFile->employee_id,
                'file_type_id' => $existingFile->file_type_id,
                'attachment' => $data['attachment'] ?? null,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'active' => true,
                'description' => $data['description'] ?? $existingFile->description,
                'dynamic_field_values' => $data['dynamic_field_values'] ?? $existingFile->dynamic_field_values,
            ]);
        });
    }
}
