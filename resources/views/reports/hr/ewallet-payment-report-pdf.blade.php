<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>{{ ($isBank ?? false) ? 'Bank Verified Sheet' : 'eWallet Verified Sheet' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        @page {
            margin: 10mm;
            size: auto;
        }

        body {
            font-family: 'cairo', sans-serif;
            font-size: 12px;
            line-height: 1.2;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .wrap {
            width: 100%;
            margin: 0 auto;
        }

        .head-table {
            border-bottom: 2px solid #0d7c66;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .head-table td {
            vertical-align: middle;
        }

        .logoBox {
            width: 25%;
            text-align: left;
        }

        .center-box {
            width: 50%;
            text-align: center;
        }

        .comp {
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 5px;
            color: #0d7c66;
        }

        .title {
            text-align: center;
            font-weight: 800;
            margin: 10px 0 5px;
            font-size: 16px;
        }

        .summary-table {
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .summary-table td {
            padding: 5px;
            font-size: 13px;
        }

        table.data-table {
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #e6e6e6;
            padding: 8px;
            text-align: left;
        }

        table.data-table th {
            background-color: #fafafa;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #0d7c66;
        }
        
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

    </style>
</head>

<body>
    <div class="wrap">
        <table class="head-table" width="100%">
            <tr>
                <td class="logoBox">
                    <img style="width:100px;" src="{{ public_path('storage/logo/default.png') }}">
                </td>
                <td class="center-box">
                    <div class="comp">{{ settingWithDefault('company_name', 'Company Name') }}</div>
                    <div class="title">{{ ($isBank ?? false) ? 'Bank Verified Sheet' : 'eWallet Verified Sheet' }}</div>
                </td>
                <td class="logoBox" style="text-align: right;">
                    @if(setting('company_logo'))
                    <img style="width:100px;" src="{{ public_path('storage/' . setting('company_logo')) }}">
                    @endif
                </td>
            </tr>
        </table>

        <table class="summary-table" width="100%">
            <tr>
                <td style="width: 25%;"><strong>Month / Year:</strong></td>
                <td style="width: 25%;">{{ \Carbon\Carbon::create()->month($report->month)->format('F') }} {{ $report->year }}</td>
                <td style="width: 25%;"><strong>Total Amount:</strong></td>
                <td style="width: 25%; font-weight: bold; color: #0d7c66;">{{ formatMoneyWithCurrency($report->total_amount) }}</td>
            </tr>
            <tr>
                <td><strong>Staff Count:</strong></td>
                <td>{{ $report->employees_count }}</td>
                <td><strong>Generated On:</strong></td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>

        <table class="data-table" width="100%">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 24%;">{{ ($isBank ?? false) ? 'Bank Account No.' : 'eWallet Account No.' }}</th>
                    <th style="width: 25%;">Reward Name</th>
                    <th style="width: 15%;" class="text-right">Net Salary</th>
                    <th style="width: 30%;">Reward Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->account_number ?? '-' }}</td>
                    <td>{{ $item->reward_name }}</td>
                    <td class="text-right">{{ formatMoneyWithCurrency($item->net_salary) }}</td>
                    <td>{{ $item->reward_description }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="font-weight: bold; padding: 10px;">Total</td>
                    <td colspan="2" class="text-right" style="font-weight: bold; color: #0d7c66; padding: 10px;">{{ formatMoneyWithCurrency($report->total_amount) }}</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #777;">
            <p>This report is computer generated. No signature is required.</p>
        </div>
    </div>
</body>

</html>
