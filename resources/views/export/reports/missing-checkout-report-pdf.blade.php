<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .report-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .report-header h2 {
            margin: 5px 0;
            font-size: 14px;
            text-decoration: underline;
        }

        .report-header p {
            margin: 3px 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .section-header {
            background-color: #0d7c66;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        .sub-header {
            background-color: #b2dfdb;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            height: 30px;
        }

        .text-center {
            text-align: center;
        }

        .signature-section {
            margin-top: 40px;
            font-size: 11px;
        }

        .signature-section p {
            margin: 3px 0;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 30px 0 10px 0;
            width: 100%;
        }

        .managers-row {
            width: 100%;
            margin-top: 20px;
        }

        .managers-row td {
            border: none;
            width: 50%;
            padding: 5px 0;
            vertical-align: top;
        }
    </style>
</head>

<body>

    {{-- ========== HEADER ========== --}}
    <table style="border: none; width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="border: none; width: 33%; text-align: left; vertical-align: top;">
                @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="Company Logo" style="height: 60px; object-fit: contain;">
                @endif
            </td>
            <td style="border: none; width: 34%; text-align: center; vertical-align: top;">
                <h2 style="margin: 0 0 5px 0; font-size: 16px; text-decoration: underline;">MISSING CHECKOUT REPORT</h2>
                <p style="margin: 0; font-weight: bold; font-size: 13px;">{{ $branchName }}</p>
            </td>
            <td style="border: none; width: 33%; text-align: right; vertical-align: top;">
                <p style="margin: 0 0 3px 0;"><strong>Start Date:</strong> {{ $dateFrom }}</p>
                <p style="margin: 0;"><strong>End Date:</strong> {{ $dateTo }}</p>
                <img src="{{ public_path('storage/workbench.png') }}" alt="System Logo" style="height: 50px; margin-top: 10px; object-fit: contain; border-radius: 50%;">
            </td>
        </tr>
    </table>

    {{-- ========== REPORT DATA ========== --}}
    <table>
        <thead>
            <tr class="sub-header">
                <th style="width:40px;" class="text-center">#</th>
                <th>Employee Name</th>
                <th class="text-center">Check Date</th>
                <th class="text-center">Check Time</th>
                <th class="text-center">Period</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['employee_name'] ?? '-' }}</td>
                <td class="text-center">{{ $item['checkin_date'] }}</td>
                <td class="text-center">{{ $item['checkin_time'] ?? '-' }}</td>
                <td class="text-center">{{ $item['period_name'] ?? '-' }} ({{ $item['period_start_at'] ?? '-' }} - {{ $item['period_end_at'] ?? '-' }})</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No missing checkouts found for this period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>