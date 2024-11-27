<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="salary-slip">
        <header>
            <div class="company-info">
                <!-- Left Logo -->
                <img src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo" class="logo-left">

                <!-- Company Name and Address in Center -->
                <div class="company-details">
                    <h1>{{ setting('company_name') }}</h1>
                    <p>Office: {{ setting('address') }}</p>
                    <p>{{ setting('company_phone') }}</p>
                </div>

                <!-- Right Logo -->
                <img style="top: 24px;width: 102px;" src="{{ url('/') . '/storage/logo/default.png' }}"
                    alt="Company Logo" class="logo-right">
            </div>

            <h2>SALARY SLIP</h2>
            <p class="month">{{ $monthName }}</p>
        </header>

        <section class="employee-info">
            <table class="employee-info-table">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>{{ $employee?->name }}</td>
                </tr>
                <tr>
                    <td><strong>ID No:</strong></td>
                    <td>{{ $employee?->employee_no }}</td>
                </tr>
                <tr>
                    <td><strong>Job:</strong></td>
                    <td>{{ $employee?->job_title }}</td>
                </tr>
                <tr>
                    <td><strong>Branch:</strong></td>
                    <td>{{ $branch?->name }}</td>
                </tr>
            </table>
        </section>

        <section class="earnings-deductions">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Earnings (RM)</th>
                        <th>Deductions (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ 'Basic salary' }}</td>
                        <td>{{ number_format($employee?->salary, 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach ($employeeAllowances as $allowance)
                        <tr>
                            <td>{{ $allowance['allowance_name'] ?? '' }}</td>
                            <td>{{ number_format($allowance['amount'], 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>{{ 'Overtime pay' }}</td>
                        <td>{{ number_format($data?->details[0]['overtime_pay'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>{{ 'Bonus' }}</td>
                        <td>{{ number_format($data?->details[0]['total_incentives'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach ($employeeDeductions as $deduction)
                        <tr>
                            <td>{{ $deduction['deduction_name'] ?? '' }}</td>
                            <td></td>
                            <td>{{ number_format($deduction['deduction_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>Total</th>
                        <th style="text-align: center;">{{ number_format($totalAllowanceAmount, 2) }}</th>
                        <th style="text-align: center;">{{ number_format($totalDeductionAmount, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </section>

        <footer>
            <div class="net-salary">
                <p>Net Salary (RM): {{ number_format(round($data?->details[0]['net_salary'], 2), 2) ?? 0 }}</p>

            </div>
            <div class="signature">
                <p>Employee Signature </p>
            </div>
        </footer>
    </div>
</body>

</html>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        background-color: #f9f9f9;
    }

    .salary-slip {
        width: 700px;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    header {
        text-align: center;
        border-bottom: 2px solid #ccc;
        padding-bottom: 10px;
        position: relative;
    }

    .company-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .company-info .company-details {
        flex: 1;
        text-align: center;
    }


    .logo-left {
        position: absolute;
        top: 10px;
        left: 20px;
    }

    .logo-right {
        position: absolute;
        top: 10px;
        right: 20px;
    }

    header h1 {
        font-size: 20px;
        margin: 5px 0;
    }

    header p {
        margin: 2px 0;
    }

    header .month {
        margin-top: 5px;
        font-size: 18px;
        font-weight: bold;
    }

    .employee-info {
        margin: 20px 0;
    }

    .employee-info .info {
        display: flex;
        justify-content: space-between;
    }

    .earnings-deductions table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    .earnings-deductions th,
    .earnings-deductions td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: left;
    }

    .earnings-deductions th {
        background-color: #f4f4f4;
    }

    .earnings-deductions td {
        text-align: right;
    }

    footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    footer .net-salary {
        font-size: 18px;
        font-weight: bold;
    }

    footer .signature {
        border-top: 1px solid #000;
        width: 150px;
        text-align: center;
        padding-top: 5px;
        font-style: italic;
    }

    .employee-info-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    .employee-info-table td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: left;
        /* Align text to the left */
        font-size: 14px;
        /* Adjust font size if needed */
    }

    .employee-info-table td strong {
        font-weight: bold;
    }

    .company-info img {
    width: 80px;
    height: auto;
    }
</style>
