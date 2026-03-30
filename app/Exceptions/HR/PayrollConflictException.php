<?php

namespace App\Exceptions\HR;

use Exception;

/**
 * Exception thrown when an HR operation conflicts with an existing payroll run.
 */
class PayrollConflictException extends Exception
{
    /**
     * Exception for attempting to rollback or delete overtime after payroll creation.
     *
     * @return self
     */
    public static function overtimeLockedByPayroll(): self
    {
        return new self(
            'Locked: Payroll has already been initiated for this period.'
        );
    }
}
