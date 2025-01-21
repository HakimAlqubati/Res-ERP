<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deductions</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Deductions Summary</h1>

    {{-- <h2>Summed Deductions (Excluding Last Month)</h2>
    <table>
        <thead>
            <tr>
                <th>Deduction Name</th>
                <th>Deduction Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summedDeductions as $deduction)
                <tr>
                    <td>{{ $deduction['deduction_name'] }}</td>
                    <td>{{ number_format($deduction['deduction_amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No deductions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table> --}}

    {{-- <h2>Last Month Deductions</h2>
    <table>
        <thead>
            <tr>
                <th>Deduction Name</th>
                <th>Deduction Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lastMonthDeductions as $deduction)
                <tr>
                    <td>{{ $deduction['deduction_name'] }}</td>
                    <td>{{ number_format($deduction['deduction_amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No deductions found for the last month.</td>
                </tr>
            @endforelse
        </tbody>
    </table> --}}

    <h2>Total Deductions (All Months Combined)</h2>
    <table>
        <thead>
            <tr>
                <th>Deduction Name</th>
                <th>Total Deduction Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($totalDeductions as $deduction)
                <tr>
                    <td>{{ $deduction['deduction_name'] }}</td>
                    <td>{{ number_format($deduction['deduction_amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No deductions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
