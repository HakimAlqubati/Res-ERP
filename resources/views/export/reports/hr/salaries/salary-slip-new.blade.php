<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salary Slip</title>

    <style>
        :root{
            --brand:#111827;            /* Text */
            --muted:#6b7280;            /* Subtext */
            --line:#e5e7eb;             /* Borders */
            --bg:#f8fafc;               /* Page bg */
            --card:#ffffff;             /* Card bg */
            --accent:#2563eb;           /* Accent */
            --accent-weak:#eff6ff;      /* Accent light */
            --success:#16a34a;
            --danger:#dc2626;
        }

        /* Page */
        html,body{
            background: var(--bg);
            margin:0;
            color: var(--brand);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
            -webkit-font-smoothing: antialiased;
            line-height:1.5;
        }

        .container{
            max-width: 820px; /* A4 width friendly on screen */
            margin: 24px auto;
            padding: 0 16px;
        }

        .salary-slip{
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            overflow: hidden;
            position: relative;
        }

        /* Watermark (faint logo in background) */
        .salary-slip::after{
            content:"";
            position:absolute;
            inset:0;
            background-image: url("{{ url('/') . '/storage/logo/default.png' }}");
            background-repeat:no-repeat;
            background-position: 95% 85%;
            background-size: 140px;
            opacity:.04;
            pointer-events:none;
        }

        /* Header */
        header{
            position: relative;
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #fff 0%, #fcfdff 100%);
        }

        .company-bar{
            display:flex;
            align-items:center;
            gap:16px;
        }

        .logo{
            width:72px; height:72px; object-fit:contain;
            border-radius: 10px;
            background: #fff;
            border: 1px solid var(--line);
            padding:6px;
        }

        .company-meta{
            flex:1;
        }
        .company-name{
            font-size: 20px;
            font-weight: 800;
            margin:0;
            letter-spacing:.3px;
        }
        .company-meta p{
            margin: 2px 0;
            color: var(--muted);
            font-size: 12px;
        }

        .slip-title{
            margin: 14px 0 0;
            display:flex;
            align-items: baseline;
            gap:12px;
        }
        .slip-title h2{
            margin:0;
            font-size: 22px;
            letter-spacing:.6px;
        }
        .month-badge{
            color: var(--accent);
            background: var(--accent-weak);
            border:1px solid #dbeafe;
            padding:4px 10px;
            font-size:12px;
            border-radius:999px;
            font-weight:700;
        }

        /* Employee section */
        .section{
            padding: 18px 24px;
        }

        .card{
            border:1px solid var(--line);
            border-radius: 12px;
            overflow:hidden;
            background:#fff;
        }
        .card header{
            padding:10px 14px;
            border-bottom:1px solid var(--line);
            background: #fafafa;
        }
        .card header h3{
            margin:0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
        }

        .table{
            width:100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table th, .table td{
            border-bottom: 1px solid var(--line);
            padding: 10px 12px;
        }
        .table th{
            text-align:left;
            font-weight:700;
            background:#fbfdff;
            border-bottom:1px solid #eaf1ff;
        }
        .table tbody tr:nth-child(even){
            background:#fcfcfd;
        }

        .employee-info-table td{
            width:25%;
            vertical-align: top;
        }
        .employee-info-table strong{
            color:#111827;
        }

        /* Earnings & Deductions layout */
        .grid{
            display:grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        @media (min-width: 700px){
            .grid{
                grid-template-columns: 1.2fr .8fr;
            }
        }

        /* Totals row */
        .totals-row th{
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .08em;
            background: #f2f6ff !important;
            border-top: 2px solid #dbeafe;
            border-bottom: 2px solid #dbeafe;
        }

        .right{
            text-align:right;
        }
        .left{
            text-align:left;
        }
        .mono{
            font-variant-numeric: tabular-nums;
        }

        /* Footer / Net */
        .footer{
            padding: 10px 24px 24px;
        }

        .net-card{
            display:flex; gap:16px; flex-wrap:wrap;
        }

        .net{
            flex:1 1 320px;
            border: 1.5px solid #bbf7d0;
            background: #f0fdf4;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .net label{
            display:block; font-size: 12px; color:#065f46; text-transform: uppercase; letter-spacing:.12em;
            margin-bottom: 6px;
        }
        .net .value{
            font-size: 24px; font-weight: 800; color:#065f46;
            font-variant-numeric: tabular-nums;
        }

        .signature{
            flex:1 1 220px;
            border:1px dashed var(--line);
            border-radius: 12px;
            background:#fff;
            display:flex; align-items:center; justify-content:center;
            min-height: 84px;
        }
        .signature p{
            margin:0; color:var(--muted);
            font-style: italic;
        }

        /* Print styles (A4) */
        @page{
            size: A4;
            margin: 16mm;
        }
        @media print{
            html, body{ background:#fff; }
            .container{ max-width: 100%; margin:0; padding:0; }
            .salary-slip{
                box-shadow: none; border:1px solid #ddd; border-radius:0;
            }
            .salary-slip::after{ opacity:.06; }
            .no-print{ display:none !important; }
            a[href]:after{ content:""; }
        }

        /* Helper badges for amounts */
        .badge{
            display:inline-block;
            padding: 3px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:700;
            border:1px solid;
        }
        .badge-earn{ color:var(--success); border-color:#bbf7d0; background:#f0fdf4; }
        .badge-ded{ color:var(--danger); border-color:#fecaca; background:#fef2f2; }
    </style>
</head>

<body>
<div class="container">
    <div class="salary-slip">

        <!-- HEADER -->
        <header>
            <div class="company-bar">
                <img class="logo" src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo">
                <div class="company-meta">
                    <h1 class="company-name">{{ setting('company_name') }}</h1>
                    <p>Office: {{ setting('address') }}</p>
                    <p>{{ setting('company_phone') }}</p>
                </div>
            </div>

            <div class="slip-title">
                <h2>SALARY SLIP</h2>
                <span class="month-badge">{{ $monthName }}</span>
            </div>
        </header>

        <!-- EMPLOYEE INFO -->
        <section class="section">
            <div class="card">
                <header><h3>Employee Information</h3></header>
                <table class="table employee-info-table">
                    <tbody>
                    <tr>
                        <td><strong>Name:</strong><br>{{ $employee?->name }}</td>
                        <td><strong>Job:</strong><br>{{ $employee?->job_title }}</td>
                        <td><strong>ID No:</strong><br>{{ $employee?->employee_no }}</td>
                        <td><strong>Branch:</strong><br>{{ $branch?->name }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- EARNINGS & DEDUCTIONS -->
        <section class="section">
            <div class="grid">
                <!-- Left: Earnings -->
                <div class="card">
                    <header><h3>Earnings ({{ setting('currency', 'RM') }})</h3></header>
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="left">Description</th>
                            <th class="left">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="left">Basic salary</td>
                            <td class="left mono">
                                {{ number_format((float) $employee->salary, 2) }}
                                <span class="badge badge-earn">+ </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="left">Overtime pay</td>
                            <td class="left mono">
                                {{ number_format((float) $data->details[0]['overtime_pay'], 2) }}
                                <span class="badge badge-earn">+ </span>
                            </td>
                        </tr>
                        @foreach ($employeeAllowances as $allowance)
                            <tr>
                                <td class="left">{{ $allowance['allowance_name'] }}</td>
                                <td class="left mono">
                                    {{ number_format((float) $allowance['amount'], 2) }}
                                    <span class="badge badge-earn">+ </span>
                                </td>
                            </tr>
                        @endforeach

                        @if (($data->details[0]['total_incentives'] ?? 0) > 0)
                            <tr>
                                <td class="left">Bonus</td>
                                <td class="left mono">
                                    {{ number_format((float) $data->details[0]['total_incentives'], 2) }}
                                    <span class="badge badge-earn">+ </span>
                                </td>
                            </tr>
                        @endif

                        <tr class="totals-row">
                            <th class="left">Total Earnings</th>
                            <th class="left mono">{{ number_format((float) $totalAllowanceAmount, 2) }}</th>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right: Deductions -->
                <div class="card">
                    <header><h3>Deductions ({{ setting('currency', 'RM') }})</h3></header>
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="left">Description</th>
                            <th class="left">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($employeeDeductions as $deduction)
                            <tr>
                                <td class="left">{{ $deduction['deduction_name'] }}</td>
                                <td class="left mono">
                                    {{ number_format((float) $deduction['deduction_amount'], 2) }}
                                    <span class="badge badge-ded">−</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="left" colspan="2" style="color:var(--muted)">No deductions</td>
                            </tr>
                        @endforelse

                        <tr class="totals-row">
                            <th class="left">Total Deductions</th>
                            <th class="left mono">{{ number_format((float) $totalDeductionAmount, 2) }}</th>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- NET & SIGN -->
        <section class="footer">
            <div class="net-card">
                <div class="net">
                    <label>Net Salary ({{ setting('currency', 'RM') }})</label>
                    <div class="value mono">{{ number_format((float) $data->details[0]['net_salary'], 2) }}</div>
                </div>
                <div class="signature">
                    <p>Employee Signature</p>
                </div>
            </div>
        </section>
    </div>

    <div class="no-print" style="text-align:center; margin: 14px 0 6px;">
        <a href="javascript:window.print()" style="text-decoration:none; font-weight:700; color:#111; border:1px solid #ddd; padding:10px 14px; border-radius:10px; background:#fff;">
            Print
        </a>
    </div>
</div>
</body>
</html>
