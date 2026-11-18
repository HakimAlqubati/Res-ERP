<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Notifications;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Models\SalaryIncreaseRequest;
use App\Models\User;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Facades\Log;

final class SalaryIncreaseNotifier
{
    public function notifySubmitted(SalaryIncreaseRequest $request): void
    {
        try {
            $request->loadMissing(['employee:id,name,branch_id,manager_id', 'employee.manager.user']);

            $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', [1, 16, 19]))
                ->where('active', 1)
                ->get();

            $managerUser = $request->employee?->manager?->user;
            if ($managerUser) {
                $recipients = $recipients->push($managerUser)->unique('id');
            }

            $actorId = auth()->id();
            $recipients = $recipients->reject(fn (User $u) => $actorId && $u->id === $actorId);

            if ($recipients->isEmpty()) {
                return;
            }

            $employeeName = $request->employee?->name ?? "Employee #{$request->employee_id}";

            $payload = WarningPayload::make(
                'New Salary Increase Request',
                sprintf(
                    '%s requested a salary increase from %s to %s (+%s).',
                    $employeeName,
                    number_format((float) $request->old_salary, 2),
                    number_format((float) $request->new_salary, 2),
                    number_format((float) $request->increase_amount, 2)
                ),
                WarningLevel::Info
            )
                ->ctx([
                    'salary_increase_request_id' => $request->id,
                    'employee_id' => $request->employee_id,
                    'old_salary' => (float) $request->old_salary,
                    'new_salary' => (float) $request->new_salary,
                ])
                ->scope("salary-increase-created-{$request->id}")
                ->expires(now()->addDays(7));

            foreach ($recipients as $recipient) {
                Warnings::send($recipient, $payload);
            }
        } catch (\Throwable $e) {
            Log::warning('[SalaryIncreaseNotifier] Failed to notify on creation.', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyDecision(SalaryIncreaseRequest $request): void
    {
        try {
            $request->loadMissing(['employee.user', 'createdBy']);

            $recipients = collect([
                $request->employee?->user,
                $request->createdBy,
            ])->filter()->unique('id');

            if ($recipients->isEmpty()) {
                return;
            }

            $isApproved = $request->isApproved();
            $title = $isApproved ? 'Salary Increase Request Approved' : 'Salary Increase Request Rejected';
            $detail = $isApproved
                ? sprintf(
                    'Salary updated from %s to %s.',
                    number_format((float) $request->old_salary, 2),
                    number_format((float) $request->new_salary, 2)
                )
                : ('Reason: ' . ($request->rejection_reason ?? 'N/A'));

            $payload = WarningPayload::make(
                $title,
                $detail,
                $isApproved ? WarningLevel::Info : WarningLevel::Warning
            )
                ->ctx([
                    'salary_increase_request_id' => $request->id,
                    'employee_id' => $request->employee_id,
                    'status' => $request->status,
                ])
                ->scope("salary-increase-decision-{$request->id}-{$request->status}")
                ->expires(now()->addDays(7));

            foreach ($recipients as $recipient) {
                Warnings::send($recipient, $payload);
            }
        } catch (\Throwable $e) {
            Log::warning('[SalaryIncreaseNotifier] Failed to notify decision.', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
