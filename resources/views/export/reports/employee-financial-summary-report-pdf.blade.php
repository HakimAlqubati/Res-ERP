<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'XBRiyaz', 'Tajawal', sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 80px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($companyLogo)
            <img src="{{ $companyLogo }}" alt="Logo">
        @endif
        <h2>{{ __('lang.employee_financial_summary_report') ?? 'Employee Financial Summary Report' }}</h2>
        <p>{{ __('lang.branch') ?? 'Branch' }}: {{ $branchName }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('lang.employee') ?? 'Employee' }}</th>
                @if(empty($branch_id))
                <th>{{ __('lang.branch') ?? 'Branch' }}</th>
                @endif
                <th>{{ __('lang.incentive_types') ?? 'Incentive Types' }}</th>
                <th>{{ __('lang.allowance_types') ?? 'Allowance Types' }}</th>
                <th>{{ __('lang.deduction_types') ?? 'Deduction Types' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->employeeName }}</td>
                @if(empty($branch_id))
                <td>{{ $item->branchName }}</td>
                @endif
                <td>{{ $item->incentiveTypes }}</td>
                <td>{{ $item->allowanceTypes }}</td>
                <td>{{ $item->deductionTypes }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px; display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; text-align: center;">
            <p><strong>{{ __('lang.branch_manager') ?? 'Branch Manager' }}</strong></p>
            <p>{{ $branchManager }}</p>
        </div>
        <div style="display: table-cell; width: 50%; text-align: center;">
            <p><strong>{{ __('lang.finance_manager') ?? 'Finance Manager' }}</strong></p>
            <p>{{ $financeManager }}</p>
        </div>
    </div>
</body>
</html>
