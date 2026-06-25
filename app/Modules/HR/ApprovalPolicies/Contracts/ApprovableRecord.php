<?php

namespace App\Modules\HR\ApprovalPolicies\Contracts;

use App\Models\Employee;

interface ApprovableRecord
{
    public function approvalEmployee(): ?Employee;

    public function approvalBranchId(): ?int;

    public function approvalApplicationTypeId(): ?int;
}
