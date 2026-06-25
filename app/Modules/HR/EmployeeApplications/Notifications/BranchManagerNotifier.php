<?php

namespace App\Modules\HR\EmployeeApplications\Notifications;

use App\Mail\GeneralNotificationMail;
use App\Models\AppLog;
use App\Models\Branch;
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
        $summary = $this->checker->getDashboardSummary(['branch_id' => $branch->id]);
        $count = $summary['total_count'] ?? 0;

        if ($count === 0) {
            return 0;
        }

        $manager = $branch->user;

        if (! $manager) {
            return $count;
        }

        $breakdown = $summary['breakdown'] ?? [];

        $this->sendFcmNotification($manager, $branch, $count, $breakdown);
        $this->sendEmailNotification($manager, $branch, $count, $breakdown);

        return $count;
    }

    // -------------------------------------------------------------------------

    private function sendFcmNotification($manager, Branch $branch, int $count, array $breakdown): void
    {
        $token = $manager->fcm_token ?? null;

        if (! $token) {
            return;
        }

        $details = [];
        foreach ($breakdown as $item) {
            $details[] = "{$item['count']} {$item['type']}";
        }
        $detailsString = implode("\n", $details);

        $body = __('You have :count pending request(s) awaiting your review.', ['count' => $count]);
        if (!empty($detailsString)) {
            $body .= "\n\n" . $detailsString;
        }

        sendNotification(
            deviceToken: $token,
            title: __('Pending requests required your approval  :branch', ['branch' => $branch->name]),
            body: $body,
            data: [
                'type' => 'pending_applications',
                'branch_id' => (string) $branch->id,
                'count' => (string) $count,
            ]
        );
    }

    private function sendEmailNotification($manager, Branch $branch, int $count, array $breakdown): void
    {
        if (! $manager->email) {
            return;
        }

        $title = "Pending requests required your approval ({$count}) — {$branch->name}";
        $message = "There are {$count} pending employee request(s) in branch [{$branch->name}] awaiting your review.";

        if (!empty($breakdown)) {
            $message .= "\n\nDetails:\n";
            foreach ($breakdown as $item) {
                $message .= "- {$item['count']} {$item['type']}\n";
            }
        }

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
