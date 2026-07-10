<table>
    <thead>
        <tr>
            <th colspan="6" style="text-align:center; font-weight:bold;">
                {{ $employeeName }}
            </th>
        </tr>
        <tr>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Operation') }}</th>
            <th>{{ __('Amount') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Date') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactions as $transaction)
        <tr>
            <td>{{ $transaction->type }}</td>
            <td>{{ $transaction->description }}</td>
            <td>{{ $transaction->operation === '+' ? 'Addition' : 'Deduction' }}</td>
            <td>{{ number_format($transaction->amount, 2) }}</td>
            <td>{{ $transaction->status }}</td>
            <td>{{ $transaction->date?->format('Y-m-d') }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="5" style="text-align: right; font-weight: bold;">{{ __('Net Salary') }}</td>
            <td style="font-weight: bold;">{{ number_format($netSalary, 2) }}</td>
        </tr>
    </tbody>
</table>