<?php

namespace App\Exceptions\HR;

use Exception;

class LeaveApprovalException extends Exception
{
    public static function missingDetails(): self
    {
        return new self('Leave request details are missing for this application.');
    }

    public static function balanceNotFound(int $year, int $month, int $type): self
    {
        return new self("Leave balance not found. Year: {$year}, Month: {$month}, Type: {$type}");
    }

    public static function notApprovedStatus(): self
    {
        return new self('Application is not in an approved status to be reversed.');
    }
}
