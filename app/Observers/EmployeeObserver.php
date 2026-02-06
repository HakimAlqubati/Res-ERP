<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Storage;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // فقط إذا لم يكن هناك user مرتبط وكان لديه بريد إلكتروني
        // تخطي إنشاء المستخدم إذا كان البريد الإلكتروني فارغاً (مثل حالة الاستيراد من Excel)
        if (!$employee->user_id && !empty($employee->email)) {

            $existingUser = User::where('email', $employee->email)->first();
            if ($existingUser) {
                throw new Exception("The email {$employee->email} is already used by another user.");
            }

            // الحصول على user_id الخاص بالمدير
            $managerUserId = Employee::find($employee->manager_id)?->user_id;

            // إعداد البيانات الأساسية
            $userData = [
                'name'          => $employee->name,
                'email'         => $employee->email,
                'branch_id'     => $employee->branch_id,
                'phone_number'  => $employee->phone_number,
                'user_type'     => $employee?->employee_type,
                'nationality'   => $employee?->nationality,
                'gender'        => $employee->gender,
                'password'      => bcrypt('123456'),
                'owner_id'      => $managerUserId,
            ];

            // إذا كان لديه avatar نضيفه
            if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                $userData['avatar'] = $employee->avatar;
            }

            // إنشاء اليوزر
            $user = User::create($userData);

            // ربط user_id بالموظف
            $employee->user_id = $user->id;
            $employee->save();
        }
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee)
    {
        // Access the related user model
        $user = $employee->user;
        if ($user) {
            $managerUserId = Employee::find($employee->manager_id)?->user_id;
            $user->owner_id = $managerUserId;
            // Check if 'email' or 'phone_number' changed
            // if ($employee->isDirty('email')) {
            if ($employee->email) {
                $user->email = $employee->email;
            }
            // }
            // if ($employee->isDirty('phone_number')) {
            $user->phone_number = $employee?->phone_number;


            $user->name = $employee->name;
            $user->branch_id = $employee?->branch_id;


            $user->gender = $employee?->gender;

            if (!is_null($employee?->nationality)) {
                $user->nationality = $employee->nationality;
            }
            $user->user_type = $employee?->employee_type;

            if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                $user->avatar = $employee->avatar;
            }
            // if ($employee->avatar && Storage::disk('s3')->exists($employee->avatar)) {
            //     $user->avatar = $employee->avatar;
            // }

            // Save changes to the user model
            $user->save();
        }
    }

    /**
     * Handle the Employee "saved" event.
     */
    public function saved(Employee $employee): void
    {
        // فهرسة الصورة في AWS Rekognition عند الإنشاء أو عند تغيير الصورة
        if ($employee->avatar && ($employee->wasRecentlyCreated || $employee->wasChanged('avatar'))) {
            \App\Services\S3ImageService::indexEmployeeImage($employee->id);
        }

        // تسجيل سجل الفرع عند تغيير branch_id
        if ($employee->wasChanged('branch_id')) {
            // إغلاق السجل السابق للفرع (إن وجد)
            $previousBranchLog = $employee->branchLogs()
                ->whereNull('end_at')
                ->latest()
                ->first();

            if ($previousBranchLog) {
                $previousBranchLog->update(['end_at' => now()]);
            }

            // إنشاء سجل جديد للفرع الحالي
            $employee->branchLogs()->create([
                'branch_id'  => $employee->branch_id,
                'start_at'   => now(),
                'end_at'     => null,
                'created_by' => auth()->id(),
            ]);
        }
    }
    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "force deleted" event.
     */
    public function forceDeleted(Employee $employee): void
    {
        //
    }
}
