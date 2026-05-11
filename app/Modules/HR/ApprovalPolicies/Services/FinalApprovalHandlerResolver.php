<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\AdvanceWage;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\AdvanceWageFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplicationFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeOvertimeFinalApprovalHandler;
use Illuminate\Database\Eloquent\Model;

class FinalApprovalHandlerResolver
{
    /**
     * @var array<class-string<Model>, class-string<FinalApprovalHandler>>
     */
    private array $defaultHandlers = [
        EmployeeApplicationV2::class => EmployeeApplicationFinalApprovalHandler::class,
        EmployeeOvertime::class => EmployeeOvertimeFinalApprovalHandler::class,
        AdvanceWage::class => AdvanceWageFinalApprovalHandler::class,
    ];

    public function resolve(Model $record): ?FinalApprovalHandler
    {
        $handlerClass = $this->defaultHandlers[$record::class] ?? null;

        if (! $handlerClass) {
            return null;
        }

        return app($handlerClass);
    }
}
