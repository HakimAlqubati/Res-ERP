<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Models\EquipmentLog;

class ServiceRequestObserver
{
    /**
     * Handle the ServiceRequest "created" event.
     */
    public function created(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->logToEquipment(
            EquipmentLog::ACTION_SERVICED,
            "Service request #{$serviceRequest->id} opened: " . mb_strimwidth($serviceRequest->description ?? '', 0, 50, '...')
        );

        if ($serviceRequest->assigned_to) {
            $this->sendNotifications($serviceRequest);
        }

        $this->notifyMaintenanceManagers($serviceRequest);
    }

    /**
     * Handle the ServiceRequest "updated" event.
     */
    public function updated(ServiceRequest $serviceRequest): void
    {
        if ($serviceRequest->isDirty('status')) {
            $serviceRequest->logToEquipment(
                EquipmentLog::ACTION_UPDATED,
                "Service request #{$serviceRequest->id} is now marked as '{$serviceRequest->status}'"
            );
        }

        if ($serviceRequest->isDirty('assigned_to') && $serviceRequest->assigned_to) {
            $this->sendNotifications($serviceRequest);
        }
    }

    /**
     * Send email and firebase notifications to the assigned employee.
     */
    protected function sendNotifications(ServiceRequest $serviceRequest): void
    {
        $employee = $serviceRequest->assignedTo;

        if (!$employee) {
            return;
        }

        $subject = "Notification: Service Request #{$serviceRequest->id} Assigned";
        $body = "A new service request #{$serviceRequest->id} has been assigned to you.\nDescription: " . ($serviceRequest->description ?? 'No description') . "\nStatus: " . $serviceRequest->status;

        // // Send Email
        // if ($employee->email) {
        //     \Illuminate\Support\Facades\Mail::raw(
        //         $body,
        //         function ($message) use ($employee, $subject) {
        //             $message->to($employee->email)
        //                     ->subject($subject);
        //         }
        //     );
        // }

        // Send Firebase Notification
        if ($employee->user && $employee->user->fcm_token) {
            sendNotification(
                $employee->user->fcm_token,
                $subject,
                $body,
                [
                    'service_request_id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                    'type' => 'service_request_assigned'
                ]
            );
        }
    }

    /**
     * Send firebase notifications to maintenance managers (Role 14).
     */
    protected function notifyMaintenanceManagers(ServiceRequest $serviceRequest): void
    {
        $maintenanceManagers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('roles.id', 14);
        })->get();

        $subject = "New Service Request #{$serviceRequest->id}";
        $body = "A new service request has been created.\nDescription: " . mb_strimwidth($serviceRequest->description ?? 'No description', 0, 100, '...');

        foreach ($maintenanceManagers as $manager) {
            if ($manager->fcm_token) {
                sendNotification(
                    $manager->fcm_token,
                    $subject,
                    $body,
                    [
                        'service_request_id' => $serviceRequest->id,
                        'status' => $serviceRequest->status,
                        'type' => 'service_request_created'
                    ]
                );
            }
        }
    }
}
