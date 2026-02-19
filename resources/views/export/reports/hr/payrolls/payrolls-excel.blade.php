<table>
    <thead>
        <tr>
            <th>{{ __('Employee No') }}</th>
            <th>{{ __('Employee Name') }}</th>
            <th>{{ __('Base Salary') }}</th>
            <th>{{ __('Allowances') }}</th>
            <th>{{ __('Deductions') }}</th>
            <th>{{ __('Net Salary') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($payrolls as $payroll)
        <tr>
            <td>{{ $payroll->employee?->employee_no }}</td>
            <td>{{ $payroll->employee?->name }}</td>
            <td>{{ $payroll->base_salary }}</td>
            <td>{{ $payroll->transactions()->where('operation', '+')->sum('amount') }}</td>
            <td>{{ $payroll->transactions()->where('operation', '-')->where('type', '!=', \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD->value)->sum('amount') }}</td>
            <td>{{ $payroll->net_salary }}</td>
        </tr>
        @endforeach
    </tbody>
</table>