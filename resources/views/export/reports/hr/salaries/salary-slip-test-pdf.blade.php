<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .salary-slip {
            width: 100%;
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
        }
        footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="salary-slip">
        <header>
            <h1>Salary Slip</h1>
            <p>{{ $monthName }}</p>
        </header>

        <h3>Employee Details</h3>
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $employee->name }}</td>
            </tr>
            <tr>
                <th>Employee ID</th>
                <td>{{ $employee->employee_no }}</td>
            </tr>
            <tr>
                <th>Job Title</th>
                <td>{{ $employee->job_title }}</td>
            </tr>
            <tr>
                <th>Branch</th>
                <td>{{ $employee->branch->name }}</td>
            </tr>
        </table>

        <h3>Earnings and Deductions</h3>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Earnings (RM)</th>
                    <th>Deductions (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employeeAllowances as $allowance)
                <tr>
                    <td>{{ $allowance['allowance_name'] }}</td>
                    <td>{{ number_format($allowance['amount'], 2) }}</td>
                    <td></td>
                </tr>
                @endforeach
                @foreach ($employeeDeductions as $deduction)
                <tr>
                    <td>{{ $deduction['deduction_name'] }}</td>
                    <td></td>
                    <td>{{ number_format($deduction['deduction_amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td>Overtime Pay</td>
                    <td>{{ number_format($data['details'][0]['overtime_pay'], 2) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Bonus</td>
                    <td>{{ number_format($data['details'][0]['total_incentives'], 2) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <th>Total</th>
                    <th>{{ number_format($totalAllowanceAmount, 2) }}</th>
                    <th>{{ number_format($totalDeductionAmount, 2) }}</th>
                </tr>
            </tbody>
        </table>

        <footer>
            <p><strong>Net Salary:</strong> RM {{ number_format($data['details'][0]['net_salary'], 2) }}</p>
        </footer>
    </div>
</body>

</html>
