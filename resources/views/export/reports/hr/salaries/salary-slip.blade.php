<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip</title>
</head>

<body>
    <div class="salary-slip">
        <header>
            <div class="company-info">
                {{-- <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                    style="width: 80px; height: auto;" class="logo-left"> --}}
                <div class="company-details">
                    <h1>{{ setting('company_name') }}</h1>
                    <p>Office: {{ setting('address') }}</p>
                    <p>{{ setting('company_phone') }}</p>
                </div>
            </div>

            <h2>SALARY SLIP</h2>
            <p class="month">{{ $monthName }}</p>
        </header>

        {{-- {{ dd($employee?->employee_no,$branch?->name) }} --}}
        <section class="employee-info">
            <table class="employee-info-table">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>{{ $employee['name'] ?? '' }}</td>
                    <td><strong>Job:</strong></td>
                    <td>{{ $employee['job_title'] ?? '' }}</td>
                </tr>
                <tr>
                    <td><strong>ID No:</strong></td>
                    <td>{{ $employee['employee_no'] ?? '' }}</td>
                    <td><strong>Branch:</strong></td>
                    {{-- <td>{{ $branch?->name }}</td> --}}
                </tr>
            </table>
        </section>

        {{-- <section class="earnings-deductions">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Earnings ({{ setting('currency', 'RM') }})</th>
                        <th>Deductions ({{ setting('currency', 'RM') }})</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left">Basic salary</td>
                        <td style="text-align: left">{{ number_format($employee->salary, 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="text-align: left">Overtime pay</td>
                        <td style="text-align: left">{{ number_format($data->details[0]['overtime_pay'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach ($employeeAllowances as $allowance)
                        <tr>
                            <td style="text-align: left">{{ $allowance['allowance_name'] }}</td>
                            <td style="text-align: left">{{ number_format($allowance['amount'], 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    @if ($data->details[0]['total_incentives'] > 0)
                        <tr>
                            <td style="text-align: left">Bonus</td>
                            <td style="text-align: left">{{ number_format($data->details[0]['total_incentives'], 2) }}</td>
                            <td></td>
                        </tr>
                    @endif
                    @foreach ($employeeDeductions as $deduction)
                        <tr>
                            <td style="text-align: left;">{{ $deduction['deduction_name'] }}</td>
                            <td></td>
                            <td style="text-align: left">{{ number_format($deduction['deduction_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>Total</th>
                        <th style="text-align: left;">{{ number_format($totalAllowanceAmount, 2) }}</th>
                        <th style="text-align: left;">{{ number_format($totalDeductionAmount, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </section> --}}

        {{-- <footer>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%">
                        <div class="net-salary">
                            <p>Net Salary ({{ setting('currency', 'RM') }}): {{ number_format($data->details[0]['net_salary'], 2) }}</p>
                        </div>
                    </td>
                    <td>
                        <div class="signature">
                            <p>Employee Signature </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                        <img style="top: 12px;width: 51px;" src="{{ url('/') . '/storage/logo/default.png' }}"
                            alt="Company Logo" class="logo-right">
                    </td>
                </tr>
            </table>
        </footer> --}}
    </div>
</body>

</html>

<style>
    /* ---------- Theme tokens ---------- */
    :root {
        --bg: #f8fafc;
        --card: #ffffff;
        --text: #111827;
        --muted: #6b7280;
        --line: #e5e7eb;
        --line-strong: #d1d5db;
        --head: #0f172a;
        --accent: #2563eb;
        --accent-weak: #eff6ff;
        --success: #065f46;
        --danger: #b91c1c;
    }

    /* ---------- Page base ---------- */
    html,
    body {
        background-color: var(--bg);
        color: var(--text);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        margin: 0;
        padding: 0;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans";
        line-height: 1.5;
    }

    /* Centering container kept as-is */
    body {
        display: flex;
        justify-content: center;
        padding: 24px 12px;
    }

    /* ---------- Card ---------- */
    .salary-slip {
        width: 700px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 16px;
        /* تحسين الحواف */
        box-shadow: 0 10px 30px rgba(2, 6, 23, .06);
        /* ظل أنعم */
        overflow: hidden;
        position: relative;
    }

    /* علامة مائية خفيفة (لا تغيّر الهيكل) */
    .salary-slip::after {
        content: "";
        position: absolute;
        inset: 0;
        background-image: url("{{ url('/') . '/storage/logo/default.png' }}");
        background-repeat: no-repeat;
        background-position: 96% 88%;
        background-size: 120px;
        opacity: .04;
        pointer-events: none;
    }

    /* ---------- Header ---------- */
    header {
        text-align: center;
        border-bottom: 1px solid var(--line);
        position: relative;
        padding: 18px 18px 10px;
        /* مسافات متوازنة */
        background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
        /* تدرّج رقيق */
    }

    .company-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .company-info .company-details {
        flex: 1;
        text-align: center;
    }

    .company-info img {
        width: 80px;
        height: auto;
        border: 1px solid var(--line);
        /* إطار خفيف للشعار */
        border-radius: 10px;
        background: #fff;
        padding: 4px;
    }

    .logo-left {
        position: absolute;
        top: 12px;
        left: 18px;
    }

    .logo-right {
        position: absolute;
        top: 10px;
        right: 20px;
    }

    header h1 {
        font-size: 20px;
        margin: 4px 0;
        color: var(--head);
        letter-spacing: .2px;
        font-weight: 800;
    }

    header p {
        margin: 2px 0;
        color: var(--muted);
        font-size: 12px;
    }

    header .month {
        margin-top: 6px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        color: var(--accent);
        background: var(--accent-weak);
        border: 1px solid #dbeafe;
        padding: 3px 10px;
        border-radius: 999px;
        letter-spacing: .4px;
    }

    h2 {
        margin: 8px 0 0;
        font-size: 22px;
        letter-spacing: .4px;
    }

    /* ---------- Sections ---------- */
    .employee-info {
        margin: 16px 0 8px;
        padding: 0 12px;
    }

    .employee-info .info {
        display: flex;
        justify-content: space-between;
    }

    /* ---------- Tables ---------- */
    .employee-info-table,
    .earnings-deductions table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        /* زوايا أنعم */
        overflow: hidden;
    }

    .employee-info-table td {
        border: 1px solid var(--line);
        padding: 8px 10px;
        /* راحة في القراءة */
        text-align: left;
        font-size: 14px;
    }

    .employee-info-table td strong {
        font-weight: 800;
        color: var(--head);
    }

    .earnings-deductions th,
    .earnings-deductions td {
        border-bottom: 1px solid var(--line);
        padding: 10px 12px;
        font-size: 14px;
    }

    .earnings-deductions th {
        background-color: #fbfdff;
        /* رأس أفتح */
        border-bottom: 1px solid #eaf1ff;
        text-align: left;
        font-weight: 800;
        color: var(--head);
        letter-spacing: .2px;
    }

    .earnings-deductions tbody tr:nth-child(even) {
        background: #fcfcfd;
        /* زيبرا */
    }

    .earnings-deductions td {
        text-align: right;
        /* كما هو */
        font-variant-numeric: tabular-nums;
        /* محاذاة أرقام */
    }

    .earnings-deductions td:first-child {
        text-align: left;
        /* الوصف يسار */
    }

    /* صف الإجمالي */
    .earnings-deductions tr:last-child th {
        background: #f2f6ff;
        border-top: 2px solid #dbeafe;
        border-bottom: 2px solid #dbeafe;
        text-transform: uppercase;
        letter-spacing: .06em;
        font-size: 13px;
    }

    /* ---------- Footer ---------- */
    footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 12px 14px;
    }

    footer .net-salary {
        font-size: 18px;
        font-weight: 800;
        color: var(--success);
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        display: inline-block;
        padding: 8px 12px;
        border-radius: 10px;
        font-variant-numeric: tabular-nums;
    }

    footer .signature {
        border-top: 1px dashed var(--line-strong);
        width: 180px;
        text-align: center;
        padding-top: 8px;
        font-style: italic;
        color: var(--muted);
    }

    /* ---------- Print (A4) ---------- */
    @page {
        size: A4;
        margin: 14mm;
    }

    @media print {

        html,
        body {
            background: #fff;
        }

        body {
            padding: 0;
        }

        .salary-slip {
            border: 1px solid #ddd;
            border-radius: 0;
            box-shadow: none;
        }

        .salary-slip::after {
            opacity: .06;
        }

        .company-info img {
            filter: grayscale(0);
        }

        a[href]:after {
            content: "";
        }
    }
</style>
