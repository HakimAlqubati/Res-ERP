<?php

namespace App\Observers;

use App\Facades\Warnings;
use App\Enums\Warnings\WarningLevel;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Facades\Log;

/**
 * Observer for EmployeeApplicationV2 model.
 * Handles notification logic when applications are created.
 */
class EmployeeApplicationObserver
{
    /**
     * Handle the EmployeeApplicationV2 "created" event.
     */
    public function created(EmployeeApplicationV2 $app): void
    {
        try {
            // جلب الموظف مع مديره واليوزر الخاص بالمدير
            $employee = $app->employee()->with(['manager.user'])->first();

            if (!$employee || !$employee->manager || !$employee->manager->user) {
                return; // لا يوجد مدير/يوزر -> لا إشعار
            }

            $managerUser = $employee->manager->user;

            // تجنّب إشعار الشخص نفسه (لو أنشأ الطلب هو نفسه المدير)
            if (auth()->check() && auth()->id() === $managerUser->id) {
                return;
            }

            // عنوان ونص الإشعار
            $typeName = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[$app->application_type_id] ?? 'Application';
            $title    = 'New Request from ' . ($employee->name ?? 'Employee');
            $lines    = [
                "Type: {$typeName}",
                "Date: " . ($app->application_date ?: now()->toDateString()),
            ];
            $body = implode("\n", $lines);

            // رابط شاشة الطلبات في لوحة التحكم + فلتر التبويب (إن وُجد)
            $baseUrl = EmployeeApplicationResource::getUrl();
            $filterSuffix = EmployeeApplicationV2::APPLICATION_TYPE_FILTERS[$app->application_type_id] ?? '';
            $url = rtrim($baseUrl, '/') . $filterSuffix;

            // إرسال الإشعار
            Warnings::send(
                $managerUser,
                WarningPayload::make(
                    $title,
                    $body,
                    WarningLevel::Info
                )
                    ->ctx([
                        'application_id' => $app->id,
                        'employee_id'    => $employee->id,
                        'type_id'        => $app->application_type_id,
                    ])
                    ->url($url)
                    ->scope("emp-app-{$app->id}")   // scope فريد لتجنّب التكرار
                    ->expires(now()->addHours(24))  // صلاحية الإشعار
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to notify manager for new EmployeeApplicationV2', [
                'application_id' => $app->id ?? null,
                'employee_id'    => $app->employee_id ?? null,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
