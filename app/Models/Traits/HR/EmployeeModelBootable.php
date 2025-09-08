<?php
namespace App\Models\Traits\HR;

use App\Mail\MailableEmployee;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

trait EmployeeModelBootable
{
    public static function bootEmployeeModelBootable()
    {
        static::addGlobalScopesBasedOnUser();

        static::updating(function ($employee) {
            if ($employee->is_ceo) {
                // Unset the previous default store
                Employee::where('is_ceo', true)
                    ->where('id', '!=', $employee->id)
                    ->update(['is_ceo' => false]);
            }

            // Check if the 'branch_id' attribute is being updated
            if ($employee->isDirty('branch_id')) {
                // Log the branch change in the EmployeeBranchLog table
                EmployeeBranchLog::create([
                    'employee_id' => $employee->id,
                    'branch_id'   => $employee->branch_id,
                    'start_at'    => now(),        // Set the start time of the new branch
                    'end_at'      => null,         // End time is null because this is the current branch
                    'created_by'  => auth()->id(), // Who made the change
                ]);

                // Optionally, you could handle the previous branch log (if you want to mark the previous branch as ended)
                $previousBranchLog = $employee->branchLogs()->whereNull('end_at')->latest()->first();
                if ($previousBranchLog) {
                    // Update the previous branch log with the 'end_at' timestamp
                    $previousBranchLog->update(['end_at' => now()]);
                }
            }
        });

        static::created(function ($employee) {
            // فقط إذا لم يكن هناك user مرتبط
            if (! $employee->user_id) {
                // الحصول على user_id الخاص بالمدير
                $managerUserId = Employee::find($employee->manager_id)?->user_id;

                // إعداد البيانات الأساسية
                $userData = [
                    'name'          => $employee->name,
                    'email'         => $employee->email,
                    'branch_id'     => $employee->branch_id,
                    'phone_number'  => $employee->phone_number,
                    'user_type' => $employee?->employee_type,
                    'nationality' => $employee?->nationality,
                    'gender'  => $employee->gender,
                    'password'      => bcrypt('123456'),
                    'owner_id'      => $managerUserId,
                ];

                // إذا كان لديه avatar نضيفه
                if ($employee->avatar && Storage::disk('s3')->exists($employee->avatar)) {
                    $userData['avatar'] = $employee->avatar;
                }
                if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                    $userData['avatar'] = $employee->avatar;
                }

                // إنشاء اليوزر
                $user = User::create($userData);

                // ربط user_id بالموظف
                $employee->user_id = $user->id;
                $employee->save();

                // إعطاءه الدور المناسب
                // $user->assignRole(8);

                // إرسال بريد إلكتروني 
                Mail::to($user->email)->send(new MailableEmployee($employee->name, $user->email,));
            }
        });
    }

    protected static function addGlobalScopesBasedOnUser()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (Builder $builder) {
                $builder->whereNotNull('branch_id')->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        } elseif (isStuff()) {
            static::addGlobalScope(function (Builder $builder) {
                // dd(auth()->user()->employee->id);
                // $builder->where('id', auth()->user()->employee->id); // Add your default query here
            });
        }
    }
}
