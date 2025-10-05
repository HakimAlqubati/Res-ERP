<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Payroll Transactions</title>
    <link rel="icon" type="image/png" href="https://nltworkbench.com/storage/logo/default.png" />

    <style>
        :root {
            --brand: #0d7c66;
            --brand-dark: #0b6e5b;
            --text: #333;
            --muted: #666;
            --border: #e5e5e5;
            --border-print: #bdbdbd;
        }

        html, body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: var(--text);
            background: #f9f9f9;
        }

        /* شاشة */
        .wrap {
            max-width: 980px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
            font-size: 26px;
            margin: 0 0 6px;
            color: var(--brand);
            letter-spacing: .3px;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: normal;
            margin: 0 0 24px;
            color: var(--muted);
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-block;
            padding: 8px 18px;
            background: var(--brand);
            color: #fff;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,.08);
        }
        .btn:hover { background: var(--brand-dark); }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid var(--border);
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }

        thead th {
            background: var(--brand);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        tbody tr:nth-child(even) { background: #f5f9f8; }
        tbody tr:hover { background: #eaf5f2; transition: background .2s; }

        tfoot td {
            font-weight: 700;
            text-align: right;
            color: var(--brand);
            font-size: 16px;
        }

        /* طباعة */
        @media print {
            @page {
                size: A4;
                margin: 12mm 10mm;
            }

            html, body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .wrap {
                margin: 0;
                max-width: none;
            }

            .toolbar { display: none !important; }
            table { box-shadow: none !important; }

            /* حدود أوضح للطباعة */
            th, td { border-color: var(--border-print) !important; }

            /* تثبيت ترويسة الجدول بكل صفحة */
            thead { display: table-header-group; }
            tfoot { display: table-row-group; }
            tr { page-break-inside: avoid; }

            /* إزالة لاحقة روابط URLs */
            a[href]:after { content: ""; }
        }
    </style>
</head>

<body>
<div class="wrap">
    <h1>Payroll Transactions</h1>
    <h2>{{ $payroll?->employee?->name }} — {{ $payroll->year }}/{{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}</h2>

    <div class="toolbar">
        <button class="btn" onclick="window.print()">🖨 Print Report</button>
    </div>

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
                <td>{{ ucfirst(str_replace('_', ' ', $t->sub_type)) }}</td>
                <td>
                    @if ($t->operation === '+')
                        <span style="color: var(--brand); font-weight: 700;">+</span>
                    @else
                        <span style="color: #c0392b; font-weight: 700;">-</span>
                    @endif
                </td>
                <td>{{ number_format($t->amount, 2) }}</td>
                <td>{{ $t->date?->format('Y-m-d') }}</td>
                <td>{{ $t->description ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="7">
                    Final Result: {{ $total }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
