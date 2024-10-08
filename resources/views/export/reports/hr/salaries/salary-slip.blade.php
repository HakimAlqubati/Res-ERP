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
                <img src="{{ url('/') . '/storage/logo/default.png' }}" alt="Company Logo">
                <h1>AL ROMANSIAH SDN BHD</h1>
                {{-- <p>Office: No 64 Jalan Damai, 55000 Kuala Lumpur</p> --}}
                {{-- <p>03 – 2181 7161</p> --}}
            </div>
            <h2>SALARY SLIP</h2>
            <p class="month">{{ $monthSalary?->month }}</p>
        </header>

        <section class="employee-info">
            <div class="info">
                <span>Name:</span> {{ $employee?->name }}
            </div>
            <div class="info">
                <span>ID No:</span> {{ $employee?->employee_no }}
            </div>
            <div class="info">
                <span>Job:</span> {{ $employee?->job_title }}
            </div>
            <div class="info">
                <span>Branch:</span> {{ $branch?->name }}
            </div>
        </section>

        <section class="earnings-deductions">
            @php
                // Calculate total earnings
                $basicSalary = $data?->basic_salary ?? 0;
                $totalAllowances = $increaseDetails->whereIn('type_id', array_keys($allowanceTypes))->sum('amount');
                $totalEarnings = $basicSalary + $totalAllowances;

                // Calculate total deductions
                $totalDeductions = $deducationDetail
                    ->whereIn('deduction_id', array_keys($allDeductionTypes))
                    ->sum('deduction_amount');
            @endphp

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
                        <td>Basic Salary</td>
                        <td>{{ number_format($basicSalary, 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach ($allowanceTypes as $detailId => $detailName)
                        <tr>
                            <td>{{ $detailName }}</td>
                            <td>{{ number_format(optional($increaseDetails->firstWhere('type_id', $detailId))->amount ?? 0, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                    @foreach ($allDeductionTypes as $deducationId => $deducationName)
                        <tr>
                            <td>{{ $deducationName }}</td>
                            <td></td>
                            <td>{{ number_format(optional($deducationDetail->firstWhere('deduction_id', $deducationId))->deduction_amount ?? 0, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>Total</th>
                        <th>{{ number_format($totalEarnings, 2) }}</th>
                        <th>{{ number_format($totalDeductions, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </section>

        <footer>
            <div class="net-salary">
                <p>Net Salary (RM): {{ $data?->net_salary }}</p>
            </div>
            <div class="signature">
                <p>Employee Signature:</p>
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
    }

    header .company-info img {
        width: 100px;
        height: auto;
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
</style>
