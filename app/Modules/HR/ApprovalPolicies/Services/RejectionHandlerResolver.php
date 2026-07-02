<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\AdvanceWage;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Modules\HR\ApprovalPolicies\Contracts\RejectionHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\AdvanceWageRejectionHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplicationRejectionHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeOvertimeRejectionHandler;
use Illuminate\Database\Eloquent\Model;

class RejectionHandlerResolver
{
    /**
     * @var array<class-string<Model>, class-string<RejectionHandler>>
     */
    private array $handlers = [
        EmployeeApplicationV2::class => EmployeeApplicationRejectionHandler::class,
        EmployeeOvertime::class => EmployeeOvertimeRejectionHandler::class,
        AdvanceWage::class => AdvanceWageRejectionHandler::class,
    ];

    public function resolve(Model $record): ?RejectionHandler
    {
        $handlerClass = $this->handlers[$record::class] ?? null;

        return $handlerClass ? app($handlerClass) : null;
    }
}
