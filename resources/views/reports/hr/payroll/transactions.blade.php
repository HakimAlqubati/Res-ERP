<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Transactions</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            margin: 40px;
            color: #333;
            background: #f9f9f9;
        }
        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
            color: #222;
        }
        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: normal;
            margin: 0 0 30px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) { background: #f8f8f8; }
        tr:hover { background: #f1f7f1; }
        .total {
            margin-top: 20px;
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            color: #4CAF50;
        }
        .no-print {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .no-print:hover {
            background: #45a049;
        }
        @media print {
            body { margin: 0; background: #fff; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>Payroll Transactions</h1>
    <h2>{{ $payroll?->employee?->name }} — {{ $payroll->year }}/{{ str_pad($payroll->month,2,'0',STR_PAD_LEFT) }}</h2>

    <button class="no-print" onclick="window.print()">🖨 Print Report</button>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Sub Type</th>
                <th>Operation</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($transactions as $i => $t)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ ucfirst($t->type) }}</td>
                <td>{{ ucfirst(str_replace('_',' ',$t->sub_type)) }}</td>
                <td>
                    @if($t->operation === '+')
                        <span style="color: green; font-weight: bold;">+</span>
                    @else
                        <span style="color: red; font-weight: bold;">-</span>
                    @endif
                </td>
                <td>{{ number_format($t->amount, 2) }}</td>
                <td>{{ $t->date?->format('Y-m-d') }}</td>
                <td>{{ $t->description ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">
        Final Result: {{ number_format($total, 2) }}
    </div>
</body>
</html>
