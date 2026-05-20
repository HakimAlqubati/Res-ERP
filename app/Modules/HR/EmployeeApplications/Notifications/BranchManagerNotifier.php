<?php

namespace App\Modules\HR\EmployeeApplications\Notifications;

use App\Models\Branch;
use App\Models\AppLog;
use App\Mail\GeneralNotificationMail;
use App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker;
use Illuminate\Support\Facades\Mail;

class BranchManagerNotifier
{
    public function __construct(
        private readonly MonthlyPendingApplicationChecker $checker
    ) {}

    /**
     * Notify the manager of the given branch if pending applications exist for this month.
     *
     * @return int Number of pending applications found (0 = none, no notification sent).
     */
    public function notifyIfPending(Branch $branch): int
    {
        $count = $this->countPendingApplications($branch->id);

        if ($count === 0) {
            return 0;
        }

        $manager = $branch->user;

        if (! $manager) {
            return $count;
        }

        $this->sendFcmNotification($manager, $branch, $count);
        $this->sendEmailNotification($manager, $branch, $count);

        return $count;
    }

    // -------------------------------------------------------------------------

    private function countPendingApplications(int $branchId): int
    {
        return $this->checker->getTotalCount(['branch_id' => $branchId]);
    }

    private function sendFcmNotification($manager, Branch $branch, int $count): void
    {
        $token = $manager->fcm_token ?? null;

        if (! $token) {
            return;
        }

        sendNotification(
            deviceToken: $token,
            title: __('Pending requests required your approval  :branch', ['branch' => $branch->name]),
            body: __('You have :count pending application(s) awaiting your review.', ['count' => $count]),
            data: [
                'type'      => 'pending_applications',
                'branch_id' => (string) $branch->id,
                'count'     => (string) $count,
            ]
        );
    }

    private function sendEmailNotification($manager, Branch $branch, int $count): void
    {
        if (! $manager->email) {
            return;
        }

        $title   = "Pending Applications ({$count}) — {$branch->name}";
        $message = "There are {$count} pending employee application(s) in branch [{$branch->name}] awaiting your review.";

        try {
            Mail::to($manager->email)->send(new GeneralNotificationMail($title, $message));
        } catch (\Exception $e) {
            AppLog::write(
                message: "Failed to send pending-applications email to manager [{$manager->email}]: {$e->getMessage()}",
                level: AppLog::LEVEL_ERROR,
                context: 'HR_PENDING_NOTIFY',
            );
        }
    }
}
