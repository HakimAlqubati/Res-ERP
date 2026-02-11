<?php

namespace App\Enums\HR\Payroll;

enum SalaryTransactionType: string
{
    case TYPE_SALARY    = 'salary';
    case TYPE_ALLOWANCE = 'allowance';
    case TYPE_DEDUCTION = 'deduction';
    case TYPE_ADVANCE   = 'advance';
    case TYPE_INSTALL   = 'installment';
    case TYPE_BONUS     = 'bonus';
    case TYPE_OVERTIME  = 'overtime';
    case TYPE_PENALTY   = 'penalty';
    case TYPE_OTHER     = 'other';
    case TYPE_NET_SALARY = 'net_salary';
    case TYPE_ADJUSTMENT = 'adjustment';   // Salary Adjustment
    case TYPE_EMPLOYER_CONTRIBUTION = 'employer_contribution';
    case TYPE_CARRY_FORWARD = 'carry_forward';
}
