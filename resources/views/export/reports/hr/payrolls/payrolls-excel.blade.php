<table>
    <thead>
        <tr>
            <th>{{ __('Employee No') }}</th>
            <th>{{ __('Employee Name') }}</th>
            <th>{{ __('Base Salary') }}</th>
            @foreach($additionColumns as $col)
                <th>{{ __($col) }}</th>
            @endforeach
            <th>{{ __('Total Allowances') }}</th>
            @foreach($deductionColumns as $col)
                <th>{{ __($col) }}</th>
            @endforeach
            <th>{{ __('Total Deductions') }}</th>
            <th>{{ __('Net Salary') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
        <tr>
            <td>{{ $row['employee_no'] }}</td>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ $row['base_salary'] }}</td>

            @foreach($additionColumns as $col)
                <td>{{ $row['additions'][$col] ?? 0 }}</td>
            @endforeach
            <td>{{ $row['total_additions'] }}</td>

            @foreach($deductionColumns as $col)
                <td>{{ $row['deductions'][$col] ?? 0 }}</td>
            @endforeach
            <td>{{ $row['total_deductions'] }}</td>

            <td>{{ $row['net_salary'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="font-weight: bold; text-align: right;">{{ __('Total') }}</td>
            <td style="font-weight: bold;">{{ $totals['base_salary'] }}</td>

            @foreach($additionColumns as $col)
                <td style="font-weight: bold;">{{ $totals['additions'][$col] ?? 0 }}</td>
            @endforeach
            <td style="font-weight: bold;">{{ $totals['total_additions'] }}</td>

            @foreach($deductionColumns as $col)
                <td style="font-weight: bold;">{{ $totals['deductions'][$col] ?? 0 }}</td>
            @endforeach
            <td style="font-weight: bold;">{{ $totals['total_deductions'] }}</td>

            <td style="font-weight: bold;">{{ $totals['net_salary'] }}</td>
        </tr>
    </tfoot>
</table>