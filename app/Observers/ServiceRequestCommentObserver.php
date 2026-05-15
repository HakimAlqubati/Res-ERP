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

        $branchManager = $serviceRequest->branch->user;
        
        if ($branchManager && $branchManager->fcm_token) {
            // Do not notify the manager if they are the ones who created the comment
            if ($comment->created_by === $branchManager->id) {
                return;
            }

            $commenterName = $comment->user ? $comment->user->name : 'Someone';
            
            sendNotification(
                $branchManager->fcm_token,
                "New Comment on Service Request #{$serviceRequest->id}",
                "{$commenterName} added a comment: " . mb_strimwidth($comment->comment ?? '', 0, 100, '...'),
                [
                    'service_request_id' => $serviceRequest->id,
                    'comment_id' => $comment->id,
                    'type' => 'service_request_comment'
                ]
            );
        }
    }
}
