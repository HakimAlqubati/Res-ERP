<?php

namespace App\Filament\Dev\Widgets;

use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeApplicationV2;
use App\Services\HR\Applications\AdvanceRequest\AdvanceApprovalService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dev-panel widget that detects approved advance-request applications
 * with no installments and generates them on-demand.
 */
class AdvanceInstallmentFixerWidget extends Widget
{
    protected string $view = 'filament.dev.widgets.advance-installment-fixer-widget';

    protected static ?int $sort = 10;

    // protected static int|string|array $columnSpan = 'full';

    // ── Reactive state ────────────────────────────────────────────────────────

    /** @var list<array{id:int, employee:string, amount:float, starts_from:string}> */
    public array $pendingApplications = [];

    public int $fixedCount  = 0;
    public int $failedCount = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Re-query approved advance applications that have zero installments.
     */
    public function refresh(): void
    {
        $this->pendingApplications = EmployeeApplicationV2::query()
            ->with(['employee', 'advanceRequest'])
            ->where('status', EmployeeApplicationV2::STATUS_APPROVED)
            ->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST)
            ->whereDoesntHave('advanceRequest', function ($q) {
                // Only keep those whose advance request HAS no installments
                $q->whereHas('installments');
            })
            ->whereHas('advanceRequest') // must have an advance request record
            ->latest()
            ->get()
            ->map(fn($app) => [
                'id'          => $app->id,
                'employee'    => $app->employee?->name ?? "Employee #{$app->employee_id}",
                'amount'      => $app->advanceRequest?->advance_amount ?? 0,
                'starts_from' => $app->advanceRequest?->deduction_starts_from ?? '—',
                'months'      => $app->advanceRequest?->number_of_months_of_deduction ?? 0,
            ])
            ->toArray();

        $this->fixedCount  = 0;
        $this->failedCount = 0;
    }

    /**
     * Fix all pending applications at once.
     */
    public function fixAll(): void
    {
        if (empty($this->pendingApplications)) {
            Notification::make()->title('No pending applications found.')->warning()->send();
            return;
        }

        $service = app(AdvanceApprovalService::class);
        $fixed   = 0;
        $failed  = 0;

        foreach ($this->pendingApplications as $row) {
            $app = EmployeeApplicationV2::with('advanceRequest')->find($row['id']);

            if (! $app) {
                $failed++;
                continue;
            }

            try {
                DB::transaction(fn() => $service->process($app));
                $fixed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[AdvanceInstallmentFixerWidget] Failed to fix application.', [
                    'application_id' => $app->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->fixedCount  = $fixed;
        $this->failedCount = $failed;

        $this->refresh();

        if ($failed === 0) {
            Notification::make()
                ->title("Done — {$fixed} application(s) fixed successfully.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("{$fixed} fixed, {$failed} failed. Check logs for details.")
                ->warning()
                ->send();
        }
    }
}
