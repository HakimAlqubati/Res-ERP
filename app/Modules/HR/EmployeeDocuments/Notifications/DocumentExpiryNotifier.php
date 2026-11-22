<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Notifications;

use App\Enums\Warnings\WarningLevel;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource;
use App\Models\AppLog;
use App\Models\Branch;
use App\Models\User;
use App\Modules\HR\EmployeeDocuments\Builders\EmployeeDocumentQueryBuilder;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentSummaryDTO;
use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Services\Warnings\WarningPayload;
use App\Services\Warnings\WarningSender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Dispatches deduplicated daily summary digests of expired and expiring-soon employee documents
 * to HR/Admins (organization-wide) and Branch Managers (branch-scoped).
 */
final class DocumentExpiryNotifier
{
    public function __construct(
        private readonly DocumentReportServiceInterface $reportService,
        private readonly DocumentExpiryRecipientResolver $recipients,
        private readonly DocumentExpiryDeduplicator $deduplicator,
        private readonly WarningSender $sender,
    ) {}

    /**
     * @return array{sent: int, skipped: int, failed: int, expired: int, expiring_soon: int}
     */
    public function notifyForCurrentTenant(
        ?CarbonImmutable $asOf = null,
        string|int|null $onlyUser = null,
        bool $dryRun = false,
        bool $force = false,
    ): array {
        $referenceDate = $asOf ?? CarbonImmutable::today();
        $dateString = $referenceDate->toDateString();

        $globalSummary = $this->reportService->getSummary(new DocumentReportFilterDTO(
            onlyActiveEmployees: true,
            onlyActiveFiles: true,
            applyAccessScope: false,
            asOf: $referenceDate,
        ));

        if (! $globalSummary->hasAttentionRequired()) {
            return [
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
                'expired' => 0,
                'expiring_soon' => 0,
            ];
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        // 1. Organization-wide digest for HR / SuperAdmin / SystemManager
        $globalUsers = $this->recipients->resolveGlobalRecipients();
        $globalUserIds = $globalUsers->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $filteredGlobalUsers = $this->recipients->filterByOptionUser($globalUsers, $onlyUser);

        if ($filteredGlobalUsers->isNotEmpty()) {
            $payload = $this->buildPayload(
                summary: $globalSummary,
                targetScope: 'global',
                dateString: $dateString,
                branchName: null,
                branchId: null,
            );

            [$s, $sk, $f] = $this->dispatchToUsers($filteredGlobalUsers, $payload, $dateString, $dryRun, $force);
            $sent += $s;
            $skipped += $sk;
            $failed += $f;
        }

        // 2. Branch-scoped digests for Branch Managers (only branches with affected documents)
        $affectedBranchIds = $this->resolveAffectedBranchIds($referenceDate);

        if ($affectedBranchIds !== []) {
            $branchNames = Branch::withoutGlobalScopes()
                ->whereIn('id', $affectedBranchIds)
                ->pluck('name', 'id');

            foreach ($affectedBranchIds as $branchId) {
                $branchSummary = $this->reportService->getSummary(new DocumentReportFilterDTO(
                    branchIds: [$branchId],
                    onlyActiveEmployees: true,
                    onlyActiveFiles: true,
                    applyAccessScope: false,
                    asOf: $referenceDate,
                ));

                if (! $branchSummary->hasAttentionRequired()) {
                    continue;
                }

                $managers = $this->recipients->resolveBranchManagers($branchId, $globalUserIds);
                $managers = $this->recipients->filterByOptionUser($managers, $onlyUser);

                if ($managers->isEmpty()) {
                    continue;
                }

                $payload = $this->buildPayload(
                    summary: $branchSummary,
                    targetScope: "branch:{$branchId}",
                    dateString: $dateString,
                    branchName: (string) ($branchNames->get($branchId) ?? "Branch #{$branchId}"),
                    branchId: $branchId,
                );

                [$s, $sk, $f] = $this->dispatchToUsers($managers, $payload, $dateString, $dryRun, $force);
                $sent += $s;
                $skipped += $sk;
                $failed += $f;
            }
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'expired' => $globalSummary->expired,
            'expiring_soon' => $globalSummary->expiringSoon,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveAffectedBranchIds(CarbonImmutable $referenceDate): array
    {
        return EmployeeDocumentQueryBuilder::make()
            ->applyFilter(new DocumentReportFilterDTO(
                statuses: [DocumentExpiryStatus::Expired, DocumentExpiryStatus::ExpiringSoon],
                onlyActiveEmployees: true,
                onlyActiveFiles: true,
                applyAccessScope: false,
                asOf: $referenceDate,
            ), includeStatus: true)
            ->build()
            ->toBase()
            ->whereNotNull('emp.branch_id')
            ->distinct()
            ->pluck('emp.branch_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function buildPayload(
        DocumentSummaryDTO $summary,
        string $targetScope,
        string $dateString,
        ?string $branchName,
        ?int $branchId,
    ): WarningPayload {
        $level = $summary->expired > 0 ? WarningLevel::Critical : WarningLevel::Warning;

        $title = $branchName !== null
            ? __('lang.doc_expiry_digest_title_branch', ['branch' => $branchName])
            : __('lang.doc_expiry_digest_title');

        $detail = __('lang.doc_expiry_digest_body', [
            'expired' => $summary->expired,
            'expiring_soon' => $summary->expiringSoon,
        ]);

        $scopeKey = $this->deduplicator->buildScopeKey($targetScope, $dateString);

        return WarningPayload::make($title, $detail, $level)
            ->ctx(array_filter([
                'type' => 'employee_document_expiry',
                'date' => $dateString,
                'branch_id' => $branchId,
                'expired_count' => $summary->expired,
                'expiring_soon_count' => $summary->expiringSoon,
            ], static fn ($value): bool => $value !== null))
            ->scope($scopeKey)
            ->url($this->resolveReportUrl($summary))
            ->expires(now()->endOfDay());
    }

    private function resolveReportUrl(DocumentSummaryDTO $summary): ?string
    {
        try {
            $activeTab = $summary->expired > 0
                ? DocumentExpiryStatus::Expired->value
                : DocumentExpiryStatus::ExpiringSoon->value;

            return EmployeeDocumentReportResource::getUrl('index', [
                'activeTab' => $activeTab,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array{0: int, 1: int, 2: int}
     */
    private function dispatchToUsers(
        Collection $users,
        WarningPayload $payload,
        string $dateString,
        bool $dryRun,
        bool $force,
    ): array {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            if (! $force && $this->deduplicator->wasSentToday($payload, (int) $user->id, $dateString)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                $this->sender->send($user, $payload);
                $this->deduplicator->markSent($payload, (int) $user->id, $dateString);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                AppLog::write(
                    message: "Failed to send document expiry digest to user #{$user->id}: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_DOCUMENT_EXPIRY_NOTIFY',
                    extra: ['user_id' => $user->id, 'scope_key' => $payload->scopeKey]
                );
            }
        }

        return [$sent, $skipped, $failed];
    }
}
