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
            'Action denied: This overtime record is linked to a period for which a payroll run has already been initiated. ' .
            'Modifying or deleting it would cause data inconsistency.'
        );
    }
}
