<?php

namespace App\Traits;

use App\Models\EmployeeApplicationV2;

/**
 * Trait containing all accessor methods for EmployeeApplicationV2 model.
 * Extracted to reduce model file size.
 */
trait EmployeeApplicationAccessors
{
    public function getDetailTimeAttribute()
    {
        if ($this->application_type_id == 2) {
            return $this->missedCheckinRequest?->time;
        }

        if ($this->application_type_id == 4) {
            return $this->missedCheckoutRequest?->time;
        }

        return null;
    }

    public function getDetailDateAttribute()
    {
        if ($this->application_type_id == 2) {
            return $this->missedCheckinRequest?->date;
        }

        if ($this->application_type_id == 3) {
            $details = json_decode($this->details, true);
            return $details['detail_date'] ?? null;
        }

        if ($this->application_type_id == 4) {
            return $this->missedCheckoutRequest?->date;
        }

        return null;
    }

    public function getDetailMonthlyDeductionAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->monthly_deduction_amount;
        }
        return null;
    }

    public function getDetailAdvanceAmountAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->advance_amount;
        }
        return null;
    }

    public function getDetailDeductionStartsFromAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->deduction_starts_from;
        }
        return null;
    }

    public function getDetailDeductionEndsAtAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->deduction_ends_at;
        }
        return null;
    }

    public function getDetailNumberOfMonthsOfDeductionAttribute()
    {
        if ($this->application_type_id == 3) {
            return $this->advanceRequest->number_of_months_of_deduction;
        }
        return null;
    }

    public function getDetailFromDateAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->start_date ?? null;
        }
        return null;
    }

    public function getDetailToDateAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->end_date ?? null;
        }
        return null;
    }

    public function getLeaveTypeModelAttribute()
    {
        if ($this->application_type_id != EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        return $this->leaveRequest?->leaveType;
    }

    public function getLeaveTypeNameAttribute()
    {
        if ($this->application_type_id != EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        return $this->leaveRequest?->leaveType?->name;
    }

    public function getLeaveTypeIdAttribute()
    {
        if ($this->application_type_id != EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            return null;
        }

        return $this->leaveRequest?->leaveType?->id;
    }

    public function getDetailDaysCountAttribute()
    {
        if ($this->application_type_id == 1) {
            return $this->leaveRequest->days_count ?? null;
        }
        return null;
    }

    public function getDetailYearAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_year'] ?? null;
        }
        return null;
    }

    public function getDetailMonthAttribute()
    {
        if ($this->application_type_id == 1) {
            $details = json_decode($this->details, true);
            return $details['detail_month'] ?? null;
        }
        return null;
    }
}
