<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Salary Slip</title>
    <link rel="icon" type="image/png" href="https://nltworkbench.com/storage/logo/default.png">
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        :root {
            --ink: #222;
            --muted: #666;
            --line: #e6e6e6;
            --line-print: #bdbdbd;
            --bg: #f6f7fb;
            --card: #ffffff;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.45;
            color: var(--ink);
            background: var(--bg);
        }

        .wrap {
            max-width: 820px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid var(--line);
        }

        .doc {
            padding: 22px;
        }

        /* Header (like the screenshot) */
        .head {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 10px;
        }

        .logoBox {
            font-size: 12px;
            color: #777;
        }

        .center {
            text-align: center;
        }

        .center .comp {
            font-weight: 800;
            letter-spacing: .3px;
        }

        .center .addr {
            margin-top: 3px;
            color: #555;
            font-size: 12px;
            line-height: 1.3;
        }

        .title {
            text-align: center;
            font-weight: 800;
            margin: 14px 0 4px;
            font-size: 18px;
        }

        .month {
            text-align: center;
            font-size: 12px;
            color: #444;
            margin: 0 0 14px;
        }

        /* Info table (small grid like screenshot) */
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background: #fff;
        }

        .info td {
            border: 1px solid var(--line);
            padding: 8px 10px;
        }

        .info td.label {
            width: 130px;
            background: #fafafa;
            color: #333;
            font-weight: 600;
        }

        /* Main table (Description | Earnings | Deductions) */
        table.pay {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 8px;
        }

        table.pay th,
        table.pay td {
            border: 1px solid var(--line);
            padding: 8px 10px;
            text-align: left;
        }

        table.pay thead th {
            background: #fafafa;
            text-align: left;
            font-weight: 700;
        }

        table.pay tfoot td {
            font-weight: 800;
        }

        .right {
            text-align: right
        }

        .muted {
            color: #777
        }

        /* Footer bits */
        .note {
            margin-top: 10px;
            font-size: 13px;
        }

        .sign {
            margin-top: 26px;
            font-size: 12.5px;
            color: #444
        }

        /* Print */
        @media print {
            @page {
                size: A4;
                margin: 12mm 10mm;
            }

            html,
            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .wrap {
                margin: 0;
                border: 0;
            }

            .head {
                border-bottom: 1px solid var(--line-print);
            }

            table.pay th,
            table.pay td,
            .info td {
                border-color: var(--line-print) !important;
            }
        }

        @media print {
            button {
                display: none !important;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</head>

<body>
    <div class="wrap">
        <div class="doc">

            <!-- Header strip -->
            <div class="head">
                <div class="logoBox">
                    <img style="display: inline-block;width:80px;" src="{{ asset('/storage/logo/default.png') }}">
                </div>
                <div class="center">
                    <div class="comp">{{ settingWithDefault('company_name', 'Company Name') }}</div>
                    <div class="addr">
                        {{ settingWithDefault('address', 'Company Address') }}
                    </div>
                </div>
                <div class="logoBox right">
                    <img style="display: inline-block;width:80px;"
                        src="{{ asset('/storage/' . setting('company_logo') . '') }}">
                </div>
            </div>

            <!-- ✅ زر الطباعة -->
            <div style="text-align:right; margin-top:12px;">
                <button onclick="downloadPDF()"
                    style="
        background:#0d7c66;
        color:#fff;
        border:none;
        padding:8px 16px;
        border-radius:6px;
        cursor:pointer;
        font-weight:600;
        font-size:13px;
    ">
                    ⬇️ Download PDF
                </button>
            </div>


            <h2 class="title">SALARY SLIP</h2>
            <p class="month">
                {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->translatedFormat('F Y') }}
            </p>

            <!-- Employee small table -->
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

            <!-- Description | Earnings | Deductions table -->
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
                    // $rows = max($earn->count(), $ded->count());
                    $rows = $transactions->count();
                    @endphp

                    @for ($i = 0; $i < $rows; $i++)
                        @php
                        $e=$earn->get($i);
                        $d = $ded->get($i);
                        $eDesc =
                        $e?->description ?:
                        (isset($e)
                        ? ucfirst(str_replace('_', ' ', $e->sub_type ?? ($e->type ?? '')))
                        : '');
                        $dDesc =
                        $d?->description ?:
                        (isset($d)
                        ? ucfirst(str_replace('_', ' ', $d->sub_type ?? ($d->type ?? '')))
                        : '');
                        @endphp
                        <tr>
                            <td>
                                @if ($e && $d)
                                {{ $eDesc }}
                                @elseif($e)
                                {{ $eDesc }}
                                @elseif($d)
                                {{ $dDesc }}
                                @else
                                &nbsp;
                                @endif
                            </td>
                            <td class="right">
                                @if ($e)
                                {{ formatMoneyWithCurrency($e->amount) }}
                                @endif
                            </td>
                            <td class="right">
                                @if ($d)
                                {{ formatMoneyWithCurrency($d->amount) }}
                                @endif
                            </td>
                        </tr>
                        @endfor
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="right"><strong>{{ formatMoneyWithCurrency($gross) }}</strong></td>
                        <td class="right"><strong>{{ formatMoneyWithCurrency($totalDeductions) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <p class="note"><strong>Net Salary:</strong> {{ formatMoneyWithCurrency($net) }}</p>

            <div class="sign">Employee Signature</div>

        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.querySelector('.wrap');
            const opt = {
                margin: 0.5,
                filename: 'salary-slip.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>

</html>