<?php

namespace App\Observers;

use App\Models\ServiceRequestComment;

class ServiceRequestCommentObserver
{
    /**
     * Handle the ServiceRequestComment "created" event.
     */
    public function created(ServiceRequestComment $comment): void
    {
        $serviceRequest = $comment->serviceRequest;
        
        if (!$serviceRequest || !$serviceRequest->branch) {
            return;
        }

        $usersToNotify = collect();

        // 1. Branch Manager
        if ($serviceRequest->branch && $serviceRequest->branch->user) {
            $usersToNotify->push($serviceRequest->branch->user);
        }

        // 2. Maintenance Managers (Role 14)
        $maintenanceManagers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('roles.id', 14);
        })->get();

        $usersToNotify = $usersToNotify->merge($maintenanceManagers)->unique('id');

        // Remove the user who created the comment so they don't get notified of their own comment
        $usersToNotify = $usersToNotify->reject(function ($user) use ($comment) {
            return $user->id === $comment->created_by;
        });

        if ($usersToNotify->isEmpty()) {
            return;
        }

        $commenterName = $comment->user ? $comment->user->name : 'Someone';
        $subject = "New Comment on Service Request #{$serviceRequest->id}";
        $body = "{$commenterName} added a comment: " . mb_strimwidth($comment->comment ?? '', 0, 100, '...');

        foreach ($usersToNotify as $user) {
            if ($user->fcm_token) {
                sendNotification(
                    $user->fcm_token,
                    $subject,
                    $body,
                    [
                        'service_request_id' => $serviceRequest->id,
                        'comment_id' => $comment->id,
                        'type' => 'service_request_comment'
                    ]
                );
            }
        }
    }
}
