<table>
    <thead>
        <tr>
            <th>{{ __('Employee No') }}</th>
            <th>{{ __('Employee Name') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Base Salary') }}</th>
            @foreach($additionColumns as $col)
            <th>{{ __($col) }}</th>
            @endforeach
            <th>{{ __('Total Additions') }}</th>
            @foreach($deductionColumns as $col)
            <th>{{ __($col) }}</th>
            @endforeach
            <th>{{ __('Total Deductions') }}</th>
            @foreach($employerContributionColumns as $col)
                <th>{{ __($col) }}</th>
            @endforeach
            <th>{{ __('Advance Wages') }}</th>
            <th>{{ __('Net Salary') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
        <tr>
            <td>{{ $row['employee_no'] }}</td>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ $row['branch_name'] }}</td>
            <td>{{ $row['base_salary'] }}</td>

            @foreach($additionColumns as $col)
            <td>{{ $row['additions'][$col] ?? 0 }}</td>
            @endforeach
            <td>{{ $row['total_additions'] }}</td>

            @foreach($deductionColumns as $col)
            <td>{{ $row['deductions'][$col] ?? 0 }}</td>
            @endforeach
            <td>{{ $row['total_deductions'] }}</td>
            @foreach($employerContributionColumns as $col)
                <td>{{ $row['employer_contributions'][$col] ?? 0 }}</td>
            @endforeach
            <td>{{ $row['advance_wages'] ?? 0 }}</td>
            <td>{{ $row['net_salary'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: right;">{{ __('Total') }}</td>
            <td style="font-weight: bold;">{{ $totals['base_salary'] }}</td>

            @foreach($additionColumns as $col)
            <td style="font-weight: bold;">{{ $totals['additions'][$col] ?? 0 }}</td>
            @endforeach
            <td style="font-weight: bold;">{{ $totals['total_additions'] }}</td>

            @foreach($deductionColumns as $col)
            <td style="font-weight: bold;">{{ $totals['deductions'][$col] ?? 0 }}</td>
            @endforeach
            <td style="font-weight: bold;">{{ $totals['total_deductions'] }}</td>
            @foreach($employerContributionColumns as $col)
                <td style="font-weight: bold;">{{ $totals['employer_contributions'][$col] ?? 0 }}</td>
            @endforeach
            <td style="font-weight: bold;">{{ $totals['advance_wages'] ?? 0 }}</td>
            <td style="font-weight: bold;">{{ $totals['net_salary'] }}</td>
        </tr>
    </tfoot>
</table>