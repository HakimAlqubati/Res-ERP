<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Payroll Transactions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        body {
            font-family: 'cairo', sans-serif;
            font-size: 14px;
            color: #333;
            background: #fff;
        }

        .wrap {
            width: 98%;
            margin: 0 auto;
            border: 2px solid #0d7c66;
            padding: 10px;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin: 0 0 5px;
            color: #0d7c66;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: normal;
            margin: 0 0 20px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #e5e5e5;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #0d7c66;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f5f9f8;
        }

        tfoot td {
            font-weight: 700;
            text-align: right;
            color: #0d7c66;
            font-size: 16px;
            background-color: #fff;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <h1>Payroll Transactions</h1>
        <h2>{{ $payroll?->employee?->name }} — {{ $payroll->year }}/{{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}</h2>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 15%">Type</th>
                    <th style="width: 15%">Sub Type</th>
                    <th style="width: 10%">Op</th>
                    <th style="width: 15%">Amount</th>
                    <th style="width: 15%">Date</th>
                    <th style="width: 25%">Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $i => $t)
                <tr
                    @if($t->type == 'employer_contribution') style="background-color: #e6ffc8ff;"
                    @endif>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->sub_type)) }}</td>
                    <td>
                        @if ($t->operation === '+')
                        <span style="color: #0d7c66; font-weight: 700;">+</span>
                        @else
                        <span style="color: #c0392b; font-weight: 700;">-</span>
                        @endif
                    </td>
                    <td>{{ formatMoneyWithCurrency($t->amount) }}</td>
                    <td>{{ $t->date?->format('Y-m-d') }}</td>
                    <td>{{ $t->description ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7" style="border-bottom: none;">
                        Total Additions: {{ $totalAdditions }}
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="border-bottom: none; border-top: none;">
                        Total Deductions: {{ $totalDeductions }}
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="border-top: none;">
                        Final Result: {{ $total }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>