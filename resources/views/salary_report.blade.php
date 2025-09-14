<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Report - {{ $employee->name }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo:700,400&display=swap">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f7f9fa; margin: 0; padding: 0; }
        .container { max-width: 750px; margin: 45px auto; background: #fff; border-radius: 20px; box-shadow: 0 6px 28px #0001; padding: 36px 34px 40px 34px; }
        h2 { color: #222a; text-align: center; font-weight: 700; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px;}
        th, td { padding: 12px 10px; text-align: center; }
        th { background: #e8eaf6; color: #384054; font-weight: bold; }
        tr:nth-child(even) { background: #f6f7fb; }
        .amount-pos { color: #21b389; font-weight: 700; }
        .amount-neg { color: #e04b6e; font-weight: 700; }
        .desc { color: #596080; font-size: 0.97em; }
        .total-bar { background: linear-gradient(90deg,#31c77f,#0cc2f1); color: #fff; font-size: 1.4em; font-weight: bold; text-align: center; border-radius: 18px; margin: 38px 0 0 0; padding: 18px 0 12px 0; box-shadow: 0 2px 12px #14e6e650; letter-spacing: 1px;}
        .tx-type { border-radius: 10px; font-size: 0.95em; padding: 2px 11px; display: inline-block;}
        .tx-type.salary, .tx-type.bonus, .tx-type.allowance { background: #e9f9f0; color: #27a385;}
        .tx-type.deduction, .tx-type.advance, .tx-type.penalty { background: #fde8ef; color: #e05a7d;}
        .tx-type.other { background: #eee; color: #888;}
        @media (max-width:600px) {
            .container {padding: 14px 4px;}
            table, th, td {font-size: 0.9em;}
        }
    </style>
</head>
<body>
<div class="container">
    <h2>تقرير مستحقات الموظف<br>{{ $employee->name }}</h2>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>التاريخ</th>
            {{-- <th>نوع الحركة</th> --}}
            <th>الشهر</th>
            <th>الوصف</th>
            <th>المبلغ</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transactions as $tx)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ \Carbon\Carbon::parse($tx->date)->format('Y-m-d') }}</td>
                {{-- <td>
                    <span class="tx-type {{ $tx->type }}">
                        {{ __("transactions.types.$tx->type") ?? ucfirst($tx->type) }}
                    </span>
                </td> --}}
                <td>{{ $tx->month }}/{{ $tx->year }}</td>
                <td class="desc">{{ $tx->description }}</td>
                <td>
                    @if($tx->operation === '+')
                        <span class="amount-pos">+{{ number_format($tx->amount) }}</span>
                    @else
                        <span class="amount-neg">-{{ number_format($tx->amount) }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if($transactions->isEmpty())
            <tr>
                <td colspan="6" style="color: #888; font-size:1.1em;">لا توجد حركات مالية لهذا الموظف.</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="total-bar">
        إجمالي صافي المستحقات:&nbsp;
        <span style="font-size: 1.45em; font-family:monospace;">
            {{ number_format($total, 2) }}
        </span>
        <span style="font-size:0.95em; font-weight:normal;">{{ $transactions->first()->currency ?? 'YER' }}</span>
    </div>
</div>
</body>
</html>
