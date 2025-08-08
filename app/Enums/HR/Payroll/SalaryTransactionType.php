<?php

namespace App\Enums\HR\Payroll;

enum SalaryTransactionType: string
{
    const TYPE_SALARY    = 'salary';
    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_DEDUCTION = 'deduction';
    const TYPE_ADVANCE   = 'advance';
    const TYPE_INSTALL   = 'installment';
    const TYPE_BONUS     = 'bonus';
    const TYPE_OVERTIME  = 'overtime';
    const TYPE_PENALTY   = 'penalty';
    const TYPE_OTHER     = 'other';
    const TYPE_NET_SALARY = 'net_salary';
}
