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
            $this->sendEmailNotification($serviceRequest);
        }
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
            $this->sendEmailNotification($serviceRequest);
        }
    }

    /**
     * Send email notification to the assigned employee.
     */
    protected function sendEmailNotification(ServiceRequest $serviceRequest): void
    {
        $employee = $serviceRequest->assignedTo;
        if ($employee && $employee->email) {
            \Illuminate\Support\Facades\Mail::raw(
                "A new service request #{$serviceRequest->id} has been assigned to you.\nDescription: " . ($serviceRequest->description ?? 'No description') . "\nStatus: " . $serviceRequest->status,
                function ($message) use ($employee, $serviceRequest) {
                    $message->to($employee->email)
                            ->subject("Notification: Service Request #{$serviceRequest->id} Assigned");
                }
            );
        }
    }
}
