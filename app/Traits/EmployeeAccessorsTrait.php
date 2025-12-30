<?php

namespace App\Traits;

use App\Models\EmployeeApplicationV2;
use App\Models\EmployeePeriodLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Trait containing all accessor methods for Employee model.
 */
trait EmployeeAccessorsTrait
{
    public function getRequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', true);
        })->count();
    }

    public function getUnrequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', false);
        })->count();
    }

    public function getAvatarImageAttribute()
    {
        if ($this->avatar && Storage::disk('s3')->exists($this->avatar)) {
            return Storage::disk('s3')->url($this->avatar);
        }
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            if (env('APP_ENV') == 'local') {
                return Storage::disk('public')->url($this->avatar);
            }
            return url('/') . Storage::disk('public')->url($this->avatar);
        }

        $defaultAvatarPath = 'imgs/avatar.png';

        if (Storage::disk('public')->exists($defaultAvatarPath)) {
            if (env('APP_ENV') == 'local') {
                return Storage::disk('public')->url($defaultAvatarPath);
            }
            return url('/') . Storage::disk('public')->url($defaultAvatarPath);
        }

        return asset('imgs/avatar.png');
    }

    public function getAvatarImageAttributeOld()
    {
        if ($this->avatar && Storage::disk('s3')->exists($this->avatar)) {
            return Storage::disk('s3')->url($this?->avatar);
        }
        if (!$this->avatar) {
            return url('/storage') . '/' . 'employees/default/avatar.png';
        }
    }

    public function getAvatarImage2Attribute()
    {
        $filePath = 'public/' . $this->avatar;

        if (Storage::exists($filePath) && ($this->avatar != 'employees/default/avatar.png' || $this->avatar != null)) {
            $arr = explode('/', $this->avatar);
            if (is_array($arr) && count($arr) > 0) {
                return $arr[1] ?? $this->avatar;
            }
            return $this->avatar;
        } else if ($this->avatar == null) {
            return 'no';
        }
        return 'no';
    }

    public function getApprovedAdvanceApplicationAttribute()
    {
        return $this->approvedAdvanceApplication()->get()->map(function ($application) {
            return [
                'id'         => $application->id,
                'paid'       => $application->paid_installments_count,
                'details'    => json_decode($application->details, true),
                'created_at' => $application->created_at->format('Y-m-d'),
                'updated_at' => $application->updated_at->format('Y-m-d'),
            ];
        });
    }

    public function getApprovedLeaveRequestsAttribute()
    {
        return $this->approvedLeaveApplication()->get()->map(function ($leaveRequest) {
            return [
                'id'            => $leaveRequest->id,
                'leave_type_id' => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_leave_type_id'] ?? null : null,
                'from_date'     => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_from_date'] ?? null : null,
                'to_date'       => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_to_date'] ?? null : null,
                'days_count'    => $leaveRequest->details ? json_decode($leaveRequest->details, true)['detail_days_count'] ?? null : null,
                'created_at'    => $leaveRequest->created_at->format('Y-m-d'),
                'updated_at'    => $leaveRequest->updated_at->format('Y-m-d'),
            ];
        });
    }

    public function getHoursCountAttribute()
    {
        $totalHours = 0;

        foreach ($this->periods as $period) {
            $start = Carbon::parse($period->start_at);
            $end   = Carbon::parse($period->end_at);
            $totalHours += $start->diffInHours($end);
        }

        return $totalHours;
    }

    public function getHasUserAttribute()
    {
        if ($this->user()->withTrashed()->exists()) {
            return true;
        }
        return false;
    }

    public function getTaxPercentageAttribute()
    {
        if ($this->nationality !== 'MY') {
            return 0;
        }

        $salary = $this->salary;

        foreach (self::TAX_BRACKETS as $bracket) {
            [$min, $max, $percentage] = $bracket;

            if ($salary >= $min && $salary <= $max) {
                return $percentage;
            }
        }

        return 0;
    }

    public function getIsCitizenAttribute()
    {
        $defaultNationality = setting('default_nationality');
        return $this->nationality == $defaultNationality;
    }

    public function getIsForeignAttribute()
    {
        return !$this->is_citizen;
    }

    public function getGenderTitleAttribute()
    {
        switch ($this->gender) {
            case 1:
                return 'Male';
            case 0:
                return 'Female';
            default:
                return 'Not set';
        }
    }

    public function getPeriodsCountAttribute()
    {
        return $this->periods()->count();
    }

    public function logPeriodChange(array $periodIds, $action)
    {
        EmployeePeriodLog::create([
            'employee_id' => $this->id,
            'period_ids'  => json_encode($periodIds),
            'action'      => $action,
        ]);
    }

    public function getApprovedPenaltyDeductionsForPeriod($year, $month)
    {
        return $this->approvedPenaltyDeductions()
            ->select('year', 'month', 'penalty_amount', 'deduction_id', 'hr_deductions.name as deduction_name')
            ->leftJoin('hr_deductions', 'hr_deductions.id', '=', 'hr_penalty_deductions.deduction_id')
            ->where('year', $year)
            ->where('month', $month)
            ->get();
    }
}
