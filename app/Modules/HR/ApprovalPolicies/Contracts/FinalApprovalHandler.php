<?php

namespace App\Modules\HR\ApprovalPolicies\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface FinalApprovalHandler
{
    public function handle(Model&ApprovableRecord $record, User $approvedBy): void;
}
