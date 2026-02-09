<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Salary Slip</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        body {
            font-family: 'cairo', sans-serif;
            font-size: 14px;
            line-height: 1.45;
            color: #222;
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
            margin-bottom: 20px;
            padding-bottom: 10px;
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
            margin-bottom: 20px;
        }

        /* Info table */
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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

        .sign {
            margin-top: 40px;
            font-size: 12.5px;
            color: #444;
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

            <h2 class="title">SALARY SLIP</h2>
            <p class="month">
                {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->translatedFormat('F Y') }}
            </p>

            <!-- Employee Info -->
            <table class="info">
                <tr>
                    <td class="label">Name:</td>
                    <td>{{ $payroll->employee?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">ID No:</td>
                    <td>{{ $payroll->employee?->employee_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Job:</td>
                    <td>{{ $payroll->employee?->job_title ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch:</td>
                    <td>{{ $payroll->employee?->branch?->name ?? '-' }}</td>
                </tr>
            </table>

            <!-- Pay Table -->
            <table class="pay">
                <thead>
                    <tr>
                        <th style="width:50%">Description</th>
                        <th style="width:25%">Earnings</th>
                        <th style="width:25%">Deductions</th>
                    </tr>
                </thead>
                <tbody>

                    @php
                    $earn = $earnings ?? collect();
                    $ded = $deductions ?? collect();
                    $rows = $transactions->count();
                    @endphp

                    @for ($i = 0; $i < $rows; $i++)
                        @php
                        $e=$earn->get($i);
                        $d = $ded->get($i);
                        $eDesc = $e?->description ?: (isset($e) ? ucfirst(str_replace('_', ' ', $e->sub_type ?? ($e->type ?? ''))) : '');
                        $dDesc = $d?->description ?: (isset($d) ? ucfirst(str_replace('_', ' ', $d->sub_type ?? ($d->type ?? ''))) : '');
                        @endphp
                        <tr>
                            <td>
                                @if ($e && $d)
                                {{ $eDesc }} / {{ $dDesc }}
                                @elseif($e)
                                {{ $eDesc }}
                                @elseif($d)
                                {{ $dDesc }}
                                @else
                                &nbsp;
                                @endif
                            </td>
                            <td>
                                @if ($e)
                                {{ formatMoneyWithCurrency($e->amount) }}
                                @endif
                            </td>
                            <td>
                                @if ($d)
                                {{ formatMoneyWithCurrency($d->amount) }}
                                @endif
                            </td>
                        </tr>
                        @endfor
                        @foreach ($employerContrib as $employerContribution)
                        <tr style="background-color: #e6ffc8ff;">
                            <td>{{ $employerContribution->description }}</td>
                            <td></td>
                            <td>{{ formatMoneyWithCurrency($employerContribution->amount) }}</td>
                        </tr>
                        @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong>{{ formatMoneyWithCurrency($gross) }}</strong></td>
                        <td><strong>{{ formatMoneyWithCurrency($totalDeductions) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <p class="note"><strong>Net Salary:</strong> {{ formatMoneyWithCurrency($net) }}</p>

            <div class="sign">Employee Signature</div>

        </div>
    </div>
</body>

</html>