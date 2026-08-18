<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Salary Slip</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        @page {
            margin: 10mm;
            size: auto; /* Or specific size like 210mm 1000mm if needed */
        }

        body {
            font-family: 'cairo', sans-serif;
            font-size: 13px;
            line-height: 1.2;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .wrap {
            width: 100%;
            margin: 0 auto;
            /* border: 1px solid #e6e6e6; */
        }

        .doc {
            padding: 10px;
        }

        /* Header converted to Table for mPDF */
        .head-table {
            width: 100%;
            border-bottom: 1px solid #e6e6e6;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }

        .head-table td {
            vertical-align: middle;
        }

        .logoBox {
            width: 20%;
            text-align: left;
            font-size: 12px;
            color: #777;
        }

        .center-box {
            width: 60%;
            text-align: center;
        }

        .comp {
            font-weight: 800;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .addr {
            color: #555;
            font-size: 12px;
        }

        .logoBox-right {
            width: 20%;
            text-align: right;
        }

        .title {
            text-align: center;
            font-weight: 800;
            margin: 20px 0 5px;
            font-size: 18px;
        }

        .month {
            text-align: center;
            font-size: 12px;
            color: #444;
            margin-bottom: 10px;
        }

        /* Info table */
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info td {
            border: 1px solid #e6e6e6;
            padding: 8px 10px;
        }

        .info td.label {
            width: 130px;
            background-color: #fafafa;
            color: #333;
            font-weight: 600;
        }

        /* Payment table */
        table.pay {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.pay th,
        table.pay td {
            border: 1px solid #e6e6e6;
            padding: 8px 10px;
            text-align: left;
        }

        /* RTL adjustments if needed, but 'text-align: left' in RTL means left. Usually for numbers we want left (LTR) or right (RTL). Arabic usually right. */
        table.pay th {
            text-align: left;
            background-color: #fafafa;
            font-weight: 700;
        }

        table.pay td {
            text-align: left;
        }

        .right {
            text-align: left;
            /* In RTL, 'right' class usually aims for the opposite side or number alignment. Let's force left for numbers if English, Right if Arabic. Assuming Arabic interface mainly */
            text-align: left;
        }

        /* Adjust for RTL specifics if necessary. If formatting money in English, keep LTR? */
        /* Let's stick to base alignment. */

        .note {
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="doc">

            <!-- Header Table -->
            <table class="head-table">
                <tr>
                    <td class="logoBox">
                        <img style="width:80px;" src="{{ public_path('storage/logo/default.png') }}">
                    </td>
                    <td class="center-box">
                        <div class="comp">{{ settingWithDefault('company_name', 'Company Name') }}</div>
                        <div class="addr">
                            {{ settingWithDefault('address', 'Company Address') }}
                        </div>
                    </td>
                    <td class="logoBox-right">
                        @if(setting('company_logo'))
                        <!-- Use public_path for mPDF images -->
                        <img style="width:80px;" src="{{ public_path('storage/' . setting('company_logo')) }}">
                        @endif
                    </td>
                </tr>
            </table>

            <table style="width: 100%; margin-top: 10px; margin-bottom: 20px;">
                <tr>
                    <td style="width: 30%;">

                        <table align="right" style="border: 2px solid #0d7c66;">
                            <tr>
                                <td style="padding: 10px 15px; font-weight: 900; font-size: 16px; background-color: #fafafa;">
                                    Net Salary: {{ formatMoneyWithCurrency($net) }}
                                </td>
                            </tr>
                        </table>

                    </td>
                    <td style="width: 40%; text-align: center; vertical-align: middle;">
                        <h2 class="title" style="margin: 0 0 5px 0;">SALARY SLIP</h2>
                        <p class="month" style="margin: 0;">
                            {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->translatedFormat('F Y') }}
                        </p>
                    </td>
                    <td style="width: 30%; text-align: right; vertical-align: middle;">

                    </td>
                </tr>
            </table>


            <!-- Employee Info -->
            <table class="info">
                <tr>
                    <td class="label"><strong>Name:</strong></td>
                    <td>{{ $payroll->employee?->name ?? '-' }}</td>
                    <td class="label"><strong>ID No:</strong></td>
                    <td>{{ $payroll->employee?->employee_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label"><strong>Job:</strong></td>
                    <td>{{ $payroll->employee?->job_title ?? '-' }}</td>
                    <td class="label"><strong>Branch:</strong></td>
                    <td>{{ $payroll->period_branch_name ?? '-' }}</td>
                </tr>
            </table>

            <!-- Earnings Table -->
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; font-size: 13px; page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th style="border-bottom: 2px solid #aebac1; text-align: left; padding: 8px 5px; color: #0d7c66; font-weight: bold; width: 70%;">Employee Earnings / Reimbursements</th>
                        <th style="border-bottom: 2px solid #aebac1; text-align: right; padding: 8px 5px; color: #333; font-weight: bold; width: 30%;">Current</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px 5px; color: #999; border-bottom: 1px solid #f9f9f9;">Base Salary (Contracted)</td>
                        <td style="padding: 8px 5px; text-align: right; color: #999; border-bottom: 1px solid #f9f9f9;">{{ formatMoneyWithCurrency($payroll->employee?->salary ?? 0) }}</td>
                    </tr>
                    @foreach (($earnings ?? collect()) as $e)
                    @php
                    $eDesc = $e->description ?: ucfirst(str_replace('_', ' ', $e->sub_type ?? ($e->type ?? '')));
                    @endphp
                    <tr>
                        <td style="padding: 8px 5px; color: #333; border-bottom: 1px solid #f9f9f9;">{{ $eDesc }}</td>
                        <td style="padding: 8px 5px; text-align: right; color: #333; border-bottom: 1px solid #f9f9f9;">{{ formatMoneyWithCurrency($e->amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding: 12px 5px 8px 5px; font-weight: 800; color: #222;">Gross Pay</td>
                        <td style="padding: 12px 5px 8px 5px; text-align: right; font-weight: 800; color: #0d7c66;">{{ formatMoneyWithCurrency($gross) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Deductions Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 13px; page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th style="border-bottom: 2px solid #aebac1; text-align: left; padding: 8px 5px; color: red; font-weight: bold; width: 70%;">Employee Deductions</th>
                        <th style="border-bottom: 2px solid #aebac1; text-align: right; padding: 8px 5px; color: #333; font-weight: bold; width: 30%;">Current</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($deductionRows ?? collect()) as $row)
                    @if(!($row->isEmployer ?? false))
                    <tr @if($row->bgColor) style="background-color: {{ $row->bgColor }};" @endif>
                        <td style="padding: 8px 5px; color: #333; border-bottom: 1px solid #f9f9f9;">{{ $row->description }}</td>
                        <td style="padding: 8px 5px; text-align: right; color: #333; border-bottom: 1px solid #f9f9f9;">{{ formatMoneyWithCurrency($row->amount) }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding: 12px 5px 8px 5px; font-weight: 800; color: #222;">Total Deductions</td>
                        <td style="padding: 12px 5px 8px 5px; text-align: right; font-weight: 800; color: red;">{{ formatMoneyWithCurrency($totalDeductions) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Employer Contributions Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 13px; page-break-inside: avoid;">
                <thead>
                    <tr>
                        <th style="border-bottom: 2px solid #aebac1; text-align: left; padding: 8px 5px; color: #0d7c66; font-weight: bold; width: 70%;">Company Contributions</th>
                        <th style="border-bottom: 2px solid #aebac1; text-align: right; padding: 8px 5px; color: #333; font-weight: bold; width: 30%;">Current</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($employerContrib ?? collect()) as $ec)
                    @php
                    $ecDesc = $ec->description ?: ucfirst(str_replace('_', ' ', $ec->sub_type ?? ($ec->type ?? '')));
                    @endphp
                    <tr>
                        <td style="padding: 8px 5px; color: #333; border-bottom: 1px solid #f9f9f9;">{{ $ecDesc }}</td>
                        <td style="padding: 8px 5px; text-align: right; color: #333; border-bottom: 1px solid #f9f9f9;">{{ formatMoneyWithCurrency($ec->amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding: 12px 5px 8px 5px; font-weight: 800; color: #222;">Total Contributions</td>
                        <td style="padding: 12px 5px 8px 5px; text-align: right; font-weight: 800; color: #0d7c66;">{{ formatMoneyWithCurrency($totalEmployer) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #444;">
                <p style="margin-bottom: 20px;">This payslip is computer generated. No signature is required.</p>
                <p>Printed on: <strong>{{ now()->format('d/m/Y') }}</strong></p>
            </div>
        </div>
    </div>
</body>

</html>