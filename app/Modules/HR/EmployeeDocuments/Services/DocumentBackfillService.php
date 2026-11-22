<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Services;

use App\Models\AppLog;
use App\Models\EmployeeFile;
use App\Models\EmployeeFileType;
use App\Modules\HR\EmployeeDocuments\Support\DynamicFieldDateExtractor;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use Illuminate\Support\Facades\DB;

/**
 * Safely backfills issue_date and expiry_date on hr_employee_files from legacy
 * dynamic_field_values JSON without mutating or deleting dynamic_field_values.
 */
final class DocumentBackfillService
{
    public function __construct(
        private readonly DynamicFieldDateExtractor $extractor,
        private readonly EmployeeFileTypeCatalog $catalog,
    ) {}

    /**
     * @return array{
     *     scanned: int,
     *     updated: int,
     *     skipped: int,
     *     file_types_enabled: int,
     *     unmapped_keys: array<string, int>
     * }
     */
    public function run(
        bool $dryRun = false,
        bool $enableHasExpiry = false,
        int $chunkSize = 500,
    ): array {
        $chunkSize = max(50, min($chunkSize, 2000));

        // Load field definitions once to avoid N+1 queries.
        $this->catalog->flushMemory();
        $catalog = $this->catalog->all();

        $scanned = 0;
        $updated = 0;
        $skipped = 0;
        /** @var array<int, true> $expiryFileTypeIds */
        $expiryFileTypeIds = [];
        /** @var array<string, int> $unmappedKeys */
        $unmappedKeys = [];

        DB::table('hr_employee_files')
            ->select(['id', 'file_type_id', 'issue_date', 'expiry_date', 'dynamic_field_values'])
            ->whereNotNull('dynamic_field_values')
            ->where(function ($query): void {
                $query->whereNull('issue_date')
                    ->orWhereNull('expiry_date');
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
                $catalog,
                $dryRun,
                &$scanned,
                &$updated,
                &$skipped,
                &$expiryFileTypeIds,
                &$unmappedKeys
            ): void {
                foreach ($rows as $row) {
                    $scanned++;

                    $typeMeta = $catalog->get((int) $row->file_type_id);
                    $definitions = $typeMeta['dynamic_fields'] ?? [];

                    $extracted = $this->extractor->extract($row->dynamic_field_values, $definitions);

                    foreach ($extracted['unmapped_date_keys'] as $key) {
                        $unmappedKeys[$key] = ($unmappedKeys[$key] ?? 0) + 1;
                    }

                    $changes = [];

                    if ($row->issue_date === null && $extracted['issue_date'] !== null) {
                        $changes['issue_date'] = $extracted['issue_date'];
                    }

                    if ($row->expiry_date === null && $extracted['expiry_date'] !== null) {
                        $changes['expiry_date'] = $extracted['expiry_date'];
                        $expiryFileTypeIds[(int) $row->file_type_id] = true;
                    }

                    if ($changes === []) {
                        $skipped++;
                        continue;
                    }

                    if (! $dryRun) {
                        // Direct update avoids flooding OwenIt audits table during legacy migration
                        // and guarantees dynamic_field_values is never touched.
                        DB::table('hr_employee_files')
                            ->where('id', $row->id)
                            ->update($changes);
                    }

                    $updated++;
                }
            });

        $fileTypesEnabled = 0;

        if ($enableHasExpiry && $expiryFileTypeIds !== []) {
            $idsToEnable = array_keys($expiryFileTypeIds);

            if ($dryRun) {
                $fileTypesEnabled = EmployeeFileType::query()
                    ->whereIn('id', $idsToEnable)
                    ->where('has_expiry', false)
                    ->count();
            } else {
                $fileTypesEnabled = EmployeeFileType::query()
                    ->whereIn('id', $idsToEnable)
                    ->where('has_expiry', false)
                    ->update(['has_expiry' => true]);

                if ($fileTypesEnabled > 0) {
                    $this->catalog->flush();
                }
            }
        }

        $stats = [
            'scanned' => $scanned,
            'updated' => $updated,
            'skipped' => $skipped,
            'file_types_enabled' => $fileTypesEnabled,
            'unmapped_keys' => $unmappedKeys,
        ];

        if (! $dryRun && ($updated > 0 || $fileTypesEnabled > 0)) {
            AppLog::write(
                message: "Employee documents date backfill completed (updated={$updated}, file_types_enabled={$fileTypesEnabled}).",
                level: AppLog::LEVEL_INFO,
                context: 'HR_DOCUMENT_BACKFILL',
                extra: $stats
            );
        }

        return $stats;
    }
}
