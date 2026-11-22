<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeFile;
use App\Models\EmployeeFileType;
use App\Models\EmployeeFileTypeField;
use App\Models\User;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Modules\HR\EmployeeDocuments\Exports\EmployeeDocumentsReportExport;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryNotifier;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryRecipientResolver;
use App\Modules\HR\EmployeeDocuments\Services\DocumentBackfillService;
use App\Modules\HR\EmployeeDocuments\Services\DocumentRenewalService;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use App\Services\Warnings\WarningPayload;
use App\Services\Warnings\WarningSender;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class EmployeeDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    private function createBranchAndManager(string $suffix): array
    {
        $manager = User::create([
            'name' => "Branch Manager {$suffix}",
            'email' => "bm_{$suffix}_" . Str::random(6) . '@test.local',
            'password' => bcrypt('secret123'),
            'active' => 1,
        ]);

        $branch = Branch::create([
            'name' => "Doc Branch {$suffix} " . Str::random(4),
            'manager_id' => $manager->id,
            'active' => true,
            'type' => Branch::TYPE_BRANCH,
        ]);

        $manager->updateQuietly(['branch_id' => $branch->id]);

        if (Role::where('id', DocumentExpiryRecipientResolver::ROLE_BRANCH_MANAGER)->exists()) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => DocumentExpiryRecipientResolver::ROLE_BRANCH_MANAGER,
                'model_type' => User::class,
                'model_id' => $manager->id,
            ]);
        }

        return [$branch, $manager];
    }

    private function createEmployee(Branch $branch, bool $active = true): Employee
    {
        return Employee::withoutEvents(function () use ($branch, $active): Employee {
            return Employee::create([
                'name' => 'Doc Employee ' . Str::random(6),
                'email' => 'doc_emp_' . Str::random(8) . '@test.local',
                'employee_no' => 'EMP-' . Str::random(6),
                'branch_id' => $branch->id,
                'active' => $active ? 1 : 0,
                'nationality' => 'MY',
                'join_date' => '2025-01-01',
                'salary' => 3000,
                'working_hours' => 8,
                'working_days' => 26,
            ]);
        });
    }

    public function test_report_service_filters_by_status_and_computes_summary_in_single_query(): void
    {
        [$branchA, $managerA] = $this->createBranchAndManager('A');
        [$branchB] = $this->createBranchAndManager('B');

        $empA = $this->createEmployee($branchA, active: true);
        $empB = $this->createEmployee($branchB, active: true);
        $inactiveEmp = $this->createEmployee($branchA, active: false);

        $passportType = EmployeeFileType::create([
            'name' => 'Passport ' . Str::random(4),
            'active' => true,
            'is_required' => true,
            'has_expiry' => true,
            'expiry_alert_days' => 90,
        ]);

        $cvType = EmployeeFileType::create([
            'name' => 'CV ' . Str::random(4),
            'active' => true,
            'is_required' => false,
            'has_expiry' => false,
            'expiry_alert_days' => null,
        ]);

        $asOf = CarbonImmutable::parse('2026-06-15');

        // Expired (Branch A)
        $expiredFile = EmployeeFile::create([
            'employee_id' => $empA->id,
            'file_type_id' => $passportType->id,
            'issue_date' => '2020-06-01',
            'expiry_date' => '2026-06-10',
            'active' => true,
        ]);

        // Expiring soon within 90 days (Branch A)
        $expiringSoonFile = EmployeeFile::create([
            'employee_id' => $empA->id,
            'file_type_id' => $passportType->id,
            'issue_date' => '2021-08-01',
            'expiry_date' => '2026-08-15',
            'active' => true,
        ]);

        // Valid beyond 90 days (Branch A)
        $validFile = EmployeeFile::create([
            'employee_id' => $empA->id,
            'file_type_id' => $passportType->id,
            'issue_date' => '2024-01-01',
            'expiry_date' => '2027-01-01',
            'active' => true,
        ]);

        // No expiry (Branch A)
        $noExpiryFile = EmployeeFile::create([
            'employee_id' => $empA->id,
            'file_type_id' => $cvType->id,
            'active' => true,
        ]);

        // Archived file (should be excluded by default)
        EmployeeFile::create([
            'employee_id' => $empA->id,
            'file_type_id' => $passportType->id,
            'expiry_date' => '2025-01-01',
            'active' => false,
        ]);

        // Inactive employee file (should be excluded by default)
        EmployeeFile::create([
            'employee_id' => $inactiveEmp->id,
            'file_type_id' => $passportType->id,
            'expiry_date' => '2025-01-01',
            'active' => true,
        ]);

        // Branch B expired file (should be hidden from Branch A manager)
        EmployeeFile::create([
            'employee_id' => $empB->id,
            'file_type_id' => $passportType->id,
            'expiry_date' => '2026-05-01',
            'active' => true,
        ]);

        $service = app(DocumentReportServiceInterface::class);

        // Verify summary aggregation itself executes in 1 SQL query.
        DB::flushQueryLog();
        DB::enableQueryLog();
        $globalSummary = $service->getSummary(new DocumentReportFilterDTO(
            fileTypeIds: [$passportType->id, $cvType->id],
            onlyActiveEmployees: true,
            onlyActiveFiles: true,
            applyAccessScope: false,
            asOf: $asOf,
        ));
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $queryCount, 'DocumentReportService::getSummary() must execute a single SQL aggregation query.');
        $this->assertSame(5, $globalSummary->total);
        $this->assertSame(2, $globalSummary->expired);
        $this->assertSame(1, $globalSummary->expiringSoon);
        $this->assertSame(1, $globalSummary->valid);
        $this->assertSame(1, $globalSummary->noExpiry);

        // Verify Branch A manager only sees Branch A records.
        $this->actingAs($managerA);

        $filter = new DocumentReportFilterDTO(
            fileTypeIds: [$passportType->id, $cvType->id],
            onlyActiveEmployees: true,
            onlyActiveFiles: true,
            applyAccessScope: true,
            asOf: $asOf,
        );

        $summary = $service->getSummary($filter);

        $this->assertSame(4, $summary->total);
        $this->assertSame(1, $summary->expired);
        $this->assertSame(1, $summary->expiringSoon);
        $this->assertSame(1, $summary->valid);
        $this->assertSame(1, $summary->noExpiry);

        // Verify urgency ordering: Expired -> ExpiringSoon -> Valid -> NoExpiry
        $orderedIds = $service->buildQuery($filter)->pluck('id')->all();
        $this->assertSame(
            [$expiredFile->id, $expiringSoonFile->id, $validFile->id, $noExpiryFile->id],
            $orderedIds
        );

        // Verify exact SQL status filter
        $expiredIds = $service->buildQuery(new DocumentReportFilterDTO(
            fileTypeIds: [$passportType->id, $cvType->id],
            status: DocumentExpiryStatus::Expired,
            applyAccessScope: true,
            asOf: $asOf,
        ))->pluck('id')->all();

        $this->assertSame([$expiredFile->id], $expiredIds);
    }

    public function test_backfill_service_extracts_dates_without_modifying_dynamic_field_values_or_existing_dates(): void
    {
        [$branch] = $this->createBranchAndManager('BF');
        $employee = $this->createEmployee($branch);

        $fileType = EmployeeFileType::create([
            'name' => 'Legacy Passport ' . Str::random(4),
            'active' => true,
            'is_required' => true,
            'has_expiry' => false,
            'expiry_alert_days' => 60,
        ]);

        EmployeeFileTypeField::create([
            'file_type_id' => $fileType->id,
            'field_name' => 'expiry_date',
            'field_type' => 'date',
        ]);

        // Legacy row with double-encoded JSON directly in DB
        $legacyJson = json_encode(json_encode([
            'passport_no' => 'X98765',
            'issue_date' => '2024-02-01',
            'expiry_date' => '2028-02-01',
        ]));

        $legacyId = DB::table('hr_employee_files')->insertGetId([
            'employee_id' => $employee->id,
            'file_type_id' => $fileType->id,
            'active' => 1,
            'dynamic_field_values' => $legacyJson,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Row with existing expiry_date must NEVER be overwritten
        $protectedFile = EmployeeFile::create([
            'employee_id' => $employee->id,
            'file_type_id' => $fileType->id,
            'issue_date' => '2023-01-01',
            'expiry_date' => '2030-12-31',
            'active' => true,
            'dynamic_field_values' => [
                'issue_date' => '2020-01-01',
                'expiry_date' => '2022-01-01',
            ],
        ]);

        $backfill = app(DocumentBackfillService::class);

        // Dry run must NOT modify database
        $dryStats = $backfill->run(dryRun: true, enableHasExpiry: true);
        $this->assertGreaterThanOrEqual(1, $dryStats['updated']);
        $this->assertNull(DB::table('hr_employee_files')->where('id', $legacyId)->value('expiry_date'));
        $this->assertFalse((bool) $fileType->fresh()->has_expiry);

        // Real run populates issue_date & expiry_date and preserves dynamic_field_values untouched
        $realStats = $backfill->run(dryRun: false, enableHasExpiry: true);
        $this->assertGreaterThanOrEqual(1, $realStats['updated']);

        $rawLegacyRow = DB::table('hr_employee_files')->where('id', $legacyId)->first();
        $this->assertSame('2024-02-01', $rawLegacyRow->issue_date);
        $this->assertSame('2028-02-01', $rawLegacyRow->expiry_date);
        $this->assertSame($legacyJson, $rawLegacyRow->dynamic_field_values);
        $this->assertTrue((bool) $fileType->fresh()->has_expiry);

        // Existing dates remain untouched
        $this->assertSame('2023-01-01', $protectedFile->fresh()->issue_date->toDateString());
        $this->assertSame('2030-12-31', $protectedFile->fresh()->expiry_date->toDateString());
    }

    public function test_renewal_service_archives_previous_document_and_validates_dates(): void
    {
        [$branch] = $this->createBranchAndManager('RN');
        $employee = $this->createEmployee($branch);

        $fileType = EmployeeFileType::create([
            'name' => 'Work Permit ' . Str::random(4),
            'active' => true,
            'has_expiry' => true,
            'expiry_alert_days' => 60,
        ]);

        $oldFile = EmployeeFile::create([
            'employee_id' => $employee->id,
            'file_type_id' => $fileType->id,
            'issue_date' => '2024-01-01',
            'expiry_date' => '2026-01-01',
            'active' => true,
            'dynamic_field_values' => ['permit_no' => 'WP-001'],
        ]);

        $renewalService = app(DocumentRenewalService::class);

        $newFile = $renewalService->renew($oldFile, [
            'issue_date' => '2026-01-02',
            'expiry_date' => '2028-01-01',
            'description' => 'Renewed for 2 years',
        ]);

        $this->assertFalse((bool) $oldFile->fresh()->active);
        $this->assertTrue((bool) $newFile->active);
        $this->assertSame('2028-01-01', $newFile->expiry_date->toDateString());
        $this->assertSame(['permit_no' => 'WP-001'], $newFile->dynamic_field_values);

        $this->expectException(ValidationException::class);
        $renewalService->renew($newFile, [
            'issue_date' => '2028-06-01',
            'expiry_date' => '2028-01-01',
        ]);
    }

    public function test_catalog_observer_invalidates_cache_on_file_type_and_field_changes(): void
    {
        $catalog = app(EmployeeFileTypeCatalog::class);
        $catalog->flush();

        $fileType = EmployeeFileType::create([
            'name' => 'Health Card ' . Str::random(4),
            'active' => true,
            'has_expiry' => false,
            'expiry_alert_days' => 30,
        ]);

        $this->assertFalse($catalog->hasExpiry($fileType->id));
        $this->assertSame([], $catalog->dynamicFields($fileType->id));

        // Update has_expiry -> observer must flush catalog automatically
        $fileType->update(['has_expiry' => true, 'expiry_alert_days' => 45]);
        $this->assertTrue($catalog->hasExpiry($fileType->id));
        $this->assertSame(45, $catalog->alertDays($fileType->id));

        // Add dynamic field -> observer on EmployeeFileTypeField must flush catalog automatically
        EmployeeFileTypeField::create([
            'file_type_id' => $fileType->id,
            'field_name' => 'clinic_name',
            'field_type' => 'text',
        ]);

        $fields = $catalog->dynamicFields($fileType->id);
        $this->assertCount(1, $fields);
        $this->assertSame('clinic_name', $fields[0]['field_name']);
    }

    public function test_expiry_notifier_dispatches_once_per_day_and_deduplicates_repeat_runs(): void
    {
        Cache::flush();

        [$branch, $branchManager] = $this->createBranchAndManager('NF');
        $employee = $this->createEmployee($branch);

        $fileType = EmployeeFileType::create([
            'name' => 'Visa ' . Str::random(4),
            'active' => true,
            'has_expiry' => true,
            'expiry_alert_days' => 30,
        ]);

        $asOf = CarbonImmutable::parse('2026-06-15');

        EmployeeFile::create([
            'employee_id' => $employee->id,
            'file_type_id' => $fileType->id,
            'expiry_date' => '2026-06-20',
            'active' => true,
        ]);

        $captured = [];
        $fakeSender = new class($captured) implements WarningSender
        {
            /** @param array<int, array{user_id: int, scope: ?string}> $captured */
            public function __construct(public array &$captured) {}

            public function send(Authenticatable|iterable $users, WarningPayload $payload): void
            {
                $list = is_iterable($users) ? $users : [$users];
                foreach ($list as $user) {
                    $this->captured[] = [
                        'user_id' => (int) $user->getAuthIdentifier(),
                        'scope' => $payload->scopeKey,
                    ];
                }
            }

            public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void
            {
                $this->send($users, $payload);
            }
        };

        $this->app->instance(WarningSender::class, $fakeSender);

        /** @var DocumentExpiryNotifier $notifier */
        $notifier = $this->app->make(DocumentExpiryNotifier::class);

        $firstRun = $notifier->notifyForCurrentTenant(
            asOf: $asOf,
            onlyUser: $branchManager->id,
        );

        $this->assertSame(1, $firstRun['sent']);
        $this->assertSame(0, $firstRun['skipped']);
        $this->assertCount(1, $captured);
        $this->assertSame((int) $branchManager->id, $captured[0]['user_id']);

        // Second run same day must be deduplicated (0 sent, 1 skipped)
        $secondRun = $notifier->notifyForCurrentTenant(
            asOf: $asOf,
            onlyUser: $branchManager->id,
        );

        $this->assertSame(0, $secondRun['sent']);
        $this->assertSame(1, $secondRun['skipped']);
        $this->assertCount(1, $captured);
    }

    public function test_excel_export_streams_joined_query_and_maps_rows_without_errors(): void
    {
        [$branch] = $this->createBranchAndManager('EX');
        $employee = $this->createEmployee($branch);

        $fileType = EmployeeFileType::create([
            'name' => 'Export Passport ' . Str::random(4),
            'active' => true,
            'has_expiry' => true,
            'expiry_alert_days' => 60,
        ]);

        EmployeeFile::create([
            'employee_id' => $employee->id,
            'file_type_id' => $fileType->id,
            'issue_date' => '2024-01-01',
            'expiry_date' => '2026-07-01',
            'active' => true,
            'dynamic_field_values' => ['passport_no' => 'EX-1001'],
        ]);

        $asOf = CarbonImmutable::parse('2026-06-15');
        $export = new EmployeeDocumentsReportExport(new DocumentReportFilterDTO(
            fileTypeIds: [$fileType->id],
            applyAccessScope: false,
            asOf: $asOf,
        ));

        $mapped = [];
        $export->query()->chunk($export->chunkSize(), function ($chunk) use ($export, &$mapped): void {
            foreach ($chunk as $row) {
                $mapped[] = $export->map($row);
            }
        });

        $this->assertCount(1, $mapped);
        $this->assertSame($employee->name, $mapped[0][1]);
        $this->assertSame($branch->name, $mapped[0][2]);
        $this->assertSame('2026-07-01', $mapped[0][6]);
        $this->assertSame(16, $mapped[0][7]);
        $this->assertStringContainsString('EX-1001', (string) $mapped[0][10]);
    }
}
