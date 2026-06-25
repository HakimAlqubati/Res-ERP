<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use Closure;

class ApprovalWorkflowGuard
{
    private static int $bypassDepth = 0;

    public function isBypassed(): bool
    {
        return self::$bypassDepth > 0;
    }

    public function withoutGuard(Closure $callback): mixed
    {
        self::$bypassDepth++;

        try {
            return $callback();
        } finally {
            self::$bypassDepth--;
        }
    }
}
