<?php

namespace App\Modules\HR\ApprovalPolicies\Exceptions;

use RuntimeException;

class ApprovalWorkflowException extends RuntimeException
{
    public static function missingPolicy(string $recordClass): self
    {
        return new self("No active approval policy found for {$recordClass}.");
    }

    public static function missingApprovers(): self
    {
        return new self('The approval policy did not resolve any approvers.');
    }

    public static function circularManagerChain(): self
    {
        return new self('Circular manager chain detected while resolving approval steps.');
    }
}
